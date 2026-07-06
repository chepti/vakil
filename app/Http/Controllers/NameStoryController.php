<?php

namespace App\Http\Controllers;

use App\Models\NameStory;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class NameStoryController extends Controller
{
    public function index()
    {
        $stories = NameStory::with(['person', 'createdBy', 'namedAfter'])
            ->latest()
            ->get()
            ->map(fn ($s) => [
                'id'              => $s->id,
                'content'         => $s->content,
                'person_id'       => $s->person_id,
                'person_name'     => $s->person?->full_name,
                'person_context'  => $s->person?->ancestralContext(),
                'person_photo'    => $s->person?->profile_photo_url,
                'person_gender'   => $s->person?->gender,
                'named_after_id'      => $s->named_after_person_id,
                'named_after_name'    => $s->namedAfter?->full_name,
                'named_after_context' => $s->namedAfter?->ancestralContext(),
                'created_by_name' => $s->createdBy->name,
                'can_edit'        => Auth::user()->role === 'admin' || $s->created_by === Auth::id(),
            ]);

        return Inertia::render('NameStories/Index', [
            'stories' => $stories,
        ]);
    }

    public function create(Request $request)
    {
        return Inertia::render('NameStories/Form', [
            'story'         => null,
            'people'        => $this->peopleWithoutStory(),
            'allPeople'     => $this->formatPeople(Person::orderBy('first_name')->get(['id', 'first_name', 'last_name'])),
            'presetPerson'  => $request->integer('person') ?: null,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'person_id'             => 'required|exists:people,id',
            'content'               => 'required|string|max:5000',
            'named_after_person_id' => 'nullable|exists:people,id|different:person_id',
        ]);

        // סיפור אחד לכל דמות — אם קיים, נעדכן במקום ליצור כפילות
        NameStory::updateOrCreate(
            ['person_id' => $data['person_id']],
            [
                'content'               => $data['content'],
                'named_after_person_id' => $data['named_after_person_id'] ?? null,
                'created_by'            => Auth::id(),
            ],
        );

        return redirect()->route('name-stories.index')->with('success', 'הסיפור נוסף בהצלחה!');
    }

    public function edit(NameStory $nameStory)
    {
        $this->authorizeEdit($nameStory);

        $people = Person::orderBy('first_name')->get(['id', 'first_name', 'last_name']);

        return Inertia::render('NameStories/Form', [
            'story' => [
                'id'        => $nameStory->id,
                'person_id' => $nameStory->person_id,
                'content'   => $nameStory->content,
                'named_after_person_id' => $nameStory->named_after_person_id,
            ],
            'people'       => $this->formatPeople($people),
            'allPeople'    => $this->formatPeople($people),
            'presetPerson' => null,
        ]);
    }

    public function update(Request $request, NameStory $nameStory)
    {
        $this->authorizeEdit($nameStory);

        $data = $request->validate([
            'person_id'             => 'required|exists:people,id',
            'content'               => 'required|string|max:5000',
            'named_after_person_id' => 'nullable|exists:people,id|different:person_id',
        ]);
        $data['named_after_person_id'] = $data['named_after_person_id'] ?? null;

        $nameStory->update($data);

        return redirect()->route('name-stories.index')->with('success', 'הסיפור עודכן!');
    }

    public function destroy(NameStory $nameStory)
    {
        $this->authorizeEdit($nameStory);

        $nameStory->delete();

        return redirect()->route('name-stories.index')->with('success', 'הסיפור נמחק.');
    }

    private function peopleWithoutStory()
    {
        $taken = NameStory::pluck('person_id');

        $people = Person::whereNotIn('id', $taken)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        return $this->formatPeople($people);
    }

    /** מוסיף לכל דמות את ההקשר המשפחתי "של X של Y" לזיהוי חד-משמעי ברשימה */
    private function formatPeople($people)
    {
        return $people->map(fn ($p) => [
            'id'      => $p->id,
            'name'    => $p->full_name,
            'context' => $p->ancestralContext(),
        ])->values();
    }

    private function authorizeEdit(NameStory $nameStory): void
    {
        if (Auth::user()->role !== 'admin' && $nameStory->created_by !== Auth::id()) {
            abort(403);
        }
    }
}
