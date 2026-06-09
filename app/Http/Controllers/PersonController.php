<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\Relationship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PersonController extends Controller
{
    public function index()
    {
        $people = Person::select('id', 'first_name', 'last_name', 'gender', 'birth_date_gregorian', 'is_deceased', 'profile_photo')
            ->orderBy('first_name')
            ->get()
            ->map(fn($p) => [
                'id'           => $p->id,
                'full_name'    => $p->full_name,
                'gender'       => $p->gender,
                'birth_year'   => $p->birth_date_gregorian?->year,
                'is_deceased'  => $p->is_deceased,
                'photo_url'    => $p->profile_photo_url,
            ]);

        return Inertia::render('People/Index', ['people' => $people]);
    }

    public function create()
    {
        $people = Person::select('id', 'first_name', 'last_name')->orderBy('first_name')->get()
            ->map(fn($p) => ['id' => $p->id, 'label' => $p->full_name]);

        return Inertia::render('People/Create', ['allPeople' => $people]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name'            => 'required|string|max:100',
            'last_name'             => 'required|string|max:100',
            'gender'                => 'required|in:male,female',
            'birth_date_gregorian'  => 'nullable|date',
            'birth_date_hebrew'     => 'nullable|string|max:50',
            'is_deceased'           => 'boolean',
            'death_date_gregorian'  => 'nullable|date|required_if:is_deceased,true',
            'death_date_hebrew'     => 'nullable|string|max:50',
            'current_occupation'    => 'nullable|string|max:255',
            'bio'                   => 'nullable|string',
            'city'                  => 'nullable|string|max:100',
            'parent_ids'            => 'nullable|array|max:2',
            'parent_ids.*'          => 'integer|exists:people,id',
            'spouse_id'             => 'nullable|integer|exists:people,id',
            'children'              => 'nullable|array',
            'children.*.first_name' => 'required_with:children|string|max:100',
            'children.*.last_name'  => 'nullable|string|max:100',
            'children.*.gender'     => 'required_with:children|in:male,female',
            'children.*.birth_date_gregorian' => 'nullable|date',
        ]);

        $person = Person::create([
            'first_name'           => $data['first_name'],
            'last_name'            => $data['last_name'],
            'gender'               => $data['gender'],
            'birth_date_gregorian' => $data['birth_date_gregorian'] ?? null,
            'birth_date_hebrew'    => $data['birth_date_hebrew'] ?? null,
            'is_deceased'          => $data['is_deceased'] ?? false,
            'death_date_gregorian' => $data['death_date_gregorian'] ?? null,
            'death_date_hebrew'    => $data['death_date_hebrew'] ?? null,
            'current_occupation'   => $data['current_occupation'] ?? null,
            'bio'                  => $data['bio'] ?? null,
            'city'                 => $data['city'] ?? null,
            'created_by'           => Auth::id(),
        ]);

        // קשרי הורים
        foreach ($data['parent_ids'] ?? [] as $parentId) {
            Relationship::create([
                'person1_id' => $parentId,
                'person2_id' => $person->id,
                'type'       => 'parent_child',
            ]);
        }

        // קשר בן/בת זוג
        if (!empty($data['spouse_id'])) {
            Relationship::firstOrCreate([
                'person1_id' => min($person->id, $data['spouse_id']),
                'person2_id' => max($person->id, $data['spouse_id']),
                'type'       => 'spouse',
            ]);
        }

        // הוספת ילדים bulk
        foreach ($data['children'] ?? [] as $childData) {
            $child = Person::create([
                'first_name'           => $childData['first_name'],
                'last_name'            => $childData['last_name'] ?? $person->last_name,
                'gender'               => $childData['gender'],
                'birth_date_gregorian' => $childData['birth_date_gregorian'] ?? null,
                'created_by'           => Auth::id(),
            ]);

            Relationship::create([
                'person1_id' => $person->id,
                'person2_id' => $child->id,
                'type'       => 'parent_child',
            ]);
        }

        return redirect()->route('people.show', $person)->with('success', 'הדמות נוספה בהצלחה');
    }

    public function show(Person $person)
    {
        $person->load(['parents', 'children', 'photos']);

        $spouses = $person->spouses()->get();

        $allPeople = Person::where('id', '!=', $person->id)
            ->select('id', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn($p) => ['id' => $p->id, 'label' => $p->full_name]);

        return Inertia::render('People/Show', [
            'person'    => $this->formatPerson($person),
            'parents'   => $person->parents->map(fn($p) => ['id' => $p->id, 'full_name' => $p->full_name, 'photo_url' => $p->profile_photo_url]),
            'children'  => $person->children->map(fn($p) => ['id' => $p->id, 'full_name' => $p->full_name, 'photo_url' => $p->profile_photo_url, 'gender' => $p->gender]),
            'spouses'   => $spouses->map(fn($p) => ['id' => $p->id, 'full_name' => $p->full_name, 'photo_url' => $p->profile_photo_url]),
            'allPeople' => $allPeople,
        ]);
    }

    public function addSpouse(Request $request, Person $person)
    {
        $data = $request->validate([
            'spouse_id' => 'required|integer|exists:people,id',
        ]);

        Relationship::firstOrCreate([
            'person1_id' => min($person->id, $data['spouse_id']),
            'person2_id' => max($person->id, $data['spouse_id']),
            'type'       => 'spouse',
        ]);

        return redirect()->route('people.show', $person)->with('success', 'בן/בת הזוג נוסף/ה');
    }

    public function edit(Person $person)
    {
        $allPeople = Person::where('id', '!=', $person->id)
            ->select('id', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn($p) => ['id' => $p->id, 'label' => $p->full_name]);

        return Inertia::render('People/Edit', [
            'person'    => $this->formatPerson($person),
            'allPeople' => $allPeople,
            'parentIds' => $person->parents()->pluck('people.id'),
            'spouseId'  => $person->spouses()->first()?->id,
        ]);
    }

    public function update(Request $request, Person $person)
    {
        $data = $request->validate([
            'first_name'           => 'required|string|max:100',
            'last_name'            => 'required|string|max:100',
            'gender'               => 'required|in:male,female',
            'birth_date_gregorian' => 'nullable|date',
            'birth_date_hebrew'    => 'nullable|string|max:50',
            'is_deceased'          => 'boolean',
            'death_date_gregorian' => 'nullable|date',
            'death_date_hebrew'    => 'nullable|string|max:50',
            'current_occupation'   => 'nullable|string|max:255',
            'bio'                  => 'nullable|string',
            'city'                 => 'nullable|string|max:100',
            'parent_ids'           => 'nullable|array|max:2',
            'parent_ids.*'         => 'integer|exists:people,id',
            'spouse_id'            => 'nullable|integer|exists:people,id',
        ]);

        $person->update($data);

        // עדכון הורים — מחק קיימים והוסף חדשים
        Relationship::where('person2_id', $person->id)->where('type', 'parent_child')->delete();
        foreach ($data['parent_ids'] ?? [] as $parentId) {
            Relationship::create(['person1_id' => $parentId, 'person2_id' => $person->id, 'type' => 'parent_child']);
        }

        // עדכון בן/בת זוג
        Relationship::where('type', 'spouse')
            ->where(fn($q) => $q->where('person1_id', $person->id)->orWhere('person2_id', $person->id))
            ->delete();
        if (!empty($data['spouse_id'])) {
            Relationship::create([
                'person1_id' => min($person->id, $data['spouse_id']),
                'person2_id' => max($person->id, $data['spouse_id']),
                'type'       => 'spouse',
            ]);
        }

        return redirect()->route('people.show', $person)->with('success', 'הפרטים עודכנו');
    }

    public function destroy(Person $person)
    {
        // רק Admin יכול למחוק
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        // מחק קשרים
        Relationship::where('person1_id', $person->id)->orWhere('person2_id', $person->id)->delete();

        // מחק תמונת פרופיל
        if ($person->profile_photo) {
            Storage::disk('public')->delete($person->profile_photo);
        }

        $person->delete();

        return redirect()->route('people.index')->with('success', 'הדמות נמחקה');
    }

    private function formatPerson(Person $person): array
    {
        return [
            'id'                   => $person->id,
            'first_name'           => $person->first_name,
            'last_name'            => $person->last_name,
            'full_name'            => $person->full_name,
            'gender'               => $person->gender,
            'birth_date_gregorian' => $person->birth_date_gregorian?->toDateString(),
            'birth_date_hebrew'    => $person->birth_date_hebrew,
            'is_deceased'          => $person->is_deceased,
            'death_date_gregorian' => $person->death_date_gregorian?->toDateString(),
            'death_date_hebrew'    => $person->death_date_hebrew,
            'current_occupation'   => $person->current_occupation,
            'bio'                  => $person->bio,
            'city'                 => $person->city,
            'photo_url'            => $person->profile_photo_url,
        ];
    }
}
