<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Person;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $people = Person::orderBy('first_name')->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name'])
            ->map(fn ($p) => ['id' => $p->id, 'name' => "{$p->first_name} {$p->last_name}"])
            ->all();

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status'          => session('status'),
            'people'          => $people,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Update the user's email notification preferences.
     */
    public function updateNotifications(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'notify_monthly_digest'   => ['required', 'boolean'],
            'notify_new_person'       => ['required', 'boolean'],
            'notify_new_event'        => ['required', 'boolean'],
            'digest_branch_person_id' => ['nullable', 'integer', 'exists:people,id'],
        ]);

        $request->user()->fill($data)->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
