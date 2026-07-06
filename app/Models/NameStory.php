<?php

namespace App\Models;

use App\Models\Concerns\HasOriginUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NameStory extends Model
{
    use HasOriginUuid;

    protected $fillable = [
        'person_id', 'created_by', 'content', 'named_after_person_id', 'origin_uuid',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    /** הדמות בעץ שעל שמה נקרא/ה — למשל סבא רבא */
    public function namedAfter(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'named_after_person_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
