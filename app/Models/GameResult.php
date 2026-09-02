<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * תוצאת סבב משחק אחד של משתמש — הבסיס לניקוד האישי המצטבר
 * ולרשימת "את מי כבר זיהיתי".
 */
class GameResult extends Model
{
    protected $fillable = [
        'user_id', 'person_id', 'points',
        'first_name_ok', 'last_name_ok',
        'links_total', 'links_ok', 'hints_used',
    ];

    protected $casts = [
        'first_name_ok' => 'boolean',
        'last_name_ok'  => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }
}
