<?php

namespace App\Services\Photos;

use App\Models\Person;
use App\Models\Photo;
use Illuminate\Support\Facades\Storage;

/**
 * ניהול קבצי תמונה משותפים.
 *
 * מאז שאותו קובץ-מקור משמש כמה חיתוכים (thumb) בלי לשכפל אותו,
 * אסור למחוק קובץ פיזי כל עוד רשומה אחרת עדיין מפנה אליו —
 * לא ב-thumb_path, לא ב-original_path ולא כתמונת פרופיל של דמות.
 */
class PhotoStorage
{
    /**
     * מוחק קובץ מה-storage הציבורי רק אם אף אחד כבר לא מפנה אליו.
     *
     * @return bool האם הקובץ נמחק בפועל
     */
    public static function deleteIfUnreferenced(?string $path, ?int $ignorePhotoId = null): bool
    {
        if (! $path) {
            return false;
        }

        $stillUsed = Photo::query()
            ->when($ignorePhotoId, fn ($q) => $q->where('id', '!=', $ignorePhotoId))
            ->where(fn ($q) => $q->where('thumb_path', $path)->orWhere('original_path', $path))
            ->exists();

        if ($stillUsed) {
            return false;
        }

        if (Person::where('profile_photo', $path)->exists()) {
            return false;
        }

        return Storage::disk('public')->delete($path);
    }
}
