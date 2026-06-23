<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Person;
use App\Models\Relationship;
use App\Support\HebrewDate;
use Carbon\Carbon;

/**
 * בונה את תוכן המייל החודשי (ראש חודש):
 *   - מי נולד בחודש העברי שחלף (תינוקות)
 *   - אירועים בחודש העברי הקרוב, עם קישור לעמוד האירוע
 *   - ימי הולדת/נישואין "עגולים" (כפולה של 10 שנים עבריות) שחלים בחודש הקרוב
 */
class DigestBuilder
{
    /**
     * @return array{
     *   monthName:string, yearGematria:string,
     *   newBabies:array, events:array, roundBirthdays:array, roundAnniversaries:array,
     *   isEmpty:bool
     * }
     */
    public function build(Carbon $when): array
    {
        $parts = HebrewDate::parts($when);

        // החודש העברי הקרוב (שמתחיל היום, בראש חודש) והחודש שחלף
        [$monthStart, $monthEnd] = HebrewDate::monthRange($when);
        [$prevStart, $prevEnd]   = HebrewDate::monthRange($monthStart->copy()->subDay());

        $currentMonthNorm = HebrewDate::normalizeAdar($parts['month']);
        $currentHebrewYear = $parts['year'];

        $newBabies          = $this->newBabies($prevStart, $prevEnd);
        $events             = $this->upcomingEvents($monthStart, $monthEnd);
        $roundBirthdays     = $this->roundBirthdays($currentMonthNorm, $currentHebrewYear);
        $roundAnniversaries = $this->roundAnniversaries($currentMonthNorm, $currentHebrewYear);

        return [
            'monthName'          => $parts['monthName'],
            'yearGematria'       => $parts['yearGematria'],
            'newBabies'          => $newBabies,
            'events'             => $events,
            'roundBirthdays'     => $roundBirthdays,
            'roundAnniversaries' => $roundAnniversaries,
            'isEmpty'            => ! $newBabies && ! $events && ! $roundBirthdays && ! $roundAnniversaries,
        ];
    }

    /**
     * סעיף "ענף ברזולוציה גבוהה" למשתמש שבחר דמות:
     * כל ימי ההולדת והנישואין (בכל גיל, לא רק עשורים) של הצאצאים מתחת לדמות,
     * שחלים בחודש העברי הקרוב.
     *
     * @return array{rootName:string, birthdays:array, anniversaries:array}
     */
    public function branchSection(Person $root, Carbon $when): array
    {
        $parts = HebrewDate::parts($when);
        $currentMonthNorm  = HebrewDate::normalizeAdar($parts['month']);
        $currentHebrewYear = $parts['year'];

        $ids = $root->descendantIds();

        if (empty($ids)) {
            return ['rootName' => $root->full_name, 'birthdays' => [], 'anniversaries' => []];
        }

        $birthdays = [];
        Person::query()
            ->whereIn('id', $ids)
            ->where('is_deceased', false)
            ->whereNotNull('birth_date_gregorian')
            ->get()
            ->each(function (Person $p) use (&$birthdays, $currentMonthNorm, $currentHebrewYear) {
                $b = HebrewDate::parts(Carbon::parse($p->birth_date_gregorian));
                if (HebrewDate::normalizeAdar($b['month']) !== $currentMonthNorm) {
                    return;
                }
                $age = $currentHebrewYear - $b['year'];
                if ($age < 1) {
                    return;
                }
                $birthdays[] = [
                    'name'     => $p->full_name,
                    'age'      => $age,
                    'dayMonth' => HebrewDate::dayMonth(Carbon::parse($p->birth_date_gregorian)),
                    'dateGreg' => Carbon::parse($p->birth_date_gregorian)->format('d/m'),
                    'day'      => $b['day'],
                    'url'      => route('people.show', $p->id),
                    'photoUrl' => $p->profile_photo_url,
                    'context'  => $p->ancestralContext(),
                ];
            });

        $anniversaries = [];
        Relationship::query()
            ->where('type', 'spouse')
            ->where('is_former', false)
            ->whereNotNull('marriage_date_gregorian')
            ->where(fn ($q) => $q->whereIn('person1_id', $ids)->orWhereIn('person2_id', $ids))
            ->get()
            ->each(function (Relationship $r) use (&$anniversaries, $currentMonthNorm, $currentHebrewYear) {
                $m = HebrewDate::parts(Carbon::parse($r->marriage_date_gregorian));
                if (HebrewDate::normalizeAdar($m['month']) !== $currentMonthNorm) {
                    return;
                }
                $years = $currentHebrewYear - $m['year'];
                if ($years < 1) {
                    return;
                }
                $p1 = Person::find($r->person1_id);
                $p2 = Person::find($r->person2_id);
                if (! $p1 || ! $p2 || $p1->is_deceased || $p2->is_deceased) {
                    return;
                }
                $anniversaries[] = [
                    'names'    => "{$p1->full_name} ו{$p2->full_name}",
                    'years'    => $years,
                    'dayMonth' => HebrewDate::dayMonth(Carbon::parse($r->marriage_date_gregorian)),
                    'dateGreg' => Carbon::parse($r->marriage_date_gregorian)->format('d/m'),
                    'day'      => $m['day'],
                    'url'      => route('people.show', $p1->id),
                ];
            });

        // מיון לפי יום בחודש
        usort($birthdays, fn ($a, $b) => $a['day'] <=> $b['day']);
        usort($anniversaries, fn ($a, $b) => $a['day'] <=> $b['day']);

        return [
            'rootName'      => $root->full_name,
            'birthdays'     => $birthdays,
            'anniversaries' => $anniversaries,
        ];
    }

