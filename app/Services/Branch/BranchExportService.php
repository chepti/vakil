<?php

namespace App\Services\Branch;

use App\Models\FamilyPhoto;
use App\Models\Person;
use App\Models\Relationship;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

/**
 * ייצוא "ענף" — דמות-שורש + כל צאצאיה + בני/בנות הזוג שלהם,
 * עם כל התוכן הצמוד (קשרים, אירועים, סיפורי שם, תמונות, אלבום + תיוגים)
 * לקובץ ZIP עם manifest, מוכן לייבוא באתר-יעד נפרד.
 *
 * כל FK מבוטא ב-JSON כ-origin_uuid (לא id מקומי) — כך הייבוא אינו תלוי
 * במזהים של אתר המקור, ואותה ישות נשארת עם אותה "תעודת זהות" בשני האתרים.
 */
class BranchExportService
{
    /** @var array<string, string[]> tableName => רשימת הערות/אזהרות שנאספו בייצוא */
    public array $warnings = [];

    /**
     * @return string הנתיב המלא לקובץ ה-ZIP שנוצר
     */
    public function export(Person $root, string $outputDir): string
    {
        $memberIds = $this->collectBranchMemberIds($root);

        $payload = $this->buildPayload($root, $memberIds);

        return $this->writeZip($root, $payload, $outputDir);
    }

    /**
     * קבוצת הענף: השורש + כל הצאצאים (רקורסיבית) + בני/בנות זוג של כל אחד.
     * בבני-זוג עוצרים — לא מושכים את ההורים/אחים שלהם.
     *
     * @return int[] מזהי כל חברי הענף
     */
    public function collectBranchMemberIds(Person $root): array
    {
        $core = array_merge([$root->id], $root->descendantIds());
        $coreSet = array_flip($core);

        // בני/בנות זוג של חברי הליבה (כולל גרושים/אלמנים — is_former נשמר בקשר עצמו)
        $spouseIds = Relationship::where('type', 'spouse')
            ->where(function ($q) use ($core) {
                $q->whereIn('person1_id', $core)->orWhereIn('person2_id', $core);
            })
            ->get()
            ->flatMap(fn ($r) => [$r->person1_id, $r->person2_id])
            ->filter(fn ($id) => ! isset($coreSet[$id]))
            ->unique()
            ->values()
            ->all();

        return array_values(array_unique(array_merge($core, $spouseIds)));
    }

    /**
     * בונה את מבנה הנתונים המלא: לכל טבלה — רשימת רשומות כשה-FK
     * מוחלפים ב-origin_uuid, ורשימת קבצי מדיה לאריזה.
     */
    private function buildPayload(Person $root, array $memberIds): array
    {
        $schema = BranchSchema::tables();
        $tables = [];
        $mediaFiles = [];   // storage-relative path => true

        // אוספים את הרשומות של כל טבלה לפי כללי הסינון של הענף
        $records = $this->collectRecords($memberIds);

        // מפות origin_uuid לכל ישות שמופנים אליה (למשל people, events, family_photos)
        $uuidMaps = [];
        foreach ($records as $tableName => $rows) {
            $uuidMaps[$tableName] = $rows->pluck('origin_uuid', 'id')->all();
        }

        foreach ($schema as $tableName => $def) {
            $rows = $records[$tableName];
            $exported = [];

            foreach ($rows as $row) {
                $data = $row->getAttributes();

                foreach (BranchSchema::SKIP_COLUMNS as $col) {
                    unset($data[$col]);
                }

                // FK → origin_uuid של הישות המופנית
                foreach ($def['fk'] as $column => $refTable) {
                    $localId = $data[$column] ?? null;
                    unset($data[$column]);

                    if ($localId === null) {
                        $data[$column . '__uuid'] = null;
                        continue;
                    }

                    $refUuid = $uuidMaps[$refTable][$localId] ?? null;
                    if ($refUuid === null) {
                        // מפנה אל מחוץ לענף (למשל אירוע של דמות חיצונית שסונן) — משאירים null
                        $this->warnings[$tableName][] =
                            "record origin_uuid={$data['origin_uuid']}: {$column} points outside the branch, set to null";
                    }
                    $data[$column . '__uuid'] = $refUuid;
                }

                // שדות "נוצר ע"י" לא נוסעים — ימופו לאדמין המייבא
                if ($def['creator']) {
                    unset($data[$def['creator']]);
                }

                // איסוף קבצי מדיה
                foreach ($def['media'] as $mediaCol) {
                    if (! empty($data[$mediaCol])) {
                        $mediaFiles[$data[$mediaCol]] = true;
                    }
                }

                $exported[] = $data;
            }

            $tables[$tableName] = $exported;
        }

        return [
            'manifest' => [
                'schema_version'   => BranchSchema::SCHEMA_VERSION,
                'exported_at'      => now()->toIso8601String(),
                'source_app_url'   => config('app.url'),
                'root_origin_uuid' => $root->origin_uuid,
                'root_name'        => $root->full_name,
                'counts'           => array_map('count', $tables),
                'warnings'         => $this->warnings,
            ],
            'tables' => $tables,
            'media'  => array_keys($mediaFiles),
        ];
    }

