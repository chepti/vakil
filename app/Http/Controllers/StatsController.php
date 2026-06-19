<?php

namespace App\Http\Controllers;

use App\Models\FamilyPhoto;
use App\Models\Person;
use App\Models\Relationship;
use Carbon\Carbon;
use Inertia\Inertia;

class StatsController extends Controller
{
    /**
     * דף סטטיסטיקות משפחתי — פתוח לכל המשתמשים.
     */
    public function index()
    {
        $people = Person::all();

        return Inertia::render('Stats/Index', [
            'stats' => [
                'total'      => $people->count(),
                'living'     => $people->where('is_deceased', false)->count(),
                'deceased'   => $people->where('is_deceased', true)->count(),
                'males'      => $people->where('gender', 'male')->count(),
                'females'    => $people->where('gender', 'female')->count(),
                'photos'     => $people->whereNotNull('profile_photo')->count() + FamilyPhoto::count(),
            ],
            'cities'        => $this->cities($people),
            'babies'        => $this->recentBabies($people),
            'birthdays'     => $this->upcomingBirthdays($people),
            'anniversaries' => $this->upcomingAnniversaries(),
        ]);
    }

    /** ערים שונות שבהן גרים בני המשפחה, עם מספר אנשים לכל עיר. */
    private function cities($people): array
    {
        return $people
            ->filter(fn($p) => filled($p->city))
            ->groupBy('city')
            ->map(fn($group, $city) => ['city' => $city, 'count' => $group->count()])
            ->sortByDesc('count')
            ->values()
            ->all();
    }

    /** תינוקות שנולדו ב-12 החודשים האחרונים, עם שרשרת הורים. */
    private function recentBabies($people): array
    {
        $cutoff = Carbon::today()->subYear();

        return $people
            ->filter(fn($p) => $p->birth_date_gregorian && $p->birth_date_gregorian->gte($cutoff))
            ->sortByDesc(fn($p) => $p->birth_date_gregorian->timestamp)
            ->map(fn($p) => [
                'id'         => $p->id,
                'name'       => $p->first_name,
                'full_name'  => $p->full_name,
                'gender'     => $p->gender,
                'birth_date' => $p->birth_date_gregorian->format('d/m/Y'),
                'chain'      => $this->maternalChain($p),
                'photo_url'  => $p->profile_photo_url,
            ])
            ->values()
            ->all();
    }

    /**
     * שרשרת הורים (אימהית כברירת מחדל) עד 2 דורות מעלה.
     * מחזיר רשימת שמות פרטיים, למשל ['רינה', 'ניצה'] עבור "יוסי של רינה של ניצה".
     */
    private function maternalChain(Person $person, int $depth = 2): array
    {
        $chain = [];
        $current = $person;

        for ($i = 0; $i < $depth; $i++) {
            $parents = $current->parents()->get();
            if ($parents->isEmpty()) break;

            // עדיף קו אימהי; אם אין אם — קח את ההורה הראשון
            $parent = $parents->firstWhere('gender', 'female') ?? $parents->first();
            $chain[] = $parent->first_name;
            $current = $parent;
        }

        return $chain;
    }

    /** ימי הולדת ב-30 הימים הקרובים (לחיים בלבד). */
    private function upcomingBirthdays($people): array
    {
        $today = Carbon::today();
        $limit = $today->copy()->addDays(30);

        return $people
            ->filter(fn($p) => ! $p->is_deceased && $p->birth_date_gregorian)
            ->map(function ($p) use ($today) {
                $next = $this->nextOccurrence($p->birth_date_gregorian, $today);
                return [
                    'id'         => $p->id,
                    'full_name'  => $p->full_name,
                    'gender'     => $p->gender,
                    'date'       => $next->format('d/m'),
                    'days_until' => $today->diffInDays($next),
                    'turning'    => $next->year - $p->birth_date_gregorian->year,
                    'photo_url'  => $p->profile_photo_url,
                ];
            })
            ->filter(fn($b) => $this->withinWindow($b['days_until']))
            ->sortBy('days_until')
            ->values()
            ->all();
    }

    /** ימי נישואין ב-30 הימים הקרובים. */
    private function upcomingAnniversaries(): array
    {
        $today = Carbon::today();

        return Relationship::where('type', 'spouse')
            ->whereNotNull('marriage_date_gregorian')
            ->with(['person1', 'person2'])
            ->get()
            ->map(function ($r) use ($today) {
                $date = Carbon::parse($r->marriage_date_gregorian);
                $next = $this->nextOccurrence($date, $today);
                $p1 = $r->person1;
                $p2 = $r->person2;
                if (! $p1 || ! $p2) return null;
                return [
                    'couple'     => $p1->first_name . ' ו' . $p2->first_name,
                    'date'       => $next->format('d/m'),
                    'days_until' => $today->diffInDays($next),
                    'years'      => $next->year - $date->year,
                ];
            })
            ->filter(fn($a) => $a && $this->withinWindow($a['days_until']))
            ->sortBy('days_until')
            ->values()
            ->all();
    }

    /** המופע הבא של תאריך (חודש+יום) מהיום קדימה. */
    private function nextOccurrence(Carbon $date, Carbon $from): Carbon
    {
        $next = Carbon::create($from->year, $date->month, $date->day);
        if ($next->lt($from)) {
            $next->addYear();
        }
        return $next;
    }

    private function withinWindow(int $days): bool
    {
        return $days >= 0 && $days <= 30;
    }
}
