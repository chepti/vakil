<?php

namespace App\Services\Branch;

use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * ייבוא "ענף" מקובץ ZIP שנוצר ב-BranchExportService — באתר-היעד.
 *
 * upsert לפי origin_uuid (create-or-update): הרצה חוזרת עם חבילה חדשה
 * מעדכנת רשומות קיימות במקום לשכפל — זה הבסיס לסנכרון עתידי.
 *
 * כל הייבוא רץ בתוך טרנזקציה ובלי model events — כדי שלא יישלחו
 * מיילי הזמנה/התראה על כל דמות ואירוע מיובאים.
 */
class BranchImportService
{
    /** @var array<string, array{created: int, updated: int}> */
    public array $stats = [];

    public array $warnings = [];

    public function import(string $zipPath, User $importingAdmin, bool $setRootAsMain = true): array
    {
        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException("Cannot open zip: {$zipPath}");
        }

        $json = $zip->getFromName('branch.json');
        if ($json === false) {
            $zip->close();
            throw new \RuntimeException('branch.json not found in the archive — not a branch export?');
        }

        $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $version = $payload['manifest']['schema_version'] ?? null;
        if ($version !== BranchSchema::SCHEMA_VERSION) {
            $zip->close();
            throw new \RuntimeException(
                'Schema version mismatch: package=' . var_export($version, true)
                . ', app=' . BranchSchema::SCHEMA_VERSION
                . '. Update both sites to the same code before syncing.'
            );
        }

        $this->extractMedia($zip, $payload['media'] ?? []);
        $zip->close();

        DB::transaction(function () use ($payload, $importingAdmin, $setRootAsMain) {
            Model::withoutEvents(function () use ($payload, $importingAdmin, $setRootAsMain) {
                $this->importTables($payload, $importingAdmin);

                if ($setRootAsMain) {
                    $this->setRootAsMainPerson($payload['manifest']['root_origin_uuid'] ?? null);
                }
            });
        });

        return [
            'manifest' => $payload['manifest'],
            'stats'    => $this->stats,
            'warnings' => $this->warnings,
        ];
    }

    /**
     * מייבא את הטבלאות לפי סדר הסכמה (תלויות תמיד אחרי מה שהן מפנות אליו),
     * וממפה origin_uuid → id מקומי תוך כדי.
     */
    private function importTables(array $payload, User $importingAdmin): void
    {
        $schema = BranchSchema::tables();

        /** @var array<string, array<string, int>> $idMaps origin_uuid => local id, לכל טבלה */
        $idMaps = [];

        foreach ($schema as $tableName => $def) {
            $rows = $payload['tables'][$tableName] ?? [];
            $modelClass = $def['model'];
            $created = 0;
            $updated = 0;
            $idMaps[$tableName] = [];

            foreach ($rows as $data) {
                $uuid = $data['origin_uuid'] ?? null;
                if (! $uuid) {
                    $this->warnings[] = "{$tableName}: record without origin_uuid skipped";
                    continue;
                }

                // FK: <column>__uuid → id מקומי של הישות שכבר יובאה
                $skip = false;
                foreach ($def['fk'] as $column => $refTable) {
                    $refUuid = $data[$column . '__uuid'] ?? null;
                    unset($data[$column . '__uuid']);

                    if ($refUuid === null) {
                        $data[$column] = null;
                        continue;
                    }

                    $localId = $idMaps[$refTable][$refUuid]
                        ?? DB::table($refTable)->where('origin_uuid', $refUuid)->value('id');

                    if ($localId === null) {
                        $this->warnings[] =
                            "{$tableName} {$uuid}: referenced {$refTable} {$refUuid} not found — record skipped";
                        $skip = true;
                        break;
                    }
                    $data[$column] = $localId;
                }
                if ($skip) {
                    continue;
                }

                // "נוצר ע"י" → האדמין המייבא
                if ($def['creator']) {
                    $data[$def['creator']] = $importingAdmin->id;
                }

                // באתר היעד רק השורש יהיה main — לא מייבאים דגל מהמקור
                if ($tableName === 'people') {
                    $data['is_main_person'] = false;
                }

                $model = $modelClass::query()->updateOrCreate(
                    ['origin_uuid' => $uuid],
                    $data,
                );

                $model->wasRecentlyCreated ? $created++ : $updated++;
                $idMaps[$tableName][$uuid] = $model->id;
            }

            $this->stats[$tableName] = ['created' => $created, 'updated' => $updated];
        }
    }

    /** מחלץ את קבצי המדיה מה-ZIP אל storage הציבורי, באותם נתיבים יחסיים. */
    private function extractMedia(ZipArchive $zip, array $mediaPaths): void
    {
        $disk = Storage::disk('public');

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if (! str_starts_with($entry, 'media/')) {
                continue;
            }

            $relative = substr($entry, strlen('media/'));
            // הגנה בסיסית מפני zip-slip
            if ($relative === '' || str_contains($relative, '..')) {
                $this->warnings[] = "media entry skipped (unsafe path): {$entry}";
                continue;
            }

            $stream = $zip->getStream($entry);
            if ($stream === false) {
                $this->warnings[] = "media entry unreadable: {$entry}";
                continue;
            }
            $disk->put($relative, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    /** ממרכז את העץ סביב שורש-הענף: רק הוא is_main_person באתר היעד. */
    private function setRootAsMainPerson(?string $rootUuid): void
    {
        if (! $rootUuid) {
            $this->warnings[] = 'manifest has no root_origin_uuid — main person not set';
            return;
        }

        $root = Person::where('origin_uuid', $rootUuid)->first();
        if (! $root) {
            $this->warnings[] = "root person {$rootUuid} not found after import — main person not set";
            return;
        }

        Person::where('is_main_person', true)->where('id', '!=', $root->id)
            ->update(['is_main_person' => false]);
        $root->update(['is_main_person' => true]);
    }
}
