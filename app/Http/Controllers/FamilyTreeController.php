<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\Relationship;
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
