<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    protected $fillable = [
        'title', 'person_id', 'created_by', 'category',
        'quantity', 'ingredients', 'preparation',
        'is_favorite', 'is_gluten_free', 'image',
    ];

    protected $casts = [
        'is_favorite'    => 'boolean',
        'is_gluten_free' => 'boolean',
    ];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(RecipeComment::class)->whereNull('parent_id')->with('user', 'replies.user')->latest();
    }

    public function adaptations(): HasMany
    {
        return $this->hasMany(RecipeAdaptation::class)->with('user');
    }
}
