<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Person extends Model
{
    protected $fillable = [
        'first_name', 'last_name', 'gender',
        'birth_date_gregorian', 'birth_date_hebrew',
        'death_date_gregorian', 'death_date_hebrew',
        'is_deceased', 'profile_photo', 'bio',
        'current_occupation', 'city', 'created_by',
    ];

    protected $casts = [
        'birth_date_gregorian' => 'date',
        'death_date_gregorian' => 'date',
        'is_deceased' => 'boolean',
    ];

    // ─── Accessors ────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        return $this->profile_photo
            ? asset('storage/' . $this->profile_photo)
            : null;
    }

    // ─── Relationships ────────────────────────────────────────────

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function linkedUser(): HasMany
    {
        return $this->hasMany(User::class, 'person_id');
    }

    // ─── Family relationships ─────────────────────────────────────

    /** People this person is a parent of */
    public function children()
    {
        return $this->belongsToMany(
            Person::class,
            'relationships',
            'person1_id',
            'person2_id'
        )->wherePivot('type', 'parent_child');
    }

    /** This person's parents */
    public function parents()
    {
        return $this->belongsToMany(
            Person::class,
            'relationships',
            'person2_id',
            'person1_id'
        )->wherePivot('type', 'parent_child');
    }

    /** Spouses */
    public function spouses()
    {
        return Person::whereHas('relationships', function ($q) {
            $q->where(function ($inner) {
                $inner->where('person1_id', $this->id)
                      ->orWhere('person2_id', $this->id);
            })->where('type', 'spouse');
        })->where('id', '!=', $this->id);
    }

    public function relationships(): HasMany
    {
        return $this->hasMany(Relationship::class, 'person1_id');
    }
}
