<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Relationship extends Model
{
    protected $fillable = ['person1_id', 'person2_id', 'type', 'sort_order', 'marriage_date_gregorian', 'marriage_date_hebrew', 'is_former', 'is_explicit'];

    protected $casts = [
        'marriage_date_gregorian' => 'date',
        'is_former'               => 'boolean',
        'is_explicit'             => 'boolean',
    ];

    public function person1(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person1_id');
    }

    public function person2(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'person2_id');
    }
}
