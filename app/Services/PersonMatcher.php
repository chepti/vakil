<?php

namespace App\Services;

use App\Models\Person;
use Illuminate\Support\Collection;

/**
 * מציאת דמויות קיימות שעשויות להיות זהות לשורת ייבוא (name/last_name/maiden_name/phone).
 * משמש את PersonImportController להציע התאמות לפני יצירת דמות חדשה.
 */
class PersonMatcher
{
    /**
     * @param array{first_name:string,last_name?:string,maiden_name?:string,phone?:string} $row
     * @return Collection<int, array{person:Person, score:int}> ממוין מהניקוד הגבוה לנמוך
     */
    public function findCandidates(array $row): Collection
    {
        $firstName = trim($row['first_name'] ?? '');
        if ($firstName === '') {
            return collect();
        }

        $lastName   = trim($row['last_name'] ?? '');
        $maidenName = trim($row['maiden_name'] ?? '');
        $phone      = trim($row['phone'] ?? '');

        // שם פרטי הוא לרוב מילה אחת ראשונה מספיקה כדי לתפוס וריאציות ("יעל" מול "יעל בת שבע")
        $firstWord = mb_substr($firstName, 0, mb_strpos($firstName, ' ') ?: mb_strlen($firstName));

        $candidates = Person::where(function ($q) use ($firstName, $firstWord) {
            $q->where('first_name', $firstName)
              ->orWhere('first_name', 'like', "%{$firstWord}%")
              ->orWhere('first_name', 'like', "%{$firstName}%");
        })->get();

        return $candidates
            ->map(function (Person $person) use ($firstName, $lastName, $maidenName, $phone) {
                $score = 0;

                if (mb_strtolower($person->first_name) === mb_strtolower($firstName)) {
                    $score += 3;
                } elseif (str_contains($person->first_name, $firstWord) || str_contains($firstName, $person->first_name)) {
                    $score += 1;
                }

                if ($phone !== '' && $person->phone !== null && $this->normalizePhone($person->phone) === $this->normalizePhone($phone)) {
                    $score += 5;
                }

                if ($lastName !== '' && $person->last_name !== null && mb_strtolower($person->last_name) === mb_strtolower($lastName)) {
                    $score += 2;
                }

                if ($maidenName !== '' && $person->maiden_name !== null && mb_strtolower($person->maiden_name) === mb_strtolower($maidenName)) {
                    $score += 2;
                }
                // גם התאמה הפוכה: שם נעורים בקובץ יכול להתאים ל-last_name הקיים (אם עדיין לא התחתנה ב-DB)
                if ($maidenName !== '' && $person->last_name !== null && mb_strtolower($person->last_name) === mb_strtolower($maidenName)) {
                    $score += 1;
                }

                return ['person' => $person, 'score' => $score];
            })
            ->filter(fn($c) => $c['score'] > 0)
            ->sortByDesc('score')
            ->values();
    }

    /** האם קיימת התאמה יחידה וחזקה מספיק כדי להציע ברירת מחדל אוטומטית */
    public function isConfidentMatch(Collection $candidates): bool
    {
        if ($candidates->isEmpty()) {
            return false;
        }
        if ($candidates->count() === 1) {
            return $candidates->first()['score'] >= 3;
        }

        // כמה מועמדים - התאמה בטוחה רק אם המוביל גבוה בבירור מהשני
        $top    = $candidates[0]['score'];
        $second = $candidates[1]['score'];

        return $top >= 5 && $top > $second;
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D/', '', $phone) ?? '';
    }
}
