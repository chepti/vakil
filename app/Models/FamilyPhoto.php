<?php

namespace App\Models;

use App\Models\Concerns\HasOriginUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FamilyPhoto extends Model
{
    use HasOriginUuid;

    protected $fillable = ['path', 'title', 'taken_year', 'uploaded_by', 'origin_uuid'];

    public function tags(): HasMany
    {
        return $this->hasMany(PhotoTag::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->path);
    }
}
