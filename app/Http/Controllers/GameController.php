<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\Relationship;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;

class GameController extends Controller
{
    public function index()
    {
        $main = Person::where('is_main_person', true)->first();

        $allPeople = Person::select('id', 'first_name', 'last_name', 'gender', 'profile_photo')
            ->orderBy('first_name')
            ->get()
            ->map(fn($p) => [
                'id'        => $p->id,
                'full_name' => $p->full_name,
                'gender'    => $p->gender,
                'photo_url' => $p->profile_photo_url,
            ]);

        return Inertia::render('Game', [
            'mainPerson' => $main ? [
                'id'        => $main->id,
                'full_name' => $main->full_name,
                'photo_url' => $main->profile_photo_url,
            ] : null,
            'allPeople' => $allPeople,
        ]);
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

        // Distractor pool: everyone except the target and the people on the path.
        $onPath  = array_flip($path);
        $genderOf = Person::pluck('gender', 'id');
        $poolAll = array_values(array_filter(
            $genderOf->keys()->all(),
            fn($id) => $id !== $target && ! isset($onPath[$id])
        ));

        // Build per-step data: correct answer + 3 distractors + relation label.
        $steps = [];
        foreach ($path as $i => $correctId) {
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
                'options'    => array_values($options),
                'label'      => $this->relationLabel($i),
            ];
        }

        return response()->json([
            'target_id' => $target,
            'main_id'   => $main->id,
            'steps'     => $steps,
        ]);
    }

    private function relationLabel(int $depth): string
    {
        if ($depth === 0) return 'ההורה';
        if ($depth === 1) return 'הסבא/סבתא';
        $greats = $depth - 1;          // depth 2 → "רבא", depth 3 → "רבא-רבא"...
        return 'הסבא/סבתא ' . implode('-', array_fill(0, $greats, 'רבא'));
    }
}
