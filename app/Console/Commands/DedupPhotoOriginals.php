<?php

namespace App\Console\Commands;

use App\Models\Photo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DedupPhotoOriginals extends Command
{
    protected $signature = 'photos:dedup-originals
        {--dry : הצגה בלבד — מה יאוחד וכמה מקום יתפנה, בלי לשנות דבר}';

    protected $description = 'מאחד עותקים כפולים של תמונות-מקור (photos/originals) לפי תוכן — מפנה מחדש את הרשומות ומוחק כפילויות';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');
        $disk = Storage::disk('public');

        $photos = Photo::whereNotNull('original_path')
            ->where('original_path', 'like', 'photos/originals/%')
            ->orderBy('id')
            ->get();

        $this->info(($dry ? '[DRY] ' : '') . "בודק {$photos->count()} רשומות עם original_path...");

        // hash → הנתיב הקנוני (הראשון שנראה עם התוכן הזה)
        $canonical = [];
        // photoId → נתיב חדש למיפוי
        $repoints = [];
        $missing = 0;

        foreach ($photos as $photo) {
            $path = $photo->original_path;
            if (! $disk->exists($path)) {
                $missing++;
                continue;
            }

            $hash = hash_file('md5', $disk->path($path));

            if (! isset($canonical[$hash])) {
                $canonical[$hash] = $path;
            } elseif ($canonical[$hash] !== $path) {
                $repoints[$photo->id] = $canonical[$hash];
            }
        }

        $this->info('תוכן ייחודי: ' . count($canonical) . ' | רשומות למיפוי מחדש: ' . count($repoints)
            . ($missing ? " | קבצים חסרים: {$missing}" : ''));

        // מיפוי מחדש של הרשומות אל הקובץ הקנוני
        if (! $dry) {
            foreach ($repoints as $photoId => $newPath) {
                Photo::where('id', $photoId)->update(['original_path' => $newPath]);
            }
        }

        // נתיבים שעדיין בשימוש אחרי המיפוי: לכל רשומה — היעד החדש אם מופתה, אחרת המקורי
        $referenced = [];
        foreach ($photos as $photo) {
            $path = $repoints[$photo->id] ?? $photo->original_path;
            $referenced[$path] = true;
        }

        // מחיקת קבצים בתיקיית originals שכבר לא מופנים מאף רשומה
        $allFiles = $disk->files('photos/originals');
        $freedBytes = 0;
        $deleted = 0;
        foreach ($allFiles as $file) {
            if (isset($referenced[$file])) {
                continue;
            }
            $freedBytes += $disk->size($file);
            $deleted++;
            if (! $dry) {
                $disk->delete($file);
            }
        }

        $mb = round($freedBytes / 1048576, 1);
        $this->info(($dry ? '[DRY] יימחקו ' : 'נמחקו ') . "{$deleted} קבצים כפולים/יתומים — {$mb}MB " . ($dry ? 'יתפנו' : 'התפנו'));

        if ($dry && ($deleted || count($repoints))) {
            $this->comment('להרצה בפועל: php artisan photos:dedup-originals');
        }

        return self::SUCCESS;
    }
}
