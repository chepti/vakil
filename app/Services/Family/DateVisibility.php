<?php

namespace App\Services\Family;

use App\Models\Person;
use App\Models\Relationship;
use Illuminate\Support\Facades\Cache;

/**
 * מי רואה אילו תאריכים — התאמה פר-אתר (ראו config/app.php → dates).
 *
 * שני כללים, שניהם כבויים כברירת מחדל:
 *   • תצוגה עברית בלבד (בלי תאריך לועזי)
 *   • לנשים נשואות לא מציגים את שנת הלידה, רק יום וחודש
 *
 * המקור היחיד לחישוב "למי השנה מוסתרת" — משמש גם את המסכים (דרך Inertia)
 * וגם את התקציר החודשי במייל.
 */
class DateVisibility
{
    public static function hideGregorian(): bool
    {
        return (bool) config('app.dates.hide_gregorian');
    }

    /**
     * מזהי הדמויות שלהן שנת הלידה מוסתרת — נשים עם קשר זוגיות כלשהו.
     * במטמון לדקה: השאילתה קטנה אבל רצה בכל בקשה.
     *
     * @return int[]
     */
    public static function hiddenBirthYearIds(): array
    {
        if (! config('app.dates.hide_married_women_birth_year')) {
            return [];
        }

        return Cache::remember('dates.hidden_birth_year_ids', 60, function () {
            $spouseIds = Relationship::where('type', 'spouse')
                ->get(['person1_id', 'person2_id'])
                ->flatMap(fn ($r) => [$r->person1_id, $r->person2_id])
                ->unique();

            return Person::whereIn('id', $spouseIds)
                ->where('gender', 'female')
                ->pluck('id')
                ->all();
        });
    }

    public static function birthYearHidden(?int $personId): bool
    {
        return $personId !== null && in_array($personId, self::hiddenBirthYearIds(), true);
    }
}
