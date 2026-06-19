<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Invitation extends Model
{
    protected $fillable = [
        'email', 'token', 'invited_by', 'person_id', 'expires_at', 'used_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
    ];

    // ─── Factory ──────────────────────────────────────────────────

    public static function generate(string $email, int $invitedBy, ?int $personId = null): self
    {
        return self::create([
            'email'      => $email,
            'token'      => Str::random(64),
            'invited_by' => $invitedBy,
            'person_id'  => $personId,
            'expires_at' => now()->addYears(100),
        ]);
    }

    // ─── Helpers ──────────────────────────────────────────────────

    public function isValid(): bool
    {
        return is_null($this->used_at) && $this->expires_at->isFuture();
    }

    public function markUsed(): void
    {
        $this->update(['used_at' => now()]);
    }

    // ─── Relationships ────────────────────────────────────────────

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
