<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OccasionBlessing extends Model
{
    protected $fillable = ['person_id', 'user_id', 'occasion', 'year', 'kind'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function person(): BelongsTo { return $this->belongsTo(Person::class); }
}
