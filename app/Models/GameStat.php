<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameStat extends Model
{
    protected $fillable = [
        'person_id', 'correct_guesses', 'points',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
