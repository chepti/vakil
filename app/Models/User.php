<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'google_id',
        'role', 'status', 'person_id', 'invited_by',
        'notify_monthly_digest', 'notify_new_person', 'notify_new_event',
        'digest_branch_person_id',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'     => 'datetime',
            'password'              => 'hashed',
            'notify_monthly_digest' => 'boolean',
            'notify_new_person'     => 'boolean',
            'notify_new_event'      => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // ברירת מחדל: "הענף שלי" = הדמות המקושרת למשתמש. מושלם ברגע שהקישור נוצר
        // (בהרשמה או בהזמנה), רק אם עוד לא נבחר ענף — כך ניקוי ידני לא יתבטל.
        $fill = function (User $u) {
            if ($u->person_id && ! $u->digest_branch_person_id) {
                $u->digest_branch_person_id = $u->person_id;
            }
        };
        static::creating($fill);
        static::updating(function (User $u) use ($fill) {
            if ($u->isDirty('person_id') && $u->getOriginal('person_id') === null) {
                $fill($u);
            }
        });
    }

    // ─── Helpers ──────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // ─── Relationships ────────────────────────────────────────────

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function digestBranch(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'digest_branch_person_id');
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function sentInvitations(): HasMany
    {
        return $this->hasMany(Invitation::class, 'invited_by');
    }

    public function blessings(): HasMany
    {
        return $this->hasMany(Blessing::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
}
