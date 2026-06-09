<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Photo extends Model
{
    protected $fillable = [
        'person_id', 'event_id', 'thumb_path', 'original_path',
        'caption', 'taken_at', 'uploaded_by',
    ];

    protected $casts = [
        'taken_at' => 'date',
    ];

    protected $appends = ['thumb_url'];

    public function getThumbUrlAttribute(): string
    {
        return asset('storage/' . $this->thumb_path);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
