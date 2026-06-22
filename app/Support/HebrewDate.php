<?php

namespace App\Support;

use Carbon\Carbon;

/**
 * המרת תאריך לועזי לתאריך עברי, בעזרת תוסף ה-calendar של PHP (jdtojewish).
 *
 * מיפוי מספרי החודשים של PHP (אומת אמפירית מול השרת):
 *   1=תשרי 2=חשוון 3=כסלו 4=טבת 5=שבט
 *   6=אדר א׳ (רק בשנה מעוברת)   7=אדר / אדר ב׳   8=ניסן 9=אייר 10=סיוון 11=תמוז 12=אב 13=אלול
 * בשנה רגילה חודש 6 לא קיים, והאדר היחיד ממוספר 7.
 */
class HebrewDate
{
    /** שמות בסיס לחודשים (חודש 7 מטופל בנפרד לפי מעוברת/רגילה) */
    private const MONTHS = [
        1  => 'תשרי',
        2  => 'חשוון',
        3  => 'כסלו',
        4  => 'טבת',
        5  => 'שבט',
        6  => 'אדר א׳',
        7  => 'אדר',
        8  => 'ניסן',
        9  => 'אייר',
        10 => 'סיוון',
        11 => 'תמוז',
        12 => 'אב',
        13 => 'אלול',
    ];

    /** שנה עברית מעוברת? (7 שנים מעוברות במחזור של 19) */
    public static function isLeapYear(int $hebrewYear): bool
    {
        return ((7 * $hebrewYear + 1) % 19) < 7;
    }

    /**
     * פירוק תאריך לועזי לרכיביו העבריים.
     *
     * @return array{jd:int, year:int, month:int, day:int, monthName:string, dayGematria:string, yearGematria:string}
     */
    public static function parts(Carbon $date): array
    {
        $jd = gregoriantojd((int) $date->month, (int) $date->day, (int) $date->year);
        [$month, $day, $year] = array_map('intval', explode('/', jdtojewish($jd)));

        return [
            'jd'           => $jd,
            'year'         => $year,
            'month'        => $month,
            'day'          => $day,
            'monthName'    => self::monthName($month, $year),
            'dayGematria'  => self::gematria($day),
            'yearGematria' => self::yearGematria($year),
        ];
    }

    /** שם החודש העברי לפי מספר PHP ושנה (לזיהוי אדר ב׳ במעוברת) */
    public static function monthName(int $month, int $hebrewYear): string
    {
        if ($month === 7 && self::isLeapYear($hebrewYear)) {
            return 'אדר ב׳';
        }

        return self::MONTHS[$month] ?? '';
    }

    /** "תמוז תשפ״ו" */
    public static function monthYear(Carbon $date): string
    {
        $p = self::parts($date);

        return "{$p['monthName']} {$p['yearGematria']}";
    }

    /** "ז׳ בתמוז תשפ״ו" */
    public static function format(Carbon $date): string
    {
        $p = self::parts($date);

        return "{$p['dayGematria']} ב{$p['monthName']} {$p['yearGematria']}";
    }

    /** "ז׳ בתמוז" — יום + חודש בלבד (ליום-הולדת/יום-נישואין חוזר) */
    public static function dayMonth(Carbon $date): string
    {
        $p = self::parts($date);

        return "{$p['dayGematria']} ב{$p['monthName']}";
    }

    /** האם התאריך הוא א׳ בחודש (ראש חודש)? */
    public static function isRoshChodesh(Carbon $date): bool
    {
        return self::parts($date)['day'] === 1;
    }

    /**
     * טווח הימים הלועזי של החודש העברי שמכיל את התאריך.
     *
     * @return array{0:Carbon,1:Carbon} [תחילת החודש, סוף החודש] (כולל)
     */
    public static function monthRange(Carbon $date): array
    {
        // אחורה עד א׳ בחודש
        $start = $date->copy()->startOfDay();
        $guard = 0;
        while (self::parts($start)['day'] !== 1 && $guard++ < 40) {
            $start->subDay();
        }

        // קדימה עד א׳ הבא, ואז יום אחד אחורה = סוף החודש
        $next = $start->copy()->addDay();
        $guard = 0;
        while (self::parts($next)['day'] !== 1 && $guard++ < 40) {
            $next->addDay();
        }

        return [$start, $next->copy()->subDay()->endOfDay()];
    }

    /** מנרמל אדר א׳ (6) לאדר (7) כדי שתאריכי-אדר יתאימו בין שנה מעוברת לרגילה */
    public static function normalizeAdar(int $month): int
    {
        return $month === 6 ? 7 : $month;
    }

    // ─── גימטריה ──────────────────────────────────────────────────

    /** גימטריה של שנה עברית (מציג רק את המאות/עשרות/אחדות, ללא האלפים): 5786 → תשפ״ו */
    public static function yearGematria(int $hebrewYear): string
    {
        return self::gematria($hebrewYear % 1000);
    }

    /** מספר → גימטריה עם גרש/גרשיים, כולל ט״ו / ט״ז */
    public static function gematria(int $n): string
    {
        $hundreds = [100 => 'ק', 200 => 'ר', 300 => 'ש', 400 => 'ת'];
        $tens     = [10 => 'י', 20 => 'כ', 30 => 'ל', 40 => 'מ', 50 => 'נ', 60 => 'ס', 70 => 'ע', 80 => 'פ', 90 => 'צ'];
        $ones     = [1 => 'א', 2 => 'ב', 3 => 'ג', 4 => 'ד', 5 => 'ה', 6 => 'ו', 7 => 'ז', 8 => 'ח', 9 => 'ט'];

        $s = '';

        while ($n >= 400) {
            $s .= 'ת';
            $n -= 400;
        }
        if ($n >= 100) {
            $h = intdiv($n, 100) * 100;
            $s .= $hundreds[$h];
            $n -= $h;
        }

        // 15 ו-16 נכתבים ט״ו / ט״ז ולא י-ה / י-ו
        if ($n === 15) {
            $s .= 'טו';
            $n = 0;
        } elseif ($n === 16) {
            $s .= 'טז';
            $n = 0;
        }

        if ($n >= 10) {
            $t = intdiv($n, 10) * 10;
            $s .= $tens[$t];
            $n -= $t;
        }
        if ($n >= 1) {
            $s .= $ones[$n];
        }

        // גרש (אות אחת) או גרשיים (לפני האות האחרונה)
        $len = mb_strlen($s);
        if ($len === 0) {
            return '';
        }
        if ($len === 1) {
            return $s . '׳';
        }

        return mb_substr($s, 0, $len - 1) . '״' . mb_substr($s, $len - 1, 1);
    }
}