    /**
     * שולף את רשומות כל טבלה לפי חברי הענף.
     *
     * @param int[] $memberIds
     * @return array<string, \Illuminate\Support\Collection>
     */
    private function collectRecords(array $memberIds): array
    {
        $people = Person::whereIn('id', $memberIds)->get();

        // קשרים — רק כששני הצדדים בענף
        $relationships = Relationship::whereIn('person1_id', $memberIds)
            ->whereIn('person2_id', $memberIds)
            ->get();

        // אירועים של חברי הענף
        $events = \App\Models\Event::whereIn('person_id', $memberIds)->get();
        $eventIds = $events->pluck('id')->all();

        // סיפורי שם של חברי הענף
        $nameStories = \App\Models\NameStory::whereIn('person_id', $memberIds)->get();

        // תמונות אישיות: של חברי הענף, או של אירועי הענף
        $photos = \App\Models\Photo::where(function ($q) use ($memberIds, $eventIds) {
            $q->whereIn('person_id', $memberIds);
            if ($eventIds) {
                $q->orWhereIn('event_id', $eventIds);
            }
        })->get();

        // אלבום משפחתי: תמונות שבהן מתויג לפחות חבר-ענף אחד
        $familyPhotoIds = \App\Models\PhotoTag::whereIn('person_id', $memberIds)
            ->distinct()->pluck('family_photo_id')->all();
        $familyPhotos = FamilyPhoto::whereIn('id', $familyPhotoIds)->get();

        // תיוגים — רק של חברי הענף (מתויגים חיצוניים באותה תמונה לא נוסעים)
        $photoTags = \App\Models\PhotoTag::whereIn('family_photo_id', $familyPhotoIds)
            ->whereIn('person_id', $memberIds)
            ->get();

        // תמונות אלבום עם מתויגים גם מחוץ לענף — מסמנים ב-manifest ("חוצות-ענפים")
        $crossBranch = \App\Models\PhotoTag::whereIn('family_photo_id', $familyPhotoIds)
            ->whereNotIn('person_id', $memberIds)
            ->distinct()->pluck('family_photo_id');
        foreach ($crossBranch as $fpId) {
            $uuid = $familyPhotos->firstWhere('id', $fpId)?->origin_uuid;
            $this->warnings['family_photos'][] =
                "photo origin_uuid={$uuid}: also tags people outside the branch (tags for them were not exported)";
        }

        return [
            'people'        => $people,
            'family_photos' => $familyPhotos,
            'relationships' => $relationships,
            'events'        => $events,
            'name_stories'  => $nameStories,
            'photos'        => $photos,
            'photo_tags'    => $photoTags,
        ];
    }

    /**
     * אורז את ה-JSON + קבצי המדיה ל-ZIP.
     * מבנה פנימי: branch.json + media/<הנתיב-המקורי-ב-storage>
     */
    private function writeZip(Person $root, array $payload, string $outputDir): string
    {
        if (! is_dir($outputDir)) {
            mkdir($outputDir, 0775, true);
        }

        $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', $root->full_name) ?: 'branch';
        $zipPath = rtrim($outputDir, '/\\') . DIRECTORY_SEPARATOR
            . 'branch-' . $slug . '-' . now()->format('Ymd-His') . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Cannot create zip at {$zipPath}");
        }

        $zip->addFromString('branch.json', json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR
        ));

        $disk = Storage::disk('public');
        $missing = [];
        foreach ($payload['media'] as $relativePath) {
            if ($disk->exists($relativePath)) {
                // נתיב פנימי תמיד עם סלאש קדמי
                $entry = 'media/' . str_replace('\\', '/', $relativePath);
                $zip->addFile($disk->path($relativePath), $entry);
            } else {
                $missing[] = $relativePath;
            }
        }

        if ($missing) {
            $zip->addFromString('missing-media.json', json_encode(
                $missing, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
            ));
        }

        $zip->close();

        return $zipPath;
    }
}
