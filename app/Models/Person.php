<?php

namespace App\Models;

use App\Mail\InvitationMail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class Person extends Model
{
    protected static function boot(): void
    {
        parent::boot();

        static::updated(function (Person $person) {
            $newEmail = $person->email;
            $oldEmail = $person->getOriginal('email');

            if (! $newEmail || $newEmail === $oldEmail) return;

            $alreadyUser    = \App\Models\User::where('email', $newEmail)->exists();
            $alreadyInvited = Invitation::where('email', $newEmail)
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->exists();

            if ($alreadyUser || $alreadyInvited) return;

            $invitation = Invitation::generate(
                email:     $newEmail,
                invitedBy: Auth::id() ?? 1,
                personId:  $person->id,
            );
            Mail::to($newEmail)->send(new InvitationMail($invitation));
        });

        // התראה מיידית למנויים כשנוספת דמות חדשה — רק על יצירה דרך הממשק (משתמש מחובר),
        // כדי לא להציף מיילים בזמן seed/ייבוא.
        static::created(function (Person $person) {
            if (! Auth::check()) return;

            try {
                $recipients = \App\Models\User::where('notify_new_person', true)
                    ->where('status', 'active')
                    ->whereNotNull('email')
                    ->where('id', '!=', Auth::id())
                    ->get();

                foreach ($recipients as $user) {
                    Mail::to($user->email)->send(new \App\Mail\NewPersonMail($person, $user->name));
                }
            } catch (\Throwable $e) {
                report($e);
            }
        });
    }

    protected $fillable = [
        'first_name', 'last_name', 'maiden_name', 'gender',
        'birth_date_gregorian', 'birth_date_hebrew',
        'death_date_gregorian', 'death_date_hebrew',
        'is_deceased', 'is_main_person', 'profile_photo', 'bio',
        'current_occupation', 'city', 'email', 'phone', 'created_by',
    ];

    protected $casts = [
        'birth_date_gregorian' => 'date',
        'death_date_gregorian' => 'date',
        'is_deceased'     => 'boolean',
        'is_main_person'  => 'boolean',
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

    public function photoTags(): HasMany
    {
        return $this->hasMany(PhotoTag::class);
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
        )->wherePivot('type', 'parent_child')
         ->withPivot('sort_order')
         ->orderByRaw('COALESCE(relationships.sort_order, 999) ASC')
         ->orderBy('birth_date_gregorian');
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
        $id = $this->id;
        $spouseIds = Relationship::where('type', 'spouse')
            ->where(fn($q) => $q->where('person1_id', $id)->orWhere('person2_id', $id))
            ->get()
            ->map(fn($r) => $r->person1_id == $id ? $r->person2_id : $r->person1_id)
            ->unique();

        return Person::whereIn('id', $spouseIds);
    }

    public function relationships(): HasMany
    {
        return $this->hasMany(Relationship::class, 'person1_id');
    }

    // ─── Helpers ──────────────────────────────────────────────────

    /**
     * מחזיר "של X של Y" — הנתיב הקצר ביותר מהדמות עד (לא כולל) is_main_person.
     * מעדיף את ה"ענף המשפחתי" — הצד שמוביל לשורש, לא צד של חתנים/כלות שאינם בעץ.
     */
    public function ancestralContext(int $maxLevels = 5): string
    {
        static $mainPersonId;
        if (! isset($mainPersonId)) {
            $mainPersonId = static::where('is_main_person', true)->value('id');
        }

        if (! $mainPersonId) return '';

        $path = $this->pathToRoot($this->id, (int) $mainPersonId, $maxLevels, []);

        if ($path === null || count($path) === 0) return '';
        return 'של ' . implode(' של ', $path);
    }

    /**
     * DFS — מחזיר מערך של שמות-פרטיים מה"הורה הישיר" עד לפני is_main_person.
     * מחזיר [] כשמצא ישירות את השורש, null כשאין נתיב.
     */
    private function pathToRoot(int $fromId, int $rootId, int $depth, array $visited): ?array
    {
        if ($depth <= 0) return null;

        $parentIds = Relationship::where('type', 'parent_child')
            ->where('person2_id', $fromId)
            ->pluck('person1_id');

        foreach ($parentIds as $parentId) {
            if (isset($visited[$parentId])) continue;

            // הגענו לשורש — לא כוללים את שמו
            if ($parentId == $rootId) return [];

            $parent = static::find($parentId);
            if (! $parent) continue;
            if ($parent->is_main_person) return [];

            $sub = $this->pathToRoot($parentId, $rootId, $depth - 1, $visited + [$parentId => true]);
            if ($sub !== null) {
                return array_merge([$parent->first_name], $sub);
            }
        }

        return null;
    }

    /** כל מזהי הצאצאים (רקורסיבי) — לקהל יעד מסוג "ענף: צאצאי X" */
    public function descendantIds(): array
    {
        $collected = [];
        $stack = $this->children()->pluck('people.id')->all();

        while ($stack) {
            $id = array_pop($stack);
            if (isset($collected[$id])) continue;
            $collected[$id] = true;

            $childIds = Relationship::where('type', 'parent_child')
                ->where('person1_id', $id)
                ->pluck('person2_id')
                ->all();
            foreach ($childIds as $cid) $stack[] = $cid;
        }

        return array_keys($collected);
    }
}
