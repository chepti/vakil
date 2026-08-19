<?php

namespace App\Console\Commands;

use App\Models\FamilyPhoto;
use App\Models\Person;
use App\Models\Photo;
use App\Models\PhotoTag;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * מעדכן בבת אחת את תמונת הפרופיל של כל דמות להיות התיוג שלה בעל שנת הצילום החדשה ביותר.
 * תיקון חד-פעמי למשפחות שכל התמונות שלהן כבר מתויגות בשנה, אחרי שתיוג כרונולוגי החליף
 * פרופילים בלי סדר. מתוכן ואילך ההגנה הרגילה (PersonController::uploadPhoto, auto=1)
 * שומרת שתמונה ישנה לא תדרוס פרופיל חדש יותר.
 */
class RefreshNewestProfilePhotos extends Command
{
    protected $signature = 'photos:refresh-newest-profiles
        {--dry : הצגה בלבד — מי ישתנה ולאיזו שנה, בלי לשנות דבר}';

    protected $description = 'מעדכן את תמונת הפרופיל של כל דמות לתיוג בעל שנת הצילום החדשה ביותר שלה';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');

        if (! function_exists('imagecreatefromjpeg')) {
            $this->error('סיומת GD לא מותקנת ב-PHP — לא ניתן לחתוך תמונות. עצירה.');
            return self::FAILURE;
        }

        $tags = PhotoTag::whereHas('familyPhoto', fn ($q) => $q->whereNotNull('taken_year'))
            ->with('familyPhoto')
            ->get()
            ->groupBy('person_id');

        $updated = 0;
        $skipped = 0;
        $failed  = 0;

        foreach (Person::whereIn('id', $tags->keys())->get() as $person) {
            $best = $tags[$person->id]
                ->sortByDesc(fn ($t) => $t->familyPhoto->taken_year)
                ->first();

            $bestYear = $best->familyPhoto->taken_year;

            // אין צורך לגעת אם הפרופיל הנוכחי כבר מתויג בשנה חדשה כמו זו או חדשה יותר
            if ($person->profile_photo_year !== null && $person->profile_photo_year >= $bestYear) {
                $skipped++;
                continue;
            }

            $currentLabel = $person->profile_photo_year ? " (כרגע: {$person->profile_photo_year})" : ' (כרגע: לא ידוע)';
            if ($dry) {
                $this->line("{$person->full_name}: → {$bestYear}{$currentLabel}");
                $updated++;
                continue;
            }

            $thumbPath = $this->cropAndStore($best);
            if (! $thumbPath) {
                $this->warn("נכשל חיתוך עבור {$person->full_name}");
                $failed++;
                continue;
            }

            Photo::create([
                'person_id'     => $person->id,
                'thumb_path'    => $thumbPath,
                'original_path' => $best->familyPhoto->path,
                'crop_x'        => $best->x_percent,
                'crop_y'        => $best->y_percent,
                'crop_w'        => $best->w_percent,
                'crop_h'        => $best->h_percent,
                'taken_year'    => $bestYear,
            ]);

            $person->update(['profile_photo' => $thumbPath, 'profile_photo_year' => $bestYear]);
            $this->line("{$person->full_name}: → {$bestYear}{$currentLabel} ✓");
            $updated++;
        }

        $prefix = $dry ? '[DRY] ' : '';
        $this->info("{$prefix}עודכנו {$updated} דמויות · נשארו כמו שהיו {$skipped} · נכשלו {$failed}");

        if ($dry && $updated) {
            $this->comment('להרצה בפועל: php artisan photos:refresh-newest-profiles');
        }

        return self::SUCCESS;
    }

    /** חותך את ריבוע התיוג מהתמונה המשפחתית ושומר כ-avatar חדש. מחזיר את הנתיב היחסי, או null אם נכשל. */
    private function cropAndStore(PhotoTag $tag): ?string
    {
        $disk = Storage::disk('public');
        $sourcePath = $tag->familyPhoto->path;
        if (! $disk->exists($sourcePath)) return null;

        $fullPath = $disk->path($sourcePath);
        $info = @getimagesize($fullPath);
        if (! $info) return null;

        [$width, $height, $type] = $info;
        $src = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($fullPath),
            IMAGETYPE_PNG  => @imagecreatefrompng($fullPath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($fullPath) : null,
            default        => null,
        };
        if (! $src) return null;

        $sx = (int) round(($tag->x_percent / 100) * $width);
        $sy = (int) round(($tag->y_percent / 100) * $height);
        $sw = (int) round((($tag->w_percent ?: 10) / 100) * $width);
        $sh = (int) round((($tag->h_percent ?: 10) / 100) * $height);
        $sx = max(0, min($sx, $width - 1));
        $sy = max(0, min($sy, $height - 1));
        $sw = max(1, min($sw, $width - $sx));
        $sh = max(1, min($sh, $height - $sy));

        $size = 400; // גודל ריבוע האווטאר הפלט — עקבי עם חיתוכים אחרים באתר
        $dst = imagecreatetruecolor($size, $size);
        imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $size, $size, $sw, $sh);
        imagedestroy($src);

        $filename = 'avatars/' . uniqid('auto_', true) . '.jpg';
        $ok = imagejpeg($dst, $disk->path($filename), 88);
        imagedestroy($dst);

        return $ok ? $filename : null;
    }
}
