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

        $mainId = Person::where('is_main_person', true)->value('id');
        $defaultMainPersonId = $mainId ? (string) $mainId : ($nodes[0]['id'] ?? null);

        return Inertia::render('FamilyTree', [
            'nodes'               => $nodes,
            'totalPeople'         => count($nodes),
            'isAdmin'             => Auth::user()->role === 'admin',
            'rootPersonId'        => $this->findRootPersonId($nodes),
            'defaultMainPersonId' => $defaultMainPersonId,
        ]);
    }

    public function apiSetMain(int $id): JsonResponse
    {
        if (Auth::user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        Person::where('is_main_person', true)->update(['is_main_person' => false]);
        Person::where('id', $id)->update(['is_main_person' => true]);
        return response()->json(['success' => true, 'main_person_id' => $id]);
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
                'birth_date_gregorian' => ($datum['data']['birthday']   ?? '') ?: null,
                'current_occupation'   => $datum['data']['occupation']  ?? $existing->current_occupation,
                'city'                 => $datum['data']['city']        ?? $existing->city,
            ]);
        } else {
            // דמות חדשה — family-chart שלח UUID זמני
            $person = Person::create([
                'first_name'           => $datum['data']['first name']  ?? '',
                'last_name'            => $datum['data']['last name']   ?? '',
                'gender'               => ($datum['data']['gender'] ?? 'M') === 'M' ? 'male' : 'female',
                'birth_date_gregorian' => ($datum['data']['birthday']   ?? '') ?: null,
                'birth_date_hebrew'    => ($datum['data']['birthday_he'] ?? '') ?: null,
                'current_occupation'   => ($datum['data']['occupation'] ?? '') ?: null,
                'city'                 => ($datum['data']['city']       ?? '') ?: null,
                'created_by'           => Auth::id(),
            ]);

            // קשרי משפחה מתוך rels (IDs הם DB IDs אמיתיים של דמויות קיימות)
            $explicitParentIds = [];
            foreach ($datum['rels']['parents'] ?? [] as $pid) {
                if (is_numeric($pid) && (int)$pid > 0) {
                    $parentId = (int)$pid;
                    Relationship::firstOrCreate([
                        'person1_id' => $parentId,
                        'person2_id' => $person->id,
                        'type'       => 'parent_child',
                    ]);
                    $explicitParentIds[] = $parentId;
                }
            }

            // אם נוסף ילד לאחד מבני הזוג, מחבר אוטומטית גם את בן/בת הזוג כהורה
            foreach ($explicitParentIds as $parentId) {
                $spouseRel = Relationship::where('type', 'spouse')
                    ->where(fn($q) => $q->where('person1_id', $parentId)->orWhere('person2_id', $parentId))
                    ->first();
                if ($spouseRel) {
                    $spouseId = $spouseRel->person1_id == $parentId
                        ? $spouseRel->person2_id
                        : $spouseRel->person1_id;
                    if (!in_array($spouseId, $explicitParentIds)) {
                        Relationship::firstOrCreate([
                            'person1_id' => $spouseId,
                            'person2_id' => $person->id,
                            'type'       => 'parent_child',
                        ]);
                    }
                }
            }
            foreach ($datum['rels']['spouses'] ?? [] as $sid) {
                if (is_numeric($sid) && (int)$sid > 0) {
                    Relationship::updateOrCreate(
                        [
                            'person1_id' => min($person->id, (int)$sid),
                            'person2_id' => max($person->id, (int)$sid),
                            'type'       => 'spouse',
                        ],
                        [
                            'marriage_date_gregorian' => ($datum['data']['marriage_date']    ?? '') ?: null,
                            'marriage_date_hebrew'    => ($datum['data']['marriage_date_he'] ?? '') ?: null,
                        ]
                    );
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
            'current_occupation', 'city', 'email', 'profile_photo'
        )->get();

        $relationships = Relationship::orderByRaw('COALESCE(sort_order, 999) ASC')->get();

        // מפה מהירה: birth_date לפי person id
        $birthDates = $people->pluck('birth_date_gregorian', 'id');

        // בנה אינדקסים מהירים
        $children  = [];   // parent_id → [child_id, ...]
        $parents   = [];   // child_id  → [parent_id, ...]
        $spouses   = [];   // person_id → [spouse_id, ...]
        $marriages = [];   // person_id → {spouse_id → {date, date_he}}

        foreach ($relationships as $rel) {
            if ($rel->type === 'parent_child') {
                $children[$rel->person1_id][] = (string) $rel->person2_id;
                $parents[$rel->person2_id][]  = (string) $rel->person1_id;
            } elseif ($rel->type === 'spouse') {
                $spouses[$rel->person1_id][] = (string) $rel->person2_id;
                $spouses[$rel->person2_id][] = (string) $rel->person1_id;
                $mData = [
                    'date'    => $rel->marriage_date_gregorian?->format('Y-m-d'),
                    'date_he' => $rel->marriage_date_hebrew,
                ];
                $marriages[$rel->person1_id][(string) $rel->person2_id] = $mData;
                $marriages[$rel->person2_id][(string) $rel->person1_id] = $mData;
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

        // מיין ילדים: לפי sort_order (כבר נשמר בסדר הנכון ברשומות) → birth_date_gregorian.
        // הופך לסדר יורד (צעיר ראשון) כדי שבגרף LTR הבכור יופיע מימין (RTL-ראשון).
        foreach ($children as $parentId => &$childIds) {
            usort($childIds, function ($aId, $bId) use ($birthDates) {
                $a = $birthDates[(int) $aId] ?? null;
                $b = $birthDates[(int) $bId] ?? null;
                if ($a === $b) return 0;
                if ($a === null) return -1; // ללא תאריך — משמאל
                if ($b === null) return 1;
                return $b <=> $a; // יורד: צעיר ראשון → בכור אחרון (ימין בגרף)
            });
        }
        unset($childIds);

        $nodes = $people->map(function ($p) use ($children, $parents, $spouses, $marriages) {
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
                    'email'       => $p->email,
                    'marriages'   => (object) ($marriages[$p->id] ?? []),
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

        return $nodes;
    }

    private function findRootPersonId(array $nodes): string
    {
        foreach ($nodes as $node) {
            if (empty($node['rels']['parents'])) {
                return $node['id'];
            }
        }
        return $nodes[0]['id'] ?? '1';
    }
}
