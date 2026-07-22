<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\Person;
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
     * חוזר מגוגל — מחבר רק דמויות שקיימות בעץ המשפחה.
     *
     * מדיניות אבטחה (כניסה למוזמנים בלבד):
     *   1. משתמש קיים (לפי google_id או אימייל) — מתחבר אם הוא פעיל.
     *   2. אין משתמש עדיין — נוצר אוטומטית רק אם האימייל שייך לעץ:
     *      הזמנה תקפה, או דמות בעץ עם אותו אימייל. אחרת — נחסם.
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->with('error', 'ההתחברות עם Google נכשלה. נסו שוב.');
        }

        $googleId = $googleUser->getId();
        $email    = $googleUser->getEmail();

        if (blank($email)) {
            return redirect()->route('login')->with('error', 'לא התקבל אימייל מחשבון Google. נסו שוב.');
        }

        $user = User::where('google_id', $googleId)->first()
            ?? User::where('email', $email)->first();

        if ($user) {
            // חשבון קיים אך מושבת — לא לאפשר כניסה.
            if ($user->status !== 'active') {
                return redirect()->route('login')->with('error', 'החשבון הזה אינו פעיל. פנו למנהל/ת האתר.');
            }

            // קישור google_id בפעם הראשונה שמשתמש קיים מתחבר עם גוגל.
            if (! $user->google_id) {
                $user->update(['google_id' => $googleId]);
            }
        } else {
            // אין משתמש — מותר ליצור רק אם האימייל שייך לעץ (הזמנה תקפה או דמות עם אותו אימייל).
            $invitation = Invitation::where('email', $email)
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->latest('id')
                ->first();

            $treePerson = Person::where('email', $email)->first();

            if (! $invitation && ! $treePerson) {
                return redirect()->route('login')->with('error', 'החשבון הזה אינו מופיע בעץ המשפחה. הכניסה למוזמנים בלבד — פנו למנהל/ת האתר.');
            }

            $user = User::create([
                'name'              => $googleUser->getName() ?: $googleUser->getNickname() ?: $email,
                'email'             => $email,
                'google_id'         => $googleId,
                'password'          => bcrypt(Str::random(40)),
                'email_verified_at' => now(),
                'role'              => 'member',
                'status'            => 'active',
                'person_id'         => $invitation->person_id ?? $treePerson?->id,
                'invited_by'        => $invitation->invited_by ?? null,
            ]);

            $invitation?->markUsed();
        }

        Auth::login($user, remember: true);

        return redirect()->intended(route('family-tree'));
    }
}
