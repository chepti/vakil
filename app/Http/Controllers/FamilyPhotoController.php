<?php

namespace App\Http\Controllers;

use App\Models\FamilyPhoto;
use App\Models\Person;
use App\Models\PhotoTag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class FamilyPhotoController extends Controller
{
    public function index()
    {
        $photos = FamilyPhoto::with('tags')
            ->latest()
            ->get()
            ->map(fn($p) => [
                'id'          => $p->id,
                'url'         => $p->url,
                'title'       => $p->title,
                'tags_count'  => $p->tags->count(),
                'uploaded_by' => $p->uploaded_by,
            ]);

        return Inertia::render('FamilyPhotos/Index', ['photos' => $photos]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'photo' => 'required|image|max:10240',
            'title' => 'nullable|string|max:255',
        ]);

        $path = $request->file('photo')->store('family-photos', 'public');

        $photo = FamilyPhoto::create([
            'path'        => $path,
            'title'       => $request->title,
            'uploaded_by' => Auth::id(),
        ]);

        return redirect()->route('family-photos.show', $photo)->with('success', 'התמונה הועלתה');
    }

    public function show(FamilyPhoto $familyPhoto)
    {
        $familyPhoto->load('tags.person');

        $allPeople = Person::with('parents')
            ->select('id', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn($p) => [
                'id'    => $p->id,
                'label' => $p->full_name,
                'hint'  => $p->parents->isNotEmpty()
                    ? 'של ' . $p->parents->first()->first_name
                    : null,
            ]);

        return Inertia::render('FamilyPhotos/Show', [
            'photo' => [
                'id'          => $familyPhoto->id,
                'url'         => $familyPhoto->url,
                'path'        => $familyPhoto->path,
                'title'       => $familyPhoto->title,
                'uploaded_by' => $familyPhoto->uploaded_by,
                'tags'        => $familyPhoto->tags->map(fn($t) => [
                    'id'          => $t->id,
                    'person_id'   => $t->person_id,
                    'person_name' => $t->person->full_name,
                    'person_url'  => '/people/' . $t->person_id,
                    'x_percent'   => (float) $t->x_percent,
                    'y_percent'   => (float) $t->y_percent,
                    'w_percent'   => (float) ($t->w_percent ?? 10),
                    'h_percent'   => (float) ($t->h_percent ?? 10),
                ]),
            ],
            'allPeople' => $allPeople,
        ]);
    }

    public function addTag(Request $request, FamilyPhoto $familyPhoto): JsonResponse
    {
        $data = $request->validate([
            'person_id' => 'required|integer|exists:people,id',
            'x_percent' => 'required|numeric|min:0|max:100',
            'y_percent' => 'required|numeric|min:0|max:100',
            'w_percent' => 'nullable|numeric|min:1|max:100',
            'h_percent' => 'nullable|numeric|min:1|max:100',
        ]);

        $tag = PhotoTag::create([
            'family_photo_id' => $familyPhoto->id,
            'person_id'       => $data['person_id'],
            'x_percent'       => $data['x_percent'],
            'y_percent'       => $data['y_percent'],
            'w_percent'       => $data['w_percent'] ?? 10,
            'h_percent'       => $data['h_percent'] ?? 10,
        ]);

        $tag->load('person');

        return response()->json([
            'id'          => $tag->id,
            'person_id'   => $tag->person_id,
            'person_name' => $tag->person->full_name,
            'person_url'  => '/people/' . $tag->person_id,
            'x_percent'   => (float) $tag->x_percent,
            'y_percent'   => (float) $tag->y_percent,
            'w_percent'   => (float) $tag->w_percent,
            'h_percent'   => (float) $tag->h_percent,
        ]);
    }

    public function removeTag(FamilyPhoto $familyPhoto, PhotoTag $photoTag): JsonResponse
    {
        $photoTag->delete();
        return response()->json(['success' => true]);
    }

    public function update(Request $request, FamilyPhoto $familyPhoto): JsonResponse
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $familyPhoto->uploaded_by !== $user->id) {
            abort(403);
        }

        $data = $request->validate(['title' => 'nullable|string|max:255']);
        $familyPhoto->update(['title' => $data['title'] ?? null]);

        return response()->json(['title' => $familyPhoto->title]);
    }

    public function destroy(FamilyPhoto $familyPhoto)
    {
        $user = Auth::user();
        if (!$user->isAdmin() && $familyPhoto->uploaded_by !== $user->id) {
            abort(403);
        }

        Storage::disk('public')->delete($familyPhoto->path);
        $familyPhoto->delete();
        return redirect()->route('family-photos.index')->with('success', 'התמונה נמחקה');
    }
}
