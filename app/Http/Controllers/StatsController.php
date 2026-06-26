<?php

namespace App\Http\Controllers;

use App\Models\CityLocation;
use App\Models\FamilyPhoto;
use App\Models\Person;
use App\Models\Relationship;
use Carbon\Carbon;
use Illuminate\Http\Request;
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
            'savedLocations'        => CityLocation::all()
                ->mapWithKeys(fn($l) => [$l->name => ['lat' => $l->lat, 'lng' => $l->lng]]),
            'babies'                => $this->recentBabies($people),
            'birthdayCandidates'    => $this->birthdayCandidates($people),
            'anniversaryCandidates' => $this->anniversaryCandidates(),
            'agePyramid'            => $this->agePyramid($people),
        ]);
    }

    /** שמירת מיקום עיר על המפה — כל משתמש יכול, נשמר לכולם. */
    public function saveLocation(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'lat'  => 'required|numeric|between:-90,90',
            'lng'  => 'required|numeric|between:-180,180',
        ]);

        CityLocation::updateOrCreate(
            ['name' => $data['name']],
            ['lat' => $data['lat'], 'lng' => $data['lng'], 'created_by' => auth()->id()]
        );

        return response()->json(['ok' => true]);
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
                'context'   => $p->ancestralContext(3),
                'photo_url' => $p->profile_photo_url,
            ])
            ->values()
            ->all();
    }

    /** מועמדים לימי הולדת — כל החיים עם תאריך לידה. הסינון לחודש העברי בלקוח. */
    private function birthdayCandidates($people): array
    {
        return $people
            ->filter(fn($p) => ! $p->is_deceased && $p->birth_date_gregorian)
            ->map(fn($p) => [
                'id'        => $p->id,
                'name'      => $p->first_name,
                'full_name' => $p->full_name,
                'gender'    => $p->gender,
                'iso'       => $p->birth_date_gregorian->format('Y-m-d'),
                'greg'      => $p->birth_date_gregorian->format('d/m'),
                'context'   => $p->ancestralContext(3),
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

    /**
     * פירמידת גילאים — קבוצות של 5 שנים, מחולק לבנים ובנות (אחוז מסך הנתונים).
     */
    private function agePyramid($people): array
    {
        $living = $people->filter(fn ($p) => ! $p->is_deceased && $p->birth_date_gregorian);
        $total  = $living->count();

        if ($total === 0) {
            return ['brackets' => [], 'total' => 0, 'maxCount' => 0];
        }

        $counts = [];
        $today  = Carbon::today();

        foreach ($living as $p) {
            $age    = (int) $p->birth_date_gregorian->diffInYears($today);
            $label  = $age >= 100 ? '100+' : $this->ageBracketLabel($age);
            $gender = $p->gender === 'female' ? 'female' : 'male';

            if (! isset($counts[$label])) {
                $counts[$label] = ['label' => $label, 'male' => 0, 'female' => 0, 'sort' => $age >= 100 ? 100 : (int) (floor($age / 5) * 5)];
            }
            $counts[$label][$gender]++;
        }

        $brackets = collect($counts)
            ->sortBy('sort')
            ->values()
            ->map(function ($b) use ($total) {
                $malePct   = round($b['male'] / $total * 100, 1);
                $femalePct = round($b['female'] / $total * 100, 1);

                return [
                    'label'     => $b['label'],
                    'male'      => $b['male'],
                    'female'    => $b['female'],
                    'malePct'   => $malePct,
                    'femalePct' => $femalePct,
                ];
            })
            ->reverse()
            ->values()
            ->all();

        $maxCount = collect($brackets)->max(fn ($b) => max($b['male'], $b['female'])) ?: 0;

        return [
            'brackets' => $brackets,
            'total'    => $total,
            'maxCount' => $maxCount,
        ];
    }

    private function ageBracketLabel(int $age): string
    {
        $start = (int) (floor($age / 5) * 5);

        return $start . '-' . ($start + 4);
    }
}
