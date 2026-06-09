<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Event extends Model
{
    protected $fillable = [
        'person_id', 'type', 'event_date', 'hebrew_date', 'title', 'description',
    ];

    protected $casts = [
        'event_date' => 'date',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function blessings(): HasMany
    {
        return $this->hasMany(Blessing::class);
    }

    public function mazalTovs(): HasMany
    {
        return $this->hasMany(MazalTov::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }
}
