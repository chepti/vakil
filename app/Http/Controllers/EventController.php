<?php

namespace App\Http\Controllers;

use App\Models\Blessing;
use App\Models\Event;
use App\Models\EventReaction;
use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::with('person')
            ->orderBy('event_date')
            ->get()
            ->map(fn($e) => $this->cardPayload($e));

        // מפת בני/בנות זוג (נוכחיים) — שם פרטי לכל אדם
        $spouseRels = \App\Models\Relationship::where('type', 'spouse')
            ->where('is_former', false)
            ->with(['person1:id,first_name,last_name', 'person2:id,first_name,last_name'])
            ->get();
        $spouseMap = [];
        foreach ($spouseRels as $r) {
            if ($r->person1 && $r->person2) {
                $spouseMap[$r->person1_id][] = $r->person2->first_name;
                $spouseMap[$r->person2_id][] = $r->person1->first_name;
            }
        }

        // אנשים עם תאריך לידה — לסימון ימי הולדת על הלוח (כולל פרטים לפאנל)
        $birthdays = Person::whereNotNull('birth_date_gregorian')
            ->where('is_deceased', false)
            ->get()
            ->map(fn($p) => [
                'id'          => $p->id,
                'name'        => $p->full_name,
                'photo'       => $p->profile_photo_url,
                'gender'      => $p->gender,
                'birth_date'  => optional($p->birth_date_gregorian)->format('Y-m-d'),
                'hebrew_date' => $p->birth_date_hebrew,
                'maiden_name' => $p->maiden_name,
                'occupation'  => $p->current_occupation,
                'city'        => $p->city,
                'bio'         => $p->bio,
                'email'       => $p->email,
                'phone'       => $p->phone,
                'spouse'      => isset($spouseMap[$p->id]) ? implode(', ', $spouseMap[$p->id]) : null,
            ]);

        // ימי נישואין — זוגות נשואים עם תאריך נישואין
        $anniversaries = $spouseRels
            ->whereNotNull('marriage_date_gregorian')
            ->filter(fn($r) => $r->person1 && $r->person2)
            ->map(fn($r) => [
                'id'            => $r->id,
                'names'         => $r->person1->full_name . ' ו' . $r->person2->first_name,
                'person1'       => ['id' => $r->person1->id, 'name' => $r->person1->full_name],
                'person2'       => ['id' => $r->person2->id, 'name' => $r->person2->full_name],
                'marriage_date' => optional($r->marriage_date_gregorian)->format('Y-m-d'),
            ])
            ->values();

        return Inertia::render('Events/Index', [
            'events'        => $events,
            'birthdays'     => $birthdays,
            'anniversaries' => $anniversaries,
        ]);
    }

    public function create()
    {
        return Inertia::render('Events/Create', [
            'people' => $this->peopleOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateEvent($request);

        if ($request->hasFile('invitation_image')) {
            $data['invitation_image'] = $request->file('invitation_image')->store('events', 'public');
        }
        unset($data['invitation_image_file']);

        $data['created_by'] = Auth::id();

        $event = Event::create($data);

        return redirect()->route('events.show', $event)->with('success', 'האירוע נוסף');
    }

    public function show(Event $event)
    {
        $event->load(['person', 'audienceBranch', 'creator', 'blessings.user', 'reactions.user']);

        $branch = null;
        if ($event->audienceBranch) {
            $ids   = $event->audienceBranch->descendantIds();
            $names = Person::whereIn('id', $ids)->get()->map->full_name->values();
            $branch = [
                'person_name' => $event->audienceBranch->full_name,
                'count'       => $names->count(),
                'names'       => $names,
            ];
        }

        // קיבוץ תגובות אימוג'י: emoji => { count, mine }
        $userId = Auth::id();
        $reactions = $event->reactions
            ->groupBy('emoji')
            ->map(fn($group, $emoji) => [
                'emoji' => $emoji,
                'count' => $group->count(),
                'mine'  => $group->contains('user_id', $userId),
            ])
            ->values();

        return Inertia::render('Events/Show', [
            'event' => array_merge($this->cardPayload($event), [
                'description'      => $event->description,
                'location'         => $event->location,
                'photos_link'      => $event->photos_link,
                'audience'         => $event->audience ?? [],
                'audience_branch'  => $branch,
                'person_url'       => $event->person_id ? '/people/' . $event->person_id : null,
                'can_edit'         => $this->canManage($event),
            ]),
            'blessings' => $event->blessings->map(fn($b) => [
                'id'      => $b->id,
                'message' => $b->message,
                'user'    => $b->user?->name,
                'date'    => $b->created_at->format('d/m/Y'),
            ]),
            'reactions' => $reactions,
        ]);
    }

    public function edit(Event $event)
    {
        abort_unless($this->canManage($event), 403);

        return Inertia::render('Events/Edit', [
            'people' => $this->peopleOptions(),
            'event'  => [
                'id'                        => $event->id,
                'person_id'                 => $event->person_id,
                'type'                      => $event->type,
                'title'                     => $event->title,
                'event_date'                => optional($event->event_date)->format('Y-m-d'),
                'event_time'                => $event->event_time ? substr($event->event_time, 0, 5) : null,
                'hebrew_date'               => $event->hebrew_date,
                'location'                  => $event->location,
                'photos_link'               => $event->photos_link,
                'audience'                  => $event->audience ?? [],
                'audience_branch_person_id' => $event->audience_branch_person_id,
                'description'               => $event->description,
                'invitation_image_url'      => $event->invitation_image_url,
            ],
        ]);
    }

    public function update(Request $request, Event $event)
    {
        abort_unless($this->canManage($event), 403);

        $data = $this->validateEvent($request);

        if ($request->hasFile('invitation_image')) {
            if ($event->invitation_image) {
                Storage::disk('public')->delete($event->invitation_image);
            }
            $data['invitation_image'] = $request->file('invitation_image')->store('events', 'public');
        }
        unset($data['invitation_image_file']);

        $event->update($data);

        return redirect()->route('events.show', $event)->with('success', 'האירוע עודכן');
    }

    public function destroy(Event $event)
    {
        abort_unless($this->canManage($event), 403);

        if ($event->invitation_image) {
            Storage::disk('public')->delete($event->invitation_image);
        }
        $event->delete();

        return redirect()->route('events.index')->with('success', 'האירוע נמחק');
    }

    public function addBlessing(Request $request, Event $event)
    {
        $request->validate(['message' => 'required|string|max:1000']);

        Blessing::create([
            'event_id' => $event->id,
            'user_id'  => Auth::id(),
            'message'  => $request->message,
        ]);

        return redirect()->route('events.show', $event);
    }

    public function toggleReaction(Request $request, Event $event)
    {
        $request->validate(['emoji' => 'required|string|max:16']);

        $existing = EventReaction::where('event_id', $event->id)
            ->where('user_id', Auth::id())
            ->where('emoji', $request->emoji)
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            EventReaction::create([
                'event_id' => $event->id,
                'user_id'  => Auth::id(),
                'emoji'    => $request->emoji,
            ]);
        }

        return redirect()->route('events.show', $event);
    }

    // ─── Helpers ──────────────────────────────────────────────────

    private function cardPayload(Event $event): array
    {
        return [
            'id'               => $event->id,
            'title'            => $event->title,
            'type'             => $event->type,
            'person_id'        => $event->person_id,
            'person_name'      => $event->person?->full_name,
            'event_date'       => optional($event->event_date)->format('Y-m-d'),
            'event_time'       => $event->event_time ? substr($event->event_time, 0, 5) : null,
            'hebrew_date'      => $event->hebrew_date,
            'invitation_image' => $event->invitation_image_url,
            'url'              => route('events.show', $event->id),
        ];
    }

    private function peopleOptions()
    {
        return Person::select('id', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn($p) => ['id' => $p->id, 'label' => $p->full_name]);
    }

    private function validateEvent(Request $request): array
    {
        $validated = $request->validate([
            'person_id'                 => 'nullable|integer|exists:people,id',
            'type'                      => 'required|in:birth,bar_mitzvah,bat_mitzvah,wedding,death,other',
            'title'                     => 'required|string|max:255',
            'event_date'                => 'nullable|date',
            'event_time'                => 'nullable',
            'hebrew_date'               => 'nullable|string|max:50',
            'location'                  => 'nullable|string|max:255',
            'photos_link'               => 'nullable|url|max:1000',
            'audience'                  => 'nullable|array',
            'audience.*'                => 'string|max:100',
            'audience_branch_person_id' => 'nullable|integer|exists:people,id',
            'description'               => 'nullable|string',
            'invitation_image'          => 'nullable|image|max:10240',
        ]);

        return $validated;
    }

    private function canManage(Event $event): bool
    {
        $user = Auth::user();
        return $user && ($user->isAdmin() || $event->created_by === $user->id);
    }
}
