<?php

namespace App\Http\Controllers;

use App\Models\GameResult;
use App\Models\GameStat;
use App\Models\Person;
use App\Models\Relationship;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class GameController extends Controller
{
    public function index()
    {
        $main = Person::where('is_main_person', true)->first();

        $allPeople = Person::select('id', 'first_name', 'last_name', 'gender', 'profile_photo')
            ->orderBy('first_name')
            ->get();

        // תמונת פרופיל היא לרוב חיתוך של תמונה גדולה יותר — כדי שההגדלה בלייטבוקס תהיה
        // הגדלה של ממש (ולא מתיחה של תמונה זעירה), מאתרים לכל דמות את התמונה המקורית
        // (לפני החיתוך) דרך רשומת Photo המתאימה, בשליפה אחת ולא N+1.
        $originalByThumb = \App\Models\Photo::whereIn('thumb_path', $allPeople->pluck('profile_photo')->filter())
            ->get()->keyBy('thumb_path');

        $peopleData = $allPeople->map(fn($p) => [
            'id'                 => $p->id,
            'full_name'          => $p->full_name,
            'gender'             => $p->gender,
            'photo_url'          => $p->profile_photo_url,
            'original_photo_url' => $p->profile_photo
                ? ($originalByThumb[$p->profile_photo]->original_url ?? $p->profile_photo_url)
                : null,
        ]);

        return Inertia::render('Game', [
            'mainPerson' => $main ? [
                'id'                 => $main->id,
                'full_name'          => $main->full_name,
                'photo_url'          => $main->profile_photo_url,
                'original_photo_url' => $main->profile_photo
                    ? ($originalByThumb[$main->profile_photo]->original_url ?? $main->profile_photo_url)
                    : null,
            ] : null,
            'allPeople' => $peopleData,
            'myStats'   => $this->myStats(Auth::id()),
        ]);
    }

    /**
     * הניקוד האישי המצטבר של המשתמש + את מי כבר זיהה.
     * מחושב מכל סבבי המשחק שהושלמו (game_results), לא מאיפוס בכל טעינת עמוד.
     */
    private function myStats(?int $userId): array
    {
        $empty = ['total_points' => 0, 'rounds' => 0, 'people_count' => 0, 'best_round' => 0, 'full_ids' => 0, 'people' => []];
        if (! $userId) return $empty;

        $results = GameResult::where('user_id', $userId)->get();
        if ($results->isEmpty()) return $empty;

        $byPerson = $results->filter(fn($r) => $r->person_id)->groupBy('person_id');
        $names = Person::whereIn('id', $byPerson->keys())
            ->get(['id', 'first_name', 'last_name', 'profile_photo'])
            ->keyBy('id');

        $people = $byPerson->map(function ($rows, $personId) use ($names) {
            $p = $names[$personId] ?? null;
            return [
                'id'         => (int) $personId,
                'name'       => $p?->full_name ?? 'דמות שנמחקה',
                'photo_url'  => $p?->profile_photo_url,
                'points'     => (int) $rows->sum('points'),
                'times'      => $rows->count(),
                // "זיהוי מלא" — סבב שבו זוהו גם השם הפרטי וגם שם המשפחה
                'full_id'    => $rows->contains(fn($r) => $r->first_name_ok && $r->last_name_ok),
                'links_ok'   => (int) $rows->max('links_ok'),
                'links_total'=> (int) $rows->max('links_total'),
            ];
        })->sortByDesc('points')->values()->all();

        return [
            'total_points'  => (int) $results->sum('points'),
            'rounds'        => $results->count(),
            'people_count'  => count($people),
            'best_round'    => (int) $results->max('points'),
            'full_ids'      => count(array_filter($people, fn($p) => $p['full_id'])),
            'people'        => $people,
        ];
    }

    /**
     * Build a fresh round: a random descendant (with a photo) and the
     * chain of ancestors leading up to the main person ("Grandma Vakil").
     */
    public function round(): JsonResponse
    {
        $main = Person::where('is_main_person', true)->first();
        if (! $main) {
            return response()->json(['error' => 'no_main_person'], 422);
        }

        // Build parent/child maps from the relationship graph.
        $rels = Relationship::where('type', 'parent_child')->get();
        $parentsOf  = [];   // child_id  => [parent_id, ...]
        $childrenOf = [];   // parent_id => [child_id, ...]
        foreach ($rels as $r) {
            $parentsOf[$r->person2_id][]  = $r->person1_id;
            $childrenOf[$r->person1_id][] = $r->person2_id;
        }

        // Spouse map (used for relational hints).
        $spouseOf = [];   // person_id => [spouse_id, ...]
        foreach (Relationship::where('type', 'spouse')->get() as $r) {
            $spouseOf[$r->person1_id][] = $r->person2_id;
            $spouseOf[$r->person2_id][] = $r->person1_id;
        }

        // BFS down from main → all blood descendants.
        $descendants = [];
        $queue = [$main->id];
        while ($queue) {
            $cur = array_shift($queue);
            foreach ($childrenOf[$cur] ?? [] as $c) {
                if (! isset($descendants[$c])) {
                    $descendants[$c] = true;
                    $queue[] = $c;
                }
            }
        }
        $bloodline = $descendants;
        $bloodline[$main->id] = true;

        // Photos by id (used to require a visible target + to compute path).
        $photoOf = Person::whereNotNull('profile_photo')
            ->pluck('profile_photo', 'id');

        // Eligible targets: blood descendants that have a photo.
        $eligible = array_values(array_filter(
            array_keys($descendants),
            fn($id) => isset($photoOf[$id])
        ));

        if (empty($eligible)) {
            return response()->json(['error' => 'no_eligible_people'], 422);
        }

        // Compute the path to main for a candidate; prefer deeper chains (more fun).
        $buildPath = function (int $targetId) use ($parentsOf, $bloodline, $main): array {
            $path  = [];
            $cur   = $targetId;
            $guard = 0;
            while ($cur !== $main->id && $guard++ < 50) {
                $next = null;
                foreach ($parentsOf[$cur] ?? [] as $p) {
                    if (isset($bloodline[$p])) { $next = $p; break; }
                }
                if ($next === null) return [];   // no clean path
                $path[] = $next;
                $cur = $next;
            }
            return $path;
        };

        // Try a handful of random candidates, keep the first with a decent depth.
        shuffle($eligible);
        $target = null;
        $path   = [];
        foreach ($eligible as $candidateId) {
            $p = $buildPath($candidateId);
            if (empty($p)) continue;
            $target = $candidateId;
            $path   = $p;
            if (count($p) >= 2) break;   // prefer depth >= 2
        }

        if ($target === null || empty($path)) {
            return response()->json(['error' => 'no_path'], 422);
        }

        // People lookup (names + gender) for distractors, hints and labels.
        $people  = Person::select('id', 'first_name', 'last_name', 'gender')->get()->keyBy('id');
        $genderOf = $people->map(fn($p) => $p->gender);

        // Distractor pool: everyone except the target and the people on the path.
        $onPath  = array_flip($path);
        $poolAll = array_values(array_filter(
            $people->keys()->all(),
            fn($id) => $id !== $target && ! isset($onPath[$id])
        ));

        // Build per-step data. The "child" of step i is the target (i=0) or the
        // previous blood-line ancestor; we expose BOTH of the child's parents so a
        // married-in parent guess is accepted (not an error). Hints (below) describe
        // the mystery TARGET, not the answer.
        $steps = [];
        foreach ($path as $i => $correctId) {
            $childId = $i === 0 ? $target : $path[$i - 1];

            // Distractors of the same gender when possible.
            $sameGender = array_values(array_filter(
                $poolAll,
                fn($id) => ($genderOf[$id] ?? null) === ($genderOf[$correctId] ?? null)
            ));
            $pick = count($sameGender) >= 3 ? $sameGender : $poolAll;
            shuffle($pick);
            $distractors = array_slice($pick, 0, 3);

            $options = $distractors;
            $options[] = $correctId;
            shuffle($options);

            $steps[] = [
                'correct_id' => $correctId,
                'parent_ids' => array_values(array_unique($parentsOf[$childId] ?? [])),
                'options'    => array_values($options),
                'label'      => $this->relationLabel($i),
            ];
        }

        // Hints reveal the identity of the mystery starting figure (the target).
        $targetPerson = $people[$target] ?? null;

        return response()->json([
            'target_id'         => $target,
            'main_id'           => $main->id,
            'steps'             => $steps,
            'target_first_name' => $targetPerson?->first_name,
            'target_last_name'  => $targetPerson?->last_name,
            'target_hint'       => $this->targetHint($target, $parentsOf, $childrenOf, $spouseOf, $onPath, $people),
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    /**
     * רישום סבב שהושלם — נשמר גם כתוצאה אישית של המשתמש (ניקוד מצטבר + מי זוהה),
     * וגם נצבר לדמות המטרה עצמה (GameStat) לתצוגת הניקוד על העץ.
     */
    public function finish(Request $request): JsonResponse
    {
        $data = $request->validate([
            'person_id'     => 'required|exists:people,id',
            'points'        => 'required|integer|min:0|max:5000',
            'first_name_ok' => 'nullable|boolean',
            'last_name_ok'  => 'nullable|boolean',
            'links_total'   => 'nullable|integer|min:0|max:50',
            'links_ok'      => 'nullable|integer|min:0|max:50',
            'hints_used'    => 'nullable|integer|min:0|max:50',
        ]);

        $stat = GameStat::firstOrCreate(['person_id' => $data['person_id']]);
        $stat->increment('correct_guesses');
        $stat->increment('points', $data['points']);

        if ($userId = Auth::id()) {
            GameResult::create([
                'user_id'       => $userId,
                'person_id'     => $data['person_id'],
                'points'        => $data['points'],
                'first_name_ok' => (bool) ($data['first_name_ok'] ?? false),
                'last_name_ok'  => (bool) ($data['last_name_ok'] ?? false),
                'links_total'   => $data['links_total'] ?? 0,
                'links_ok'      => $data['links_ok'] ?? 0,
                'hints_used'    => $data['hints_used'] ?? 0,
            ]);
        }

        return response()->json(['ok' => true, 'myStats' => $this->myStats(Auth::id())]);
    }

    /**
     * A relational clue that helps identify the mystery TARGET — e.g.
     * "sibling of {X}", "parent of {Y}", or "married to {Z}".
     */
    private function targetHint(int $targetId, array $parentsOf, array $childrenOf, array $spouseOf, array $onPath, $people): ?string
    {
        // A sibling (shares a parent) — strong, recognizable clue.
        foreach ($parentsOf[$targetId] ?? [] as $parentId) {
            foreach ($childrenOf[$parentId] ?? [] as $sib) {
                if ($sib == $targetId) continue;
                $p = $people[$sib] ?? null;
                if ($p) return 'אח/אחות של ' . $p->first_name . ' ' . $p->last_name;
            }
        }
        // A child of the target.
        foreach ($childrenOf[$targetId] ?? [] as $childId) {
            $p = $people[$childId] ?? null;
            if ($p) return 'הורה של ' . $p->first_name . ' ' . $p->last_name;
        }
        // A spouse.
        foreach ($spouseOf[$targetId] ?? [] as $spouseId) {
            $p = $people[$spouseId] ?? null;
            if ($p) return 'נשוי/אה ל' . $p->first_name . ' ' . $p->last_name;
        }
        return null;
    }

    private function relationLabel(int $depth): string
    {
        if ($depth === 0) return 'ההורה';
        if ($depth === 1) return 'הסבא/סבתא';
        $greats = $depth - 1;          // depth 2 → "רבא", depth 3 → "רבא-רבא"...
        return 'הסבא/סבתא ' . implode('-', array_fill(0, $greats, 'רבא'));
    }
}
