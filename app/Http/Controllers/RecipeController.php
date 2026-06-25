<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\Recipe;
use App\Models\RecipeAdaptation;
use App\Models\RecipeComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class RecipeController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->query('category');

        $query = Recipe::with(['person', 'createdBy'])
            ->withCount('comments')
            ->latest();

        if ($category && $category !== 'all') {
            $query->where('category', $category);
        }

        $recipes = $query->get()->map(fn ($r) => [
            'id'           => $r->id,
            'title'        => $r->title,
            'category'     => $r->category,
            'quantity'     => $r->quantity,
            'is_favorite'  => $r->is_favorite,
            'is_gluten_free' => $r->is_gluten_free,
            'image_url'    => $r->image_url,
            'comments_count' => $r->comments_count,
            'person_name'  => $r->person?->full_name,
            'person_context' => $r->person?->ancestralContext(),
            'owner_text'   => $r->owner_text,
            'created_by_name' => $r->createdBy->name,
            'can_edit'     => Auth::user()->role === 'admin' || $r->created_by === Auth::id(),
        ]);

        return Inertia::render('Recipes/Index', [
            'recipes'         => $recipes,
            'currentCategory' => $category ?? 'all',
        ]);
    }

    public function create()
    {
        return Inertia::render('Recipes/Form', [
            'recipe'  => null,
            'people'  => Person::orderBy('first_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'          => 'required|string|max:255',
            'category'       => 'required|in:soups,mains,salads,pastries,desserts,drinks,other',
            'quantity'       => 'nullable|string|max:100',
            'ingredients'    => 'required|string',
            'preparation'    => 'required|string',
            'is_favorite'    => 'boolean',
            'is_gluten_free' => 'boolean',
            'person_id'      => 'nullable|exists:people,id',
            'owner_text'     => 'nullable|string|max:255',
            'image'          => 'nullable|image|max:8192',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('recipes', 'public');
        }

        Recipe::create(array_merge($data, [
            'created_by' => Auth::id(),
            'image'      => $imagePath,
        ]));

        return redirect()->route('recipes.index')->with('success', 'המתכון נוסף בהצלחה!');
    }

    public function show(Recipe $recipe)
    {
        $recipe->load(['person', 'createdBy', 'comments.user', 'comments.replies.user', 'adaptations.user']);

        $myAdaptation = RecipeAdaptation::where('recipe_id', $recipe->id)
            ->where('user_id', Auth::id())
            ->first();

        return Inertia::render('Recipes/Show', [
            'recipe' => [
                'id'             => $recipe->id,
                'title'          => $recipe->title,
                'category'       => $recipe->category,
                'quantity'       => $recipe->quantity,
                'ingredients'    => $recipe->ingredients,
                'preparation'    => $recipe->preparation,
                'is_favorite'    => $recipe->is_favorite,
                'is_gluten_free' => $recipe->is_gluten_free,
                'image_url'      => $recipe->image_url,
                'owner_text'     => $recipe->owner_text,
                'created_by_name' => $recipe->createdBy->name,
                'person'         => $recipe->person ? [
                    'id'      => $recipe->person->id,
                    'name'    => $recipe->person->full_name,
                    'context' => $recipe->person->ancestralContext(),
                ] : null,
                'comments' => $recipe->comments->map(fn ($c) => [
                    'id'         => $c->id,
                    'content'    => $c->content,
                    'user_name'  => $c->user->name,
                    'user_id'    => $c->user_id,
                    'created_at' => $c->created_at->format('d/m/Y H:i'),
                    'can_delete' => Auth::user()->role === 'admin' || $c->user_id === Auth::id(),
                    'replies'    => $c->replies->map(fn ($r) => [
                        'id'         => $r->id,
                        'content'    => $r->content,
                        'user_name'  => $r->user->name,
                        'user_id'    => $r->user_id,
                        'created_at' => $r->created_at->format('d/m/Y H:i'),
                        'can_delete' => Auth::user()->role === 'admin' || $r->user_id === Auth::id(),
                    ]),
                ]),
                'adaptations' => $recipe->adaptations->map(fn ($a) => [
                    'id'        => $a->id,
                    'content'   => $a->content,
                    'user_name' => $a->user->name,
                    'user_id'   => $a->user_id,
                    'is_mine'   => $a->user_id === Auth::id(),
                ]),
                'can_edit' => Auth::user()->role === 'admin' || $recipe->created_by === Auth::id(),
            ],
            'myAdaptation' => $myAdaptation ? $myAdaptation->content : null,
        ]);
    }

    public function edit(Recipe $recipe)
    {
        $this->authorizeEdit($recipe);

        return Inertia::render('Recipes/Form', [
            'recipe' => [
                'id'             => $recipe->id,
                'title'          => $recipe->title,
                'category'       => $recipe->category,
                'quantity'       => $recipe->quantity,
                'ingredients'    => $recipe->ingredients,
                'preparation'    => $recipe->preparation,
                'is_favorite'    => $recipe->is_favorite,
                'is_gluten_free' => $recipe->is_gluten_free,
                'image_url'      => $recipe->image_url,
                'person_id'      => $recipe->person_id,
            ],
            'people' => Person::orderBy('first_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function update(Request $request, Recipe $recipe)
    {
        $this->authorizeEdit($recipe);

        $data = $request->validate([
            'title'          => 'required|string|max:255',
            'category'       => 'required|in:soups,mains,salads,pastries,desserts,drinks,other',
            'quantity'       => 'nullable|string|max:100',
            'ingredients'    => 'required|string',
            'preparation'    => 'required|string',
            'is_favorite'    => 'boolean',
            'is_gluten_free' => 'boolean',
            'person_id'      => 'nullable|exists:people,id',
            'image'          => 'nullable|image|max:8192',
        ]);

        if ($request->hasFile('image')) {
            if ($recipe->image) {
                Storage::disk('public')->delete($recipe->image);
            }
            $data['image'] = $request->file('image')->store('recipes', 'public');
        } else {
            unset($data['image']);
        }

        $recipe->update($data);

        return redirect()->route('recipes.show', $recipe)->with('success', 'המתכון עודכן!');
    }

    public function destroy(Recipe $recipe)
    {
        $this->authorizeEdit($recipe);

        if ($recipe->image) {
            Storage::disk('public')->delete($recipe->image);
        }

        $recipe->delete();

        return redirect()->route('recipes.index')->with('success', 'המתכון נמחק.');
    }

    public function addComment(Request $request, Recipe $recipe)
    {
        $data = $request->validate([
            'content'   => 'required|string|max:2000',
            'parent_id' => 'nullable|exists:recipe_comments,id',
        ]);

        RecipeComment::create([
            'recipe_id' => $recipe->id,
            'user_id'   => Auth::id(),
            'parent_id' => $data['parent_id'] ?? null,
            'content'   => $data['content'],
        ]);

        return back()->with('success', 'התגובה נוספה!');
    }

    public function deleteComment(RecipeComment $comment)
    {
        if (Auth::user()->role !== 'admin' && $comment->user_id !== Auth::id()) {
            abort(403);
        }

        $comment->delete();

        return back()->with('success', 'התגובה נמחקה.');
    }

    public function saveAdaptation(Request $request, Recipe $recipe)
    {
        $data = $request->validate(['content' => 'required|string|max:3000']);

        RecipeAdaptation::updateOrCreate(
            ['recipe_id' => $recipe->id, 'user_id' => Auth::id()],
            ['content' => $data['content']]
        );

        return back()->with('success', 'ההתאמה נשמרה!');
    }

    private function authorizeEdit(Recipe $recipe): void
    {
        if (Auth::user()->role !== 'admin' && $recipe->created_by !== Auth::id()) {
            abort(403);
        }
    }
}
