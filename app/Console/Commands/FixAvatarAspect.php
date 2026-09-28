<?php

namespace App\Console\Commands;

use App\Models\Photo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * תיקון חד-פעמי: אווטארים שנוצרו אוטומטית (avatars/auto_*) נחתכו בכוח לריבוע
 * 400x400 גם כשריבוע התיוג היה מלבן — מה שמתח את הפנים ואיבד פרופורציה.
 * הפקודה בונה כל קובץ כזה מחדש מהתמונה המקורית ומלבן החיתוך השמורים ברשומה,
 * ודורסת את אותו קובץ בדיוק — ולכן אין צורך לגעת ב-DB.
 */
class FixAvatarAspect extends Command
{
    protected $signature = 'photos:fix-avatar-aspect {--dry : הצגה בלבד, בלי לכתוב קבצים}';

    protected $description = 'מתקן פרופורציה של אווטארים אוטומטיים שנדחסו לריבוע';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');
        $disk = Storage::disk('public');

        $photos = Photo::where('thumb_path', 'like', 'avatars/auto_%')
            ->whereNotNull('original_path')
            ->get();

        $this->info('נמצאו ' . $photos->count() . ' אווטארים אוטומטיים לבדיקה.');

        $fixed = 0; $skipped = 0; $failed = 0;

        foreach ($photos as $photo) {
            if (! $disk->exists($photo->original_path)) { $failed++; continue; }

            $fullPath = $disk->path($photo->original_path);
            $info = @getimagesize($fullPath);
            if (! $info) { $failed++; continue; }

            [$width, $height, $type] = $info;

            $sx = (int) round((($photo->crop_x ?: 0) / 100) * $width);
            $sy = (int) round((($photo->crop_y ?: 0) / 100) * $height);
            $sw = (int) round((($photo->crop_w ?: 10) / 100) * $width);
            $sh = (int) round((($photo->crop_h ?: 10) / 100) * $height);
            $sx = max(0, min($sx, $width - 1));
            $sy = max(0, min($sy, $height - 1));
            $sw = max(1, min($sw, $width - $sx));
            $sh = max(1, min($sh, $height - $sy));

            [$dw, $dh] = RefreshNewestProfilePhotos::fitBox($sw, $sh, 400);

            // כבר בפרופורציה הנכונה? אין מה לעשות.
            $cur = $disk->exists($photo->thumb_path) ? @getimagesize($disk->path($photo->thumb_path)) : null;
            if ($cur && abs(($cur[0] / max(1, $cur[1])) - ($dw / max(1, $dh))) < 0.02) { $skipped++; continue; }

            if ($dry) {
                $fixed++;
                if ($fixed <= 10) {
                    $this->line("  person_id={$photo->person_id}: "
                        . ($cur ? "{$cur[0]}x{$cur[1]}" : '?') . " → {$dw}x{$dh}");
                }
                continue;
            }

            $src = match ($type) {
                IMAGETYPE_JPEG => @imagecreatefromjpeg($fullPath),
                IMAGETYPE_PNG  => @imagecreatefrompng($fullPath),
                IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($fullPath) : null,
                default        => null,
            };
            if (! $src) { $failed++; continue; }

            $dst = imagecreatetruecolor($dw, $dh);
            imagecopyresampled($dst, $src, 0, 0, $sx, $sy, $dw, $dh, $sw, $sh);
            imagedestroy($src);

            // כתיבה לאותו נתיב — הרשומות ב-photos וב-people ממשיכות להצביע נכון.
            $ok = imagejpeg($dst, $disk->path($photo->thumb_path), 88);
            imagedestroy($dst);

            $ok ? $fixed++ : $failed++;
        }

        $this->newLine();
        $this->info(($dry ? '[יבש] ' : '') . "תוקנו: {$fixed} | תקינים כבר: {$skipped} | נכשלו: {$failed}");

        if ($dry) {
            $this->comment('להרצה בפועל: php artisan photos:fix-avatar-aspect');
        }

        return self::SUCCESS;
    }
}
