<?php

namespace App\Http\Controllers;

use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\Person;
use App\Models\Relationship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
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
            'maiden_name'           => 'nullable|string|max:100',
            'gender'                => 'required|in:male,female',
            'birth_date_gregorian'  => 'nullable|date',
            'birth_date_hebrew'     => 'nullable|string|max:50',
            'is_deceased'           => 'boolean',
            'death_date_gregorian'  => 'nullable|date|required_if:is_deceased,true',
            'death_date_hebrew'     => 'nullable|string|max:50',
            'current_occupation'    => 'nullable|string|max:255',
            'bio'                   => 'nullable|string',
            'city'                  => 'nullable|string|max:100',
            'email'                 => 'nullable|email|max:255',
            'parent_ids'            => 'nullable|array|max:2',
            'parent_ids.*'          => 'integer|exists:people,id',
            'spouse_id'              => 'nullable|integer|exists:people,id',
            'marriage_date_gregorian'=> 'nullable|date',
            'marriage_date_hebrew'   => 'nullable|string|max:50',
            'children'              => 'nullable|array',
            'children.*.first_name' => 'required_with:children|string|max:100',
            'children.*.last_name'  => 'nullable|string|max:100',
            'children.*.gender'     => 'required_with:children|in:male,female',
            'children.*.birth_date_gregorian' => 'nullable|date',
        ]);

        $person = Person::create([
            'first_name'           => $data['first_name'],
            'last_name'            => $data['last_name'],
            'maiden_name'          => $data['maiden_name'] ?? null,
            'gender'               => $data['gender'],
            'birth_date_gregorian' => $data['birth_date_gregorian'] ?? null,
            'birth_date_hebrew'    => $data['birth_date_hebrew'] ?? null,
            'is_deceased'          => $data['is_deceased'] ?? false,
            'death_date_gregorian' => $data['death_date_gregorian'] ?? null,
            'death_date_hebrew'    => $data['death_date_hebrew'] ?? null,
            'current_occupation'   => $data['current_occupation'] ?? null,
            'bio'                  => $data['bio'] ?? null,
            'city'                 => $data['city'] ?? null,
            'email'                => $data['email'] ?? null,
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
            Relationship::updateOrCreate(
                [
                    'person1_id' => min($person->id, $data['spouse_id']),
                    'person2_id' => max($person->id, $data['spouse_id']),
                    'type'       => 'spouse',
                ],
                [
                    'marriage_date_gregorian' => $data['marriage_date_gregorian'] ?? null,
                    'marriage_date_hebrew'    => $data['marriage_date_hebrew'] ?? null,
                ]
            );
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
        $person->load(['parents', 'children']);
        $spouses = $person->spouses()->get();

        // תאריכי נישואין לפי בן/בת זוג
        $spouseRels = Relationship::where('type', 'spouse')
            ->where(fn($q) => $q->where('person1_id', $person->id)->orWhere('person2_id', $person->id))
            ->get()
            ->mapWithKeys(function ($r) use ($person) {
                $sid = $r->person1_id == $person->id ? $r->person2_id : $r->person1_id;
                return [$sid => [
                    'marriage_date_gregorian' => $r->marriage_date_gregorian?->toDateString(),
                    'marriage_date_hebrew'    => $r->marriage_date_hebrew,
                ]];
            });

        // Siblings: people who share at least one parent with this person
        $parentIds = $person->parents->pluck('id');
        $siblings  = collect();
        if ($parentIds->isNotEmpty()) {
            $siblingIds = Relationship::where('type', 'parent_child')
                ->whereIn('person1_id', $parentIds)
                ->where('person2_id', '!=', $person->id)
                ->pluck('person2_id')
                ->unique()
                ->values();
            $siblings = Person::whereIn('id', $siblingIds)
                ->select('id', 'first_name', 'last_name', 'profile_photo', 'gender', 'is_deceased', 'birth_date_gregorian')
                ->orderByRaw('COALESCE(birth_date_gregorian, "9999-01-01") ASC')
                ->get();
        }

        // Family photos this person is tagged in (table may not exist yet)
        try {
            $photosTagged = \App\Models\PhotoTag::where('person_id', $person->id)
                ->with('familyPhoto')
                ->get()
                ->map(fn($t) => [
                    'id'        => $t->family_photo_id,
                    'url'       => $t->familyPhoto->url,
                    'title'     => $t->familyPhoto->title,
                    'x_percent' => $t->x_percent,
                    'y_percent' => $t->y_percent,
                ]);
        } catch (\Exception $e) {
            $photosTagged = collect();
        }

        $allPeople = Person::where('id', '!=', $person->id)
            ->select('id', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn($p) => ['id' => $p->id, 'label' => $p->full_name]);

        return Inertia::render('People/Show', [
            'person'       => $this->formatPerson($person),
            'parents'      => $person->parents->map(fn($p) => $this->formatMini($p)),
            'children'     => $person->children->map(fn($p) => $this->formatMini($p)),
            'spouses'      => $spouses->map(function ($p) use ($spouseRels) {
                $mini = $this->formatMini($p);
                $mini['marriage_date_gregorian'] = $spouseRels[$p->id]['marriage_date_gregorian'] ?? null;
                $mini['marriage_date_hebrew']    = $spouseRels[$p->id]['marriage_date_hebrew']    ?? null;
                return $mini;
            }),
            'siblings'     => $siblings->map(fn($p) => $this->formatMini($p)),
            'photosTagged' => $photosTagged,
            'allPeople'    => $allPeople,
            'parentIds'    => $person->parents->pluck('id')->values(),
            'spouseId'     => $spouses->first()?->id,
        ]);
    }

    public function addParent(Request $request, Person $person)
    {
        if ($person->parents()->count() >= 2) {
            return back()->withErrors(['parent' => 'לדמות כבר יש 2 הורים']);
        }

        $data = $request->validate([
            'existing_id'          => 'nullable|integer|exists:people,id',
            'first_name'           => 'required_without:existing_id|string|max:100',
            'last_name'            => 'nullable|string|max:100',
            'gender'               => 'required_without:existing_id|in:male,female',
            'birth_date_gregorian' => 'nullable|date',
            'birth_date_hebrew'    => 'nullable|string|max:50',
        ]);

        if (!empty($data['existing_id'])) {
            $parentId = $data['existing_id'];
        } else {
            $parent = Person::create([
                'first_name'           => $data['first_name'],
                'last_name'            => $data['last_name'] ?? $person->last_name,
                'gender'               => $data['gender'],
                'birth_date_gregorian' => $data['birth_date_gregorian'] ?? null,
                'birth_date_hebrew'    => $data['birth_date_hebrew'] ?? null,
                'created_by'           => Auth::id(),
            ]);
            $parentId = $parent->id;
        }

        Relationship::firstOrCreate([
            'person1_id' => $parentId,
            'person2_id' => $person->id,
            'type'       => 'parent_child',
        ]);

        return redirect()->route('people.show', $person)->with('success', 'ההורה נוסף');
    }

    public function addSibling(Request $request, Person $person)
    {
        $data = $request->validate([
            'first_name'           => 'required|string|max:100',
            'last_name'            => 'nullable|string|max:100',
            'gender'               => 'required|in:male,female',
            'birth_date_gregorian' => 'nullable|date',
            'birth_date_hebrew'    => 'nullable|string|max:50',
        ]);

        $sibling = Person::create([
            'first_name'           => $data['first_name'],
            'last_name'            => $data['last_name'] ?? $person->last_name,
            'gender'               => $data['gender'],
            'birth_date_gregorian' => $data['birth_date_gregorian'] ?? null,
            'birth_date_hebrew'    => $data['birth_date_hebrew'] ?? null,
            'created_by'           => Auth::id(),
        ]);

        $parentIds = $person->parents()->pluck('people.id');
        foreach ($parentIds as $parentId) {
            Relationship::firstOrCreate([
                'person1_id' => $parentId,
                'person2_id' => $sibling->id,
                'type'       => 'parent_child',
            ]);
        }

        return redirect()->route('people.show', $person)->with('success', 'האח/אחות נוסף/ה');
    }

    public function addSpouse(Request $request, Person $person)
    {
        $data = $request->validate([
            'spouse_id'              => 'required|integer|exists:people,id',
            'marriage_date_gregorian'=> 'nullable|date',
            'marriage_date_hebrew'   => 'nullable|string|max:50',
        ]);

        Relationship::updateOrCreate(
            [
                'person1_id' => min($person->id, $data['spouse_id']),
                'person2_id' => max($person->id, $data['spouse_id']),
                'type'       => 'spouse',
            ],
            [
                'marriage_date_gregorian' => $data['marriage_date_gregorian'] ?? null,
                'marriage_date_hebrew'    => $data['marriage_date_hebrew'] ?? null,
            ]
        );

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
            'maiden_name'          => 'nullable|string|max:100',
            'gender'               => 'required|in:male,female',
            'birth_date_gregorian' => 'nullable|date',
            'birth_date_hebrew'    => 'nullable|string|max:50',
            'is_deceased'          => 'boolean',
            'death_date_gregorian' => 'nullable|date',
            'death_date_hebrew'    => 'nullable|string|max:50',
            'current_occupation'   => 'nullable|string|max:255',
            'bio'                  => 'nullable|string',
            'city'                 => 'nullable|string|max:100',
            'email'                => 'nullable|email|max:255',
            'phone'                => 'nullable|string|max:30',
            'parent_ids'           => 'nullable|array|max:2',
            'parent_ids.*'         => 'integer|exists:people,id',
            'spouse_id'              => 'nullable|integer|exists:people,id',
            'marriage_date_gregorian'=> 'nullable|date',
            'marriage_date_hebrew'   => 'nullable|string|max:50',
        ]);

        $oldEmail = $person->email;
        $newEmail = $data['email'] ?? null;

        $person->update($data);

        // שלח הזמנה אם מייל חדש נוסף ואין לו עדיין משתמש/הזמנה פעילה
        if ($newEmail && $newEmail !== $oldEmail) {
            $alreadyUser = \App\Models\User::where('email', $newEmail)->exists();
            $alreadyInvited = Invitation::where('email', $newEmail)
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->exists();

            if (! $alreadyUser && ! $alreadyInvited) {
                $invitation = Invitation::generate(
                    email:     $newEmail,
                    invitedBy: Auth::id(),
                    personId:  $person->id,
                );
                Mail::to($newEmail)->send(new InvitationMail($invitation));
            }
        }

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
                'person1_id'              => min($person->id, $data['spouse_id']),
                'person2_id'              => max($person->id, $data['spouse_id']),
                'type'                    => 'spouse',
                'marriage_date_gregorian' => $data['marriage_date_gregorian'] ?? null,
                'marriage_date_hebrew'    => $data['marriage_date_hebrew'] ?? null,
            ]);
        }

        return redirect()->route('people.show', $person)->with('success', 'הפרטים עודכנו');
    }

    public function uploadPhoto(Request $request, Person $person)
    {
        $request->validate(['profile_photo' => 'required|image|max:5120']);

        if ($person->profile_photo) {
            Storage::disk('public')->delete($person->profile_photo);
        }

        $path = $request->file('profile_photo')->store('avatars', 'public');
        $person->update(['profile_photo' => $path]);

        return redirect()->back()->with('success', 'התמונה עודכנה');
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

    public function reorderChildren(Request $request, Person $person)
    {
        $data = $request->validate([
            'child_ids'   => 'required|array',
            'child_ids.*' => 'integer|exists:people,id',
        ]);

        foreach ($data['child_ids'] as $index => $childId) {
            Relationship::where('person1_id', $person->id)
                ->where('person2_id', $childId)
                ->where('type', 'parent_child')
                ->update(['sort_order' => $index + 1]);
        }

        return response()->json(['ok' => true]);
    }

    private function formatMini(Person $p): array
    {
        return [
            'id'         => $p->id,
            'full_name'  => $p->full_name,
            'photo_url'  => $p->profile_photo_url,
            'gender'     => $p->gender,
            'is_deceased'=> $p->is_deceased,
        ];
    }

    private function formatPerson(Person $person): array
    {
        return [
            'id'                   => $person->id,
            'first_name'           => $person->first_name,
            'last_name'            => $person->last_name,
            'maiden_name'          => $person->maiden_name,
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
            'email'                => $person->email,
            'photo_url'            => $person->profile_photo_url,
        ];
    }
}
