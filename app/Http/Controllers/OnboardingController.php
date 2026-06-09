<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\Relationship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class OnboardingController extends Controller
{
    public function show()
    {
        if (Person::exists()) {
            return redirect()->route('people.index');
        }
        return Inertia::render('Onboarding');
    }

    public function store(Request $request)
    {
        if (Person::exists()) {
            return redirect()->route('people.index');
        }

        $data = $request->validate([
            'first_name'           => 'required|string|max:100',
            'last_name'            => 'required|string|max:100',
            'gender'               => 'required|in:male,female',
            'birth_date_gregorian' => 'nullable|date',
            'current_occupation'   => 'nullable|string|max:255',
            'city'                 => 'nullable|string|max:100',
            'parents'              => 'nullable|array|max:2',
            'parents.*.first_name' => 'required_with:parents|string|max:100',
            'parents.*.last_name'  => 'nullable|string|max:100',
            'parents.*.gender'     => 'required_with:parents|in:male,female',
            'siblings'             => 'nullable|array',
            'siblings.*.first_name' => 'required_with:siblings|string|max:100',
            'siblings.*.last_name'  => 'nullable|string|max:100',
            'siblings.*.gender'     => 'required_with:siblings|in:male,female',
        ]);

        // יצירת הדמות הראשית (המשתמש עצמו)
        $me = Person::create([
            'first_name'           => $data['first_name'],
            'last_name'            => $data['last_name'],
            'gender'               => $data['gender'],
            'birth_date_gregorian' => $data['birth_date_gregorian'] ?? null,
            'current_occupation'   => $data['current_occupation'] ?? null,
            'city'                 => $data['city'] ?? null,
            'created_by'           => Auth::id(),
        ]);

        // קישור הדמות למשתמש
        Auth::user()->update(['person_id' => $me->id]);

        $parentIds = [];

        // יצירת הורים
        foreach ($data['parents'] ?? [] as $parentData) {
            if (empty($parentData['first_name'])) continue;

            $parent = Person::create([
                'first_name' => $parentData['first_name'],
                'last_name'  => $parentData['last_name'] ?? $data['last_name'],
                'gender'     => $parentData['gender'],
                'created_by' => Auth::id(),
            ]);

            Relationship::create([
                'person1_id' => $parent->id,
                'person2_id' => $me->id,
                'type'       => 'parent_child',
            ]);

            $parentIds[] = $parent->id;
        }

        // יצירת אחים (ומיקרים להורים אותם הורים)
        foreach ($data['siblings'] ?? [] as $siblingData) {
            if (empty($siblingData['first_name'])) continue;

            $sibling = Person::create([
                'first_name' => $siblingData['first_name'],
                'last_name'  => $siblingData['last_name'] ?? $data['last_name'],
                'gender'     => $siblingData['gender'],
                'created_by' => Auth::id(),
            ]);

            // קישור לאותם הורים
            foreach ($parentIds as $parentId) {
                Relationship::create([
                    'person1_id' => $parentId,
                    'person2_id' => $sibling->id,
                    'type'       => 'parent_child',
                ]);
            }
        }

        return redirect()->route('people.index')->with('success', 'ברוכים הבאים! העץ שלך מוכן 🌳');
    }
}
