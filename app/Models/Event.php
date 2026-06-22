<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class Event extends Model
{
    protected static function boot(): void
    {
        parent::boot();

        // התראה מיידית למנויים כשנוסף אירוע — רק על יצירה דרך הממשק (משתמש מחובר),
        // כדי לא להציף מיילים בזמן seed/ייבוא.
        static::created(function (Event $event) {
            if (! Auth::check()) return;

            try {
                $recipients = \App\Models\User::where('notify_new_event', true)
                    ->where('status', 'active')
                    ->whereNotNull('email')
                    ->where('id', '!=', Auth::id())
                    ->get();

                foreach ($recipients as $user) {
                    Mail::to($user->email)->send(new \App\Mail\NewEventMail($event, $user->name));
                }
            } catch (\Throwable $e) {
                report($e);
            }
        });
    }

    protected $fillable = [
        'person_id', 'type', 'event_date', 'event_time', 'hebrew_date', 'title', 'description',
        'location', 'invitation_image', 'photos_link', 'audience', 'audience_branch_person_id',
        'created_by',
    ];

    protected $casts = [
        'event_date' => 'date',
        'audience'   => 'array',
    ];

    // ─── Accessors ────────────────────────────────────────────────

    public function getInvitationImageUrlAttribute(): ?string
    {
        return $this->invitation_image
            ? asset('storage/' . $this->invitation_image)
            : null;
    }

    // ─── Relationships ────────────────────────────────────────────

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function audienceBranch(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'audience_branch_person_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function blessings(): HasMany
    {
        return $this->hasMany(Blessing::class);
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(EventReaction::class);
    }

    public function mazalTovs(): HasMany
    {
        return $this->hasMany(MazalTov::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }
}
