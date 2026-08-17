<?php

namespace App\Services\Family;

use App\Models\Person;
use App\Models\Relationship;

/**
 * השלמה אוטומטית של קשרי הורות סביב זוגיות.
 *
 * שני הכיוונים שהמשתמשים ציפו להם וחסרו:
 *   1. מוסיפים בן/בת זוג → הוא/היא נהיה אוטומטית הורה של הילדים הקיימים של בן הזוג.
 *   2. מוסיפים ילד או הורה → בן/בת הזוג של אותו הורה נהיה אוטומטית ההורה השני.
 *
 * שמרני בכוונה. ההשלמה קורית רק כשהמצב חד-משמעי:
 *   • לילד יש פחות משני הורים רשומים (אחרת לא נוגעים בכלום)
 *   • להורה הקיים יש בדיוק בן/בת זוג אחד — בנישואים שניים אי אפשר לדעת
 *     מאיזה זוגיות הילדים, אז עדיף לא לנחש
 *
 * כל שיטה מחזירה את שמות הילדים שקיבלו הורה חדש, כדי להציג הודעה למשתמש —
 * השלמה שקטה שאי אפשר לראות היא בדיוק מה שהופך תיקון טעות לעבודה מיותרת.
 */
class RelationshipSync
{
    /**
     * אחרי יצירת קשר זוגיות בין $a ל-$b: כל אחד מהם מאמץ את הילדים
     * הקיימים של השני, כשזה חד-משמעי.
     *
     * @return string[] תיאורי הקשרים שנוצרו
     */
    public function completeParenthoodForCouple(Person $a, Person $b): array
    {
        return array_merge(
            $this->linkPartnerToChildrenOf($a, $b),
            $this->linkPartnerToChildrenOf($b, $a),
        );
    }

    /**
     * אחרי שנוספה הורות של $parent לילד $child: אם ל-$parent יש בדיוק
     * בן/בת זוג אחד, גם הוא נרשם כהורה — כך שילד לא נשאר עם הורה יחיד בטעות.
     *
     * @return string[] תיאורי הקשרים שנוצרו
     */
    public function completeSecondParent(Person $child, Person $parent): array
    {
        $spouse = $this->soleSpouseOf($parent);

        if (! $spouse || ! $this->canAdoptChild($spouse, $child)) {
            return [];
        }

        return [$this->linkParentToChild($spouse, $child)];
    }

    /**
     * הילדים של $parent שאפשר לצרף אליהם את $partner כהורה נוסף.
     *
     * @return string[]
     */
    private function linkPartnerToChildrenOf(Person $parent, Person $partner): array
    {
        // נישואים שניים — לא ברור מאיזו זוגיות כל ילד, ולכן לא נוגעים
        if ($this->soleSpouseOf($parent)?->id !== $partner->id) {
            return [];
        }

        $created = [];

        foreach ($parent->children()->get() as $child) {
            if ($this->canAdoptChild($partner, $child)) {
                $created[] = $this->linkParentToChild($partner, $child);
            }
        }

        return $created;
    }

    /** בן/בת הזוג היחיד/ה של הדמות — או null אם אין, או אם יש יותר מאחד */
    private function soleSpouseOf(Person $person): ?Person
    {
        $spouses = $person->spouses()->get();

        return $spouses->count() === 1 ? $spouses->first() : null;
    }

    /** האם מותר לרשום את $parent כהורה של $child */
    private function canAdoptChild(Person $parent, Person $child): bool
    {
        if ($parent->id === $child->id) {
            return false;
        }

        // ילד ששויך ידנית להורים מסוימים (is_explicit) — בדיוק אותו סימון שמונע
        // את מיזוג בני-הזוג בתצוגת העץ — נשאר כפי שהוגדר, בלי השלמה אוטומטית
        $links = Relationship::where('person2_id', $child->id)
            ->where('type', 'parent_child')
            ->get();

        if ($links->contains(fn ($r) => (bool) $r->is_explicit)) {
            return false;
        }

        return $links->count() < 2 && ! $links->contains(fn ($r) => $r->person1_id === $parent->id);
    }

    private function linkParentToChild(Person $parent, Person $child): string
    {
        Relationship::firstOrCreate([
            'person1_id' => $parent->id,
            'person2_id' => $child->id,
            'type'       => 'parent_child',
        ]);

        return "{$parent->full_name} → {$child->full_name}";
    }

    /** הודעה קצרה למשתמש על מה שהושלם אוטומטית, או null כשלא נוצר כלום */
    public static function summarize(array $created): ?string
    {
        if (! $created) {
            return null;
        }

        return count($created) === 1
            ? "נוסף גם קשר הורות: {$created[0]}"
            : 'נוספו גם ' . count($created) . ' קשרי הורות: ' . implode(', ', $created);
    }
}