    /** תינוקות שנולדו בחודש העברי שחלף */
    private function newBabies(Carbon $from, Carbon $to): array
    {
        return Person::query()
            ->where('is_deceased', false)
            ->whereNotNull('birth_date_gregorian')
            ->whereBetween('birth_date_gregorian', [$from->toDateString(), $to->toDateString()])
            ->orderBy('birth_date_gregorian')
            ->get()
            ->map(fn (Person $p) => [
                'name'        => $p->full_name,
                'url'         => route('people.show', $p->id),
                'hebrewBirth' => HebrewDate::format(Carbon::parse($p->birth_date_gregorian)),
                'dateGreg'    => Carbon::parse($p->birth_date_gregorian)->format('d/m/Y'),
                'photoUrl'    => $p->profile_photo_url,
                'context'     => $p->ancestralContext(),
                'gender'      => $p->gender,
            ])
            ->all();
    }

    /** אירועים בטווח החודש העברי הקרוב */
    private function upcomingEvents(Carbon $from, Carbon $to): array
    {
        return Event::query()
            ->with('person')
            ->whereNotNull('event_date')
            ->whereBetween('event_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('event_date')
            ->orderBy('event_time')
            ->get()
            ->map(fn (Event $e) => [
                'title'            => $e->title ?: $this->eventTypeLabel($e->type),
                'type'             => $e->type,
                'typeLabel'        => $this->eventTypeLabel($e->type),
                'date'             => Carbon::parse($e->event_date)->format('d/m/Y'),
                'hebrewDate'       => $e->hebrew_date ?: HebrewDate::format(Carbon::parse($e->event_date)),
                'location'         => $e->location,
                'personName'       => $e->person?->full_name,
                'url'              => route('events.show', $e->id),
                'invitationImgUrl' => $e->invitation_image_url,
                'personPhotoUrl'   => $e->person?->profile_photo_url,
            ])
            ->all();
    }

    /** ימי הולדת עגולים (כפולה של 10 שנים עבריות) שחלים בחודש העברי הקרוב */
    private function roundBirthdays(int $currentMonthNorm, int $currentHebrewYear): array
    {
        $out = [];

        Person::query()
            ->where('is_deceased', false)
            ->whereNotNull('birth_date_gregorian')
            ->orderBy('birth_date_gregorian')
            ->get()
            ->each(function (Person $p) use (&$out, $currentMonthNorm, $currentHebrewYear) {
                $b = HebrewDate::parts(Carbon::parse($p->birth_date_gregorian));
                if (HebrewDate::normalizeAdar($b['month']) !== $currentMonthNorm) {
                    return;
                }
                $age = $currentHebrewYear - $b['year'];
                if ($age <= 0 || $age % 10 !== 0) {
                    return;
                }
                $out[] = [
                    'name'     => $p->full_name,
                    'age'      => $age,
                    'dayMonth' => HebrewDate::dayMonth(Carbon::parse($p->birth_date_gregorian)),
                    'dateGreg' => Carbon::parse($p->birth_date_gregorian)->format('d/m'),
                    'url'      => route('people.show', $p->id),
                    'photoUrl' => $p->profile_photo_url,
                    'context'  => $p->ancestralContext(),
                ];
            });

        return $out;
    }

    /** ימי נישואין עגולים (כפולה של 10 שנים עבריות) שחלים בחודש העברי הקרוב */
    private function roundAnniversaries(int $currentMonthNorm, int $currentHebrewYear): array
    {
        $out = [];

        Relationship::query()
            ->where('type', 'spouse')
            ->where('is_former', false)
            ->whereNotNull('marriage_date_gregorian')
            ->get()
            ->each(function (Relationship $r) use (&$out, $currentMonthNorm, $currentHebrewYear) {
                $m = HebrewDate::parts(Carbon::parse($r->marriage_date_gregorian));
                if (HebrewDate::normalizeAdar($m['month']) !== $currentMonthNorm) {
                    return;
                }
                $years = $currentHebrewYear - $m['year'];
                if ($years <= 0 || $years % 10 !== 0) {
                    return;
                }

                $p1 = Person::find($r->person1_id);
                $p2 = Person::find($r->person2_id);
                if (! $p1 || ! $p2 || $p1->is_deceased || $p2->is_deceased) {
                    return;
                }

                $out[] = [
                    'names'    => "{$p1->full_name} ו{$p2->full_name}",
                    'years'    => $years,
                    'dayMonth' => HebrewDate::dayMonth(Carbon::parse($r->marriage_date_gregorian)),
                    'dateGreg' => Carbon::parse($r->marriage_date_gregorian)->format('d/m'),
                    'url'      => route('people.show', $p1->id),
                ];
            });

        return $out;
    }

    private function eventTypeLabel(?string $type): string
    {
        return [
            'birth'       => 'הולדת',
            'bar_mitzvah' => 'בר מצווה',
            'bat_mitzvah' => 'בת מצווה',
            'wedding'     => 'חתונה',
            'death'       => 'אזכרה',
            'other'       => 'אירוע',
        ][$type] ?? 'אירוע';
    }
}
