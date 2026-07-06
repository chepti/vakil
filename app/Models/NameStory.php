<?php

namespace App\Models;

use App\Models\Concerns\HasOriginUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NameStory extends Model
{
    use HasOriginUuid;

    protected $fillable = [
        'person_id', 'created_by', 'content', 'origin_uuid',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
