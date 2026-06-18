<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Person;
use App\Models\User;
use App\Mail\InvitationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class InvitationController extends Controller
{
    /**
     * Show the "invite someone" form
     */
    public function create()
    {
        $people = Person::select('id', 'first_name', 'last_name')
            ->orderBy('first_name')
            ->get()
            ->map(fn($p) => ['id' => $p->id, 'label' => $p->full_name]);

        return Inertia::render('Invite/Create', [
            'people' => $people,
        ]);
    }

    /**
     * Send the invitation email
     */
    public function store(Request $request)
    {
        $request->validate([
            'email'     => 'required|email|unique:users,email',
            'person_id' => 'nullable|exists:people,id',
        ]);

        // Don't send twice to same email
        $existing = Invitation::where('email', $request->email)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if ($existing) {
            return back()->withErrors(['email' => 'כבר נשלחה הזמנה לכתובת הזו']);
        }

        $invitation = Invitation::generate(
            email:     $request->email,
            invitedBy: $request->user()->id,
            personId:  $request->person_id,
        );

        Mail::to($request->email)->send(new InvitationMail($invitation));

        return back()->with('success', "הזמנה נשלחה ל-{$request->email}");
    }

    /**
     * Resend an invitation to a person who already has an email
     */
    public function resend(Person $person)
    {
        if (! $person->email) {
            return back()->withErrors(['email' => 'לדמות אין כתובת מייל']);
        }

        if (User::where('email', $person->email)->exists()) {
            return back()->withErrors(['email' => "{$person->email} כבר רשום/ה במערכת"]);
        }

        // Invalidate previous pending invitations for this email
        Invitation::where('email', $person->email)
            ->whereNull('used_at')
            ->update(['expires_at' => now()->subMinute()]);

        $invitation = Invitation::generate(
            email:     $person->email,
            invitedBy: auth()->id(),
            personId:  $person->id,
        );

        Mail::to($person->email)->send(new InvitationMail($invitation));

        return back()->with('success', "הזמנה נשלחה שוב ל-{$person->email}");
    }

    /**
     * Show the registration form for an invited user
     */
    public function show(string $token)
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        if (! $invitation->isValid()) {
            return Inertia::render('Invite/Expired');
        }

        $personName = $invitation->person?->full_name;

        return Inertia::render('Invite/Register', [
            'token'      => $token,
            'email'      => $invitation->email,
            'personName' => $personName,
        ]);
    }

    /**
     * Complete registration from invitation
     */
    public function register(Request $request, string $token)
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        if (! $invitation->isValid()) {
            return back()->withErrors(['token' => 'ההזמנה פגה תוקף']);
        }

        $request->validate([
            'name'     => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name'       => $request->name,
            'email'      => $invitation->email,
            'password'   => Hash::make($request->password),
            'role'       => 'member',
            'status'     => 'active',
            'person_id'  => $invitation->person_id,
            'invited_by' => $invitation->invited_by,
        ]);

        $invitation->markUsed();

        auth()->login($user);

        // If linked to a person — go straight to their card
        if ($user->person_id) {
            return redirect()->route('people.show', $user->person_id)
                ->with('welcome', true);
        }

        return redirect()->route('tree.index');
    }
}
