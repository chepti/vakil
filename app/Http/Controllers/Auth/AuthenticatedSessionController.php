<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Person;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /** אימייל חשבון האורח המשותף באתר ההדגמה בלבד */
    private const DEMO_GUEST_EMAIL = 'guest@example.com';

    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        // באתר ההדגמה — לפני כל ניסיון כניסה עם חשבון האורח, לתקן/ליצור אותו מחדש.
        // מבקרים עלולים לשנות שם/אימייל/סיסמה בפרופיל המשותף ולשבור את הכניסה לכולם.
        if (config('app.demo') && $request->input('email') === self::DEMO_GUEST_EMAIL) {
            $mainPersonId = Person::where('is_main_person', true)->value('id') ?? Person::min('id');
            User::updateOrCreate(
                ['email' => self::DEMO_GUEST_EMAIL],
                [
                    'name'              => 'אורח/ת בהדגמה',
                    'password'          => Hash::make('demo1234'),
                    'role'              => 'member',
                    'status'            => 'active',
                    'email_verified_at' => now(),
                    'person_id'         => $mainPersonId,
                ]
            );
        }

        $request->authenticate();

        $request->session()->regenerate();

        // באתר ההדגמה — נוחתים ישר על העץ המעוגל, לא על רשימת בני המשפחה
        if (config('app.demo')) {
            return redirect()->intended(route('family-tree', absolute: false));
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
