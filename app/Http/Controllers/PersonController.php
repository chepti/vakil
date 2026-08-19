<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\Photo;
use App\Models\PhotoTag;
use App\Models\Relationship;
use App\Services\Family\RelationshipSync;
use App\Services\Photos\PhotoStorage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PersonController extends Controller
{
    public function index()
    {
        $people = Person::select('id', 'first_name', 'last_name', 'gender', 'birth_date_gregorian', 'birth_date_hebrew', 'is_deceased', 'profile_photo')
            ->orderBy('first_name')
            ->get()
            ->map(fn($p) => [
                'id'           => $p->id,
                'full_name'    => $p->full_name,
                'gender'       => $p->gender,
                'birth_year'   => $p->birth_date_gregorian?->year,
                // התאריכים עצמם — התצוגה בוחרת בין לועזי לעברי לפי הגדרת האתר
                'birth_date'   => $p->birth_date_gregorian?->format('Y-m-d'),
                'birth_he'     => $p->birth_date_hebrew,
                'is_deceased'  => $p->is_deceased,
                'photo_url'    => $p->profile_photo_url,
            ]);

        return Inertia::render('People/Index', ['people' => $people]);
    }

    /**
     * גלריית תמונות של דמות — לתצוגה מוגדלת (לייטבוקס) מכל מקום באתר.
     * מאחד: תמונת הפרופיל הנוכחית + תמונות פרופיל שהועלו + כל תמונה משפחתית שבה הדמות מתויגת,
     * עם קישור לתמונה המקורית (לפני חיתוך) כשקיימת. נטען לפי דרישה (lazy) — לא חלק מטעינת העץ.
     */
    public function apiPhotosGallery(Person $person): JsonResponse
    {
        $items = collect();

        foreach ($person->photos()->latest()->get() as $p) {
            $items->push([
                'url'         => $p->thumb_url,
                'originalUrl' => $p->original_url,
                'label'       => $p->thumb_path === $person->profile_photo ? 'תמונת פרופיל' : null,
            ]);
        }

        if ($person->profile_photo && !$items->contains(fn($i) => str_contains($i['url'], $person->profile_photo))) {
            $items->prepend([
                'url'         => $person->profile_photo_url,
                'originalUrl' => $person->profile_photo_url,
                'label'       => 'תמונת פרופיל',
            ]);
        }

        foreach (PhotoTag::where('person_id', $person->id)->with('familyPhoto')->get() as $tag) {
            if (!$tag->familyPhoto) continue;
            $url = $tag->familyPhoto->url;
            if ($items->contains(fn($i) => $i['url'] === $url)) continue;
            $items->push([
                'url'         => $url,
                'originalUrl' => $url,
                'label'       => $tag->familyPhoto->title
                    ?: ($tag->familyPhoto->taken_year ? 'שנת ' . $tag->familyPhoto->taken_year : 'תמונה משפחתית'),
            ]);
        }

        return response()->json([
            'name'   => $person->full_name,
            'photos' => $items->values(),
        ]);
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
            'explicit_parents'      => 'nullable|boolean',
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

        // קשרי הורים — explicit_parents מסמן שיוך מפורש שגובר על המיזוג האוטומטי של בני-זוג
        $explicit = (bool) ($data['explicit_parents'] ?? false);
        foreach ($data['parent_ids'] ?? [] as $parentId) {
            Relationship::create([
                'person1_id'  => $parentId,
                'person2_id'  => $person->id,
                'type'        => 'parent_child',
                'is_explicit' => $explicit,
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

        // השלמת הורות אוטומטית: בן/בת הזוג נהיה הורה של הילדים (הקיימים והחדשים),
        // וכשנבחר הורה יחיד — בן/בת הזוג שלו נרשם כהורה השני.
        $sync      = app(RelationshipSync::class);
        $completed = [];

        if (! empty($data['spouse_id'])) {
            $completed = $sync->completeParenthoodForCouple($person, Person::findOrFail($data['spouse_id']));
        }

        $parentIds = $data['parent_ids'] ?? [];
        if (! $explicit && count($parentIds) === 1) {
            $completed = array_merge($completed, $sync->completeSecondParent($person, Person::findOrFail($parentIds[0])));
        }

        $message = 'הדמות נוספה בהצלחה';
        if ($extra = RelationshipSync::summarize($completed)) {
            $message .= ' · ' . $extra;
        }

        return redirect()->route('people.show', $person)->with('success', $message);
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
                    'id'         => $t->family_photo_id,
                    'tag_id'     => $t->id,
                    'url'        => $t->familyPhoto->url,
                    'source_path' => $t->familyPhoto->path,
                    'title'      => $t->familyPhoto->title,
                    'x_percent' => (float) $t->x_percent,
                    'y_percent' => (float) $t->y_percent,
                    'w_percent' => (float) ($t->w_percent ?? 10),
                    'h_percent' => (float) ($t->h_percent ?? 10),
                ]);
        } catch (\Exception $e) {
            $photosTagged = collect();
        }

        // תמונות פרופיל שהועלו לדמות (כולל מקור לעריכת חיתוך)
        $photoRows = $person->photos()->latest()->get();
        $profilePhotos = $photoRows->map(fn($p) => [
            'id'           => $p->id,
            'thumb_url'    => $p->thumb_url,
            'original_url' => $p->original_url,
            'source_path'  => $p->original_path ?: $p->thumb_path,
            'is_current'   => $p->thumb_path === $person->profile_photo,
        ])->values();

        // השלמה: תמונת הפרופיל הנוכחית שאין לה רשומת Photo (העלאות ישנות)
        if ($person->profile_photo && !$photoRows->firstWhere('thumb_path', $person->profile_photo)) {
            $profilePhotos->prepend([
                'id'           => null,
                'thumb_url'    => $person->profile_photo_url,
                'original_url' => $person->profile_photo_url,
                'source_path'  => $person->profile_photo,
                'is_current'   => true,
            ]);
        }

        $allPeople = Person::where('id', '!=', $person->id)
            ->select('id', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn($p) => ['id' => $p->id, 'label' => $p->full_name]);

        return Inertia::render('People/Show', [
            'person'        => $this->formatPerson($person),
            'profilePhotos' => $profilePhotos,
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
            'nameStory'    => $this->loadNameStory($person),
        ]);
    }

    /** סיפור השם של הדמות — "למה קראו לי בשמי" (הטבלה עשויה שלא להתקיים עדיין) */
    private function loadNameStory(Person $person): ?array
    {
        try {
            $story = $person->nameStory()->with('createdBy')->first();
        } catch (\Exception $e) {
            return null;
        }

        if (! $story) return null;

        return [
            'id'              => $story->id,
            'content'         => $story->content,
            'created_by_name' => $story->createdBy?->name,
            'can_edit'        => Auth::user()->role === 'admin' || $story->created_by === Auth::id(),
        ];
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

        // אם להורה שנוסף יש בן/בת זוג יחיד — גם הוא נרשם כהורה
        $completed = app(RelationshipSync::class)
            ->completeSecondParent($person, Person::findOrFail($parentId));

        $message = 'ההורה נוסף';
        if ($extra = RelationshipSync::summarize($completed)) {
            $message .= ' · ' . $extra;
        }

        return redirect()->route('people.show', $person)->with('success', $message);
    }

    /**
     * ניתוק קשר הורה-ילד שגוי (למשל משיוך לא מדויק בייבוא) — לא מוחק את הדמויות עצמן, רק את הקשר.
     */
    public function removeParent(Person $person, Person $parent)
    {
        Relationship::where('person1_id', $parent->id)
            ->where('person2_id', $person->id)
            ->where('type', 'parent_child')
            ->delete();

        return redirect()->route('people.show', $person)->with('success', 'הקשר להורה נותק');
    }

    public function removeChild(Person $person, Person $child)
    {
        Relationship::where('person1_id', $person->id)
            ->where('person2_id', $child->id)
            ->where('type', 'parent_child')
            ->delete();

        return redirect()->route('people.show', $person)->with('success', 'הקשר לילד/ה נותק');
    }

    public function removeSpouse(Person $person, Person $spouse)
    {
        Relationship::where('type', 'spouse')
            ->where(fn($q) => $q->where('person1_id', min($person->id, $spouse->id))
                                 ->where('person2_id', max($person->id, $spouse->id)))
            ->delete();

        return redirect()->route('people.show', $person)->with('success', 'הקשר לבן/בת הזוג נותק');
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

        // בן/בת הזוג נהיה אוטומטית הורה של הילדים הקיימים (ולהיפך), כשזה חד-משמעי
        $completed = app(RelationshipSync::class)
            ->completeParenthoodForCouple($person, Person::findOrFail($data['spouse_id']));

        $message = 'בן/בת הזוג נוסף/ה';
        if ($extra = RelationshipSync::summarize($completed)) {
            $message .= ' · ' . $extra;
        }

        return redirect()->route('people.show', $person)->with('success', $message);
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

        $message = ($newEmail && $newEmail !== $oldEmail)
            ? "הפרטים עודכנו — הזמנה נשלחה ל-{$newEmail}"
            : 'הפרטים עודכנו';

        // זוגיות שנקבעה כאן גוררת השלמת הורות בשני הכיוונים, כמו במסך הוספת בן/בת זוג
        if (! empty($data['spouse_id'])) {
            $completed = app(RelationshipSync::class)
                ->completeParenthoodForCouple($person, Person::findOrFail($data['spouse_id']));

            if ($extra = RelationshipSync::summarize($completed)) {
                $message .= ' · ' . $extra;
            }
        }

        return redirect()->route('people.show', $person)->with('success', $message);
    }

    public function uploadPhoto(Request $request, Person $person)
    {
        $data = $request->validate([
            'profile_photo' => 'required|image|max:10240',
            'source'        => 'nullable|image|max:10240',   // קובץ מקור מלא (כשמעלים תמונה חתוכה)
            'source_path'   => 'nullable|string',            // נתיב אחסון של מקור קיים (גלריה / פרופיל ישן)
            'crop_x' => 'nullable|numeric', 'crop_y' => 'nullable|numeric',
            'crop_w' => 'nullable|numeric', 'crop_h' => 'nullable|numeric',
            // auto=1 — תיוג פנים אגבי בתמונה משפחתית (לא פעולה מפורשת על כרטיס הדמות):
            // לא דורס תמונת פרופיל "חדשה יותר" בכוונה, כדי שתמונה ישנה שתויגה במקרה
            // לא תהפוך לפתע לתמונת הפרופיל של כולם. הגדרה מפורשת (הכוכב / העלאה מהכרטיס)
            // דורסת תמיד — היא הדרך היחידה לבחור בכוונה תמונה ישנה כפרופיל.
            'auto' => 'nullable|boolean',
        ]);

        // התמונה המוצגת (avatar)
        $thumbPath = $request->file('profile_photo')->store('avatars', 'public');

        // המקור שיישמר לעריכת חיתוך עתידית
        $originalPath = $thumbPath;   // ברירת מחדל: העלאה גולמית — המקור הוא הקובץ עצמו
        if ($request->hasFile('source')) {
            $originalPath = $request->file('source')->store('photos/originals', 'public');
        } elseif (!empty($data['source_path']) && Storage::disk('public')->exists($data['source_path'])) {
            // חיתוך מתמונה שכבר קיימת — מפנים ישירות למקור הקיים בלי לשכפל אותו.
            // אותו קובץ-מקור יכול לשמש כמה חיתוכים; מחיקה מוגנת בספירת-הפניות.
            $originalPath = $data['source_path'];
        }

        // שנת הצילום של המקור — אם המקור הוא תמונה משפחתית עם שנה מתויגת
        $sourceYear = \App\Models\FamilyPhoto::where('path', $originalPath)->value('taken_year');

        \App\Models\Photo::create([
            'person_id'     => $person->id,
            'thumb_path'    => $thumbPath,
            'original_path' => $originalPath,
            'crop_x' => $data['crop_x'] ?? null, 'crop_y' => $data['crop_y'] ?? null,
            'crop_w' => $data['crop_w'] ?? null, 'crop_h' => $data['crop_h'] ?? null,
            'taken_year'    => $sourceYear,
            'uploaded_by'   => Auth::id(),
        ]);

        $isAuto = $request->boolean('auto');
        $shouldSetProfile = true;
        if ($isAuto && $person->profile_photo && $person->profile_photo_year !== null) {
            // לפרופיל הנוכחי יש שנה ידועה — תמונה חדשה חוסמת רק אם גם לה יש שנה ידועה
            // שהיא לפחות כמו הנוכחית. תמונה חדשה בלי שנה (למשל תמונה שלא תויגה בשנה)
            // לא נחשבת "עדכנית יותר" ולא דורסת פרופיל מתוארך — זה מה שגרם לבאג הקודם.
            $shouldSetProfile = $sourceYear !== null && $sourceYear >= $person->profile_photo_year;
        }

        if ($shouldSetProfile) {
            // בפעולה מפורשת (לא auto), תמונה בלי שנה ידועה נחשבת עדכנית — מישהו רק העלה אותה עכשיו
            $yearToStore = $isAuto ? $sourceYear : ($sourceYear ?? (int) date('Y'));
            $person->update(['profile_photo' => $thumbPath, 'profile_photo_year' => $yearToStore]);
        }

        return redirect()->back()->with('success', $shouldSetProfile
            ? 'התמונה עודכנה'
            : 'התמונה נשמרה בגלריה — לא הוגדרה כפרופיל כי יש תמונה עדכנית יותר');
    }

    /** עריכת חיתוך של תמונת פרופיל קיימת — מקבל מהלקוח את הקטע החתוך מחדש */
    public function cropProfilePhoto(Request $request, Person $person, \App\Models\Photo $photo)
    {
        abort_unless($photo->person_id === $person->id, 404);

        $data = $request->validate([
            'profile_photo' => 'required|image|max:10240',
            'crop_x' => 'nullable|numeric', 'crop_y' => 'nullable|numeric',
            'crop_w' => 'nullable|numeric', 'crop_h' => 'nullable|numeric',
        ]);

        $wasCurrent = $person->profile_photo === $photo->thumb_path;

        // מחיקת ה-thumb הישן (אך לא המקור, ולא אם משותף לרשומה אחרת)
        if ($photo->thumb_path && $photo->thumb_path !== $photo->original_path) {
            PhotoStorage::deleteIfUnreferenced($photo->thumb_path, $photo->id);
        }

        $newThumb = $request->file('profile_photo')->store('avatars', 'public');
        $photo->update([
            'thumb_path' => $newThumb,
            'crop_x' => $data['crop_x'] ?? null, 'crop_y' => $data['crop_y'] ?? null,
            'crop_w' => $data['crop_w'] ?? null, 'crop_h' => $data['crop_h'] ?? null,
        ]);

        if ($wasCurrent) {
            $person->update(['profile_photo' => $newThumb]);
        }

        return response()->json([
            'id'        => $photo->id,
            'thumb_url' => $photo->thumb_url,
            'is_current' => $wasCurrent,
        ]);
    }

    public function setProfilePhoto(Person $person, \App\Models\Photo $photo)
    {
        abort_unless($photo->person_id === $person->id, 404);
        // פעולה מפורשת (הכוכב על כרטיס הדמות) — דורסת תמיד, בלי קשר לשנה
        $person->update([
            'profile_photo'      => $photo->thumb_path,
            'profile_photo_year' => $photo->taken_year ?? (int) date('Y'),
        ]);
        return redirect()->back()->with('success', 'תמונת הפרופיל עודכנה');
    }

    public function deleteProfilePhoto(Person $person, \App\Models\Photo $photo)
    {
        abort_unless($photo->person_id === $person->id, 404);

        $thumb = $photo->thumb_path;
        $original = $photo->original_path;

        $wasCurrent = $person->profile_photo === $photo->thumb_path;
        $photo->delete();

        if ($wasCurrent) {
            $next = $person->photos()->latest()->first();
            $person->update(['profile_photo' => $next?->thumb_path]);
        }

        // מוחקים קבצים רק אם אף רשומה/דמות אחרת כבר לא מפנה אליהם
        // (אותו קובץ-מקור עשוי לשמש כמה חיתוכים).
        PhotoStorage::deleteIfUnreferenced($thumb);
        PhotoStorage::deleteIfUnreferenced($original);

        return redirect()->back()->with('success', 'התמונה נמחקה');
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

    /**
     * שיוך ידני של ילד להוריו האמיתיים (גובר על המיזוג האוטומטי של בני-זוג).
     * $person הוא הילד; parent_ids הם 1–2 ההורים הנכונים.
     */
    public function setParents(Request $request, Person $person)
    {
        if (Auth::user()->role !== 'admin') {
            abort(403);
        }

        $data = $request->validate([
            'parent_ids'   => 'present|array|max:2',
            'parent_ids.*' => 'integer|exists:people,id',
        ]);

        $parentIds = array_values(array_unique(array_map('intval', $data['parent_ids'])));

        // שמור sort_order קיים (סדר אחים) כדי לא לאבד אותו בעת מחיקה/יצירה מחדש
        $oldSort = Relationship::where('person2_id', $person->id)
            ->where('type', 'parent_child')
            ->whereNotNull('sort_order')
            ->value('sort_order');

        // מחק את כל קשרי ההורות הקיימים של הילד וצור מחדש עם ההורים שנבחרו, מסומנים כמפורשים
        Relationship::where('person2_id', $person->id)->where('type', 'parent_child')->delete();

        foreach ($parentIds as $pid) {
            if ($pid === $person->id) continue;
            Relationship::create([
                'person1_id'  => $pid,
                'person2_id'  => $person->id,
                'type'        => 'parent_child',
                'sort_order'  => $oldSort,
                'is_explicit' => true,
            ]);
        }

        return response()->json(['ok' => true, 'parent_ids' => $parentIds]);
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
            'phone'                => $person->phone,
            'photo_url'            => $person->profile_photo_url,
        ];
    }
}
