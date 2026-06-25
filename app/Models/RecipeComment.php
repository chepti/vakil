<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecipeComment extends Model
{
    protected $fillable = ['recipe_id', 'user_id', 'parent_id', 'content'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(RecipeComment::class, 'parent_id')->with('user')->oldest();
    }
}
