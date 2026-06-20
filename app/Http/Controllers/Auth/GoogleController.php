<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    /**
     * מפנה לעמוד ההתחברות של גוגל.
     */
    public function redirect()
    {
        if (blank(config('services.google.client_id'))) {
            return redirect()->route('login')->with('error', 'התחברות עם Google אינה מוגדרת עדיין.');
        }

        return Socialite::driver('google')->redirect();
    }

    /**
     * חוזר מגוגל — מאתר/מקשר משתמש לפי אימייל ומחבר.
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->with('error', 'ההתחברות עם Google נכשלה. נסו שוב.');
        }

        // קושר לפי google_id, ואם לא — לפי אימייל קיים (חיבור חשבון), אחרת יוצר חדש.
        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            if (! $user->google_id) {
                $user->update(['google_id' => $googleUser->getId()]);
            }
        } else {
            $user = User::create([
                'name'              => $googleUser->getName() ?: $googleUser->getNickname() ?: 'משתמש',
                'email'             => $googleUser->getEmail(),
                'google_id'         => $googleUser->getId(),
                'password'          => bcrypt(Str::random(32)),
                'email_verified_at' => now(),
                'role'              => 'member',
                'status'            => 'active',
            ]);
        }

        Auth::login($user, remember: true);

        return redirect()->intended(route('family-tree'));
    }
}
