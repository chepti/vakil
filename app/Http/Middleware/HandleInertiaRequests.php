<?php

namespace App\Http\Middleware;

use App\Models\Person;
use App\Models\Relationship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'success'        => fn() => $request->session()->get('success'),
                'error'          => fn() => $request->session()->get('error'),
                'digest_success' => fn() => $request->session()->get('digest_success'),
            ],
            // הכפתור "התחבר עם Google" יופיע רק כשהוגדרו credentials ב-.env
            'googleEnabled' => filled(config('services.google.client_id')),
            // מצב אתר-הדגמה — באנר "הכל בדוי"
            'demo'          => (bool) config('app.demo'),
            // שם המשפחה בכל הכותרות — נקבע ב-APP_NAME של כל אתר (ואקיל / בן ארצי-כוגן / הדגמה)
            'siteName'      => config('app.name', 'משפחת ואקיל'),
            // התאמות תצוגת תאריכים לאתר הנוכחי (ראו config/app.php → dates)
            'dateDisplay'   => [
                'hideGregorian'    => (bool) config('app.dates.hide_gregorian'),
                'hideBirthYearIds' => $this->hiddenBirthYearIds(),
            ],
        ];
    }

    /**
     * מזהי הדמויות שלא מציגים להן את שנת הלידה — נשים עם קשר זוגיות.
     * מחושב פעם אחת ונשמר במטמון לדקה: זו שאילתה קטנה שרצה בכל בקשה.
     *
     * @return int[]
     */
    private function hiddenBirthYearIds(): array
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
}
