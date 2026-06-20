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
     * סינון ימי הולדת/נישואין לפי החודש העברי מתבצע בצד הלקוח (hebcal),
     * כי התאריך העברי הוא השפה המרכזית במשפחה.
     */
    public function index()
    {
        $people = Person::all();

        return Inertia::render('Stats/Index', [
            'stats' => [
                'total'    => $people->count(),
                'living'   => $people->where('is_deceased', false)->count(),
                'deceased' => $people->where('is_deceased', true)->count(),
                'males'    => $people->where('gender', 'male')->count(),
                'females'  => $people->where('gender', 'female')->count(),
                'photos'   => $people->whereNotNull('profile_photo')->count() + FamilyPhoto::count(),
            ],
            'cities'                => $this->cities($people),
            'babies'                => $this->recentBabies($people),
            'birthdayCandidates'    => $this->birthdayCandidates($people),
            'anniversaryCandidates' => $this->anniversaryCandidates(),
        ]);
    }

    /** ערים שונות שבהן גרים בני המשפחה, עם מספר ושמות לכל עיר. */
    private function cities($people): array
    {
        return $people
            ->filter(fn($p) => filled($p->city))
            ->groupBy('city')
            ->map(fn($group, $city) => [
                'city'   => $city,
                'count'  => $group->count(),
                'people' => $group->map(fn($p) => $p->full_name)->values()->all(),
            ])
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
                'id'        => $p->id,
                'name'      => $p->first_name,
                'gender'    => $p->gender,
                'iso'       => $p->birth_date_gregorian->format('Y-m-d'),
                'greg'      => $p->birth_date_gregorian->format('d/m/Y'),
                'chain'     => $this->maternalChain($p),
                'photo_url' => $p->profile_photo_url,
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

            $parent = $parents->firstWhere('gender', 'female') ?? $parents->first();
            $chain[] = $parent->first_name;
            $current = $parent;
        }

        return $chain;
    }

    /** מועמדים לימי הולדת — כל החיים עם תאריך לידה. הסינון לחודש העברי בלקוח. */
    private function birthdayCandidates($people): array
    {
        return $people
            ->filter(fn($p) => ! $p->is_deceased && $p->birth_date_gregorian)
            ->map(fn($p) => [
                'id'        => $p->id,
                'full_name' => $p->full_name,
                'gender'    => $p->gender,
                'iso'       => $p->birth_date_gregorian->format('Y-m-d'),
                'greg'      => $p->birth_date_gregorian->format('d/m'),
                'photo_url' => $p->profile_photo_url,
            ])
            ->values()
            ->all();
    }

    /** מועמדים לימי נישואין — כל זוג עם תאריך חתונה. הסינון לחודש העברי בלקוח. */
    private function anniversaryCandidates(): array
    {
        return Relationship::where('type', 'spouse')
            ->whereNotNull('marriage_date_gregorian')
            ->with(['person1:id,first_name', 'person2:id,first_name'])
            ->get()
            ->map(function ($r) {
                if (! $r->person1 || ! $r->person2) return null;
                return [
                    'couple' => $r->person1->first_name . ' ו' . $r->person2->first_name,
                    'iso'    => Carbon::parse($r->marriage_date_gregorian)->format('Y-m-d'),
                    'greg'   => Carbon::parse($r->marriage_date_gregorian)->format('d/m'),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
