<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\Relationship;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class FamilyTreeController extends Controller
{
    public function index()
    {
        $nodes = $this->buildTreeData();

        return Inertia::render('FamilyTree', [
            'nodes'       => $nodes,
            'totalPeople' => count($nodes),
        ]);
    }

    // ─── JSON API endpoints (inline tree editing) ────────────────

    public function apiData(): JsonResponse
    {
        return response()->json($this->buildTreeData());
    }

    public function apiSavePerson(Request $request): JsonResponse
    {
        $datum = $request->json()->all();
        $rawId = $datum['id'] ?? '';
        $dbId  = is_numeric($rawId) ? (int) $rawId : 0;
        $existing = $dbId > 0 ? Person::find($dbId) : null;

        if ($existing) {
            // עדכון שדות של דמות קיימת
            $existing->update([
                'first_name'           => $datum['data']['first name']  ?? $existing->first_name,
                'last_name'            => $datum['data']['last name']   ?? $existing->last_name,
                'gender'               => ($datum['data']['gender'] ?? 'M') === 'M' ? 'male' : 'female',
                'birth_date_gregorian' => $datum['data']['birthday']    ?: null,
                'current_occupation'   => $datum['data']['occupation']  ?? $existing->current_occupation,
                'city'                 => $datum['data']['city']        ?? $existing->city,
            ]);
        } else {
            // דמות חדשה — family-chart שלח UUID זמני
            $person = Person::create([
                'first_name'           => $datum['data']['first name']  ?? '',
                'last_name'            => $datum['data']['last name']   ?? '',
                'gender'               => ($datum['data']['gender'] ?? 'M') === 'M' ? 'male' : 'female',
                'birth_date_gregorian' => $datum['data']['birthday']    ?: null,
                'current_occupation'   => $datum['data']['occupation']  ?? null,
                'city'                 => $datum['data']['city']        ?? null,
                'created_by'           => Auth::id(),
            ]);

            // קשרי משפחה מתוך rels (IDs הם DB IDs אמיתיים של דמויות קיימות)
            foreach ($datum['rels']['parents'] ?? [] as $pid) {
                if (is_numeric($pid) && (int)$pid > 0) {
                    Relationship::firstOrCreate([
                        'person1_id' => (int)$pid,
                        'person2_id' => $person->id,
                        'type'       => 'parent_child',
                    ]);
                }
            }
            foreach ($datum['rels']['spouses'] ?? [] as $sid) {
                if (is_numeric($sid) && (int)$sid > 0) {
                    Relationship::firstOrCreate([
                        'person1_id' => min($person->id, (int)$sid),
                        'person2_id' => max($person->id, (int)$sid),
                        'type'       => 'spouse',
                    ]);
                }
            }
            foreach ($datum['rels']['children'] ?? [] as $cid) {
                if (is_numeric($cid) && (int)$cid > 0) {
                    Relationship::firstOrCreate([
                        'person1_id' => $person->id,
                        'person2_id' => (int)$cid,
                        'type'       => 'parent_child',
                    ]);
                }
            }
        }

        return response()->json($this->buildTreeData());
    }

    public function apiDeletePerson(int $id): JsonResponse
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $person = Person::findOrFail($id);
        Relationship::where('person1_id', $id)->orWhere('person2_id', $id)->delete();
        if ($person->profile_photo) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($person->profile_photo);
        }
        $person->delete();
        return response()->json($this->buildTreeData());
    }

    // ─────────────────────────────────────────────────────────────

    /**
     * ממיר את ה-DB לפורמט של family-chart:
     * { id, data: { gender, first name, last name, birthday, avatar }, rels: { parents, spouses, children } }
     */
    private function buildTreeData(): array
    {
        $people = Person::select(
            'id', 'first_name', 'last_name', 'gender',
            'birth_date_gregorian', 'birth_date_hebrew',
            'death_date_gregorian', 'is_deceased',
            'current_occupation', 'city', 'profile_photo'
        )->get();

        $relationships = Relationship::all();

        // בנה אינדקסים מהירים
        $children = [];   // parent_id → [child_id, ...]
        $parents  = [];   // child_id  → [parent_id, ...]
        $spouses  = [];   // person_id → [spouse_id, ...]

        foreach ($relationships as $rel) {
            if ($rel->type === 'parent_child') {
                $children[$rel->person1_id][] = (string) $rel->person2_id;
                $parents[$rel->person2_id][]  = (string) $rel->person1_id;
            } elseif ($rel->type === 'spouse') {
                $spouses[$rel->person1_id][] = (string) $rel->person2_id;
                $spouses[$rel->person2_id][] = (string) $rel->person1_id;
            }
        }

        // הנחה: בני זוג תמיד הורים משותפים של כל ילדיהם.
        // pass שני — מאחד ילדים של שני בני הזוג ומוודא שכל ילד מקושר לשניהם.
        foreach ($spouses as $pid => $mySpouseIds) {
            foreach ($mySpouseIds as $spouseId) {
                $sid = (int) $spouseId;
                if ($pid >= $sid) continue; // מעבד כל זוג פעם אחת בלבד

                $allChildren = array_values(array_unique(array_merge(
                    $children[$pid] ?? [],
                    $children[$sid] ?? []
                )));

                $children[$pid] = $allChildren;
                $children[$sid] = $allChildren;

                foreach ($allChildren as $childId) {
                    $cid = (int) $childId;
                    $parents[$cid] = array_values(array_unique(array_merge(
                        $parents[$cid] ?? [],
                        [(string) $pid, (string) $sid]
                    )));
                }
            }
        }

        return $people->map(function ($p) use ($children, $parents, $spouses) {
            $id = (string) $p->id;
            return [
                'id'   => $id,
                'data' => [
                    'gender'      => $p->gender === 'male' ? 'M' : 'F',
                    'first name'  => $p->first_name,
                    'last name'   => $p->last_name,
                    'birthday'    => $p->birth_date_gregorian?->format('Y-m-d'),
                    'birthday_he' => $p->birth_date_hebrew,
                    'death_date'  => $p->death_date_gregorian?->format('Y-m-d'),
                    'is_deceased' => $p->is_deceased,
                    'occupation'  => $p->current_occupation,
                    'city'        => $p->city,
                    'avatar'      => $p->profile_photo
                        ? asset('storage/' . $p->profile_photo)
                        : null,
                ],
                'rels' => [
                    'parents'  => $parents[$p->id]  ?? [],
                    'spouses'  => $spouses[$p->id]  ?? [],
                    'children' => $children[$p->id] ?? [],
                ],
            ];
        })->values()->toArray();
    }
}
