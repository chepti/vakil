<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhotoTag extends Model
{
    protected $fillable = ['family_photo_id', 'person_id', 'x_percent', 'y_percent', 'w_percent', 'h_percent'];

    public function familyPhoto(): BelongsTo
    {
        return $this->belongsTo(FamilyPhoto::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
