<?php

use App\Http\Controllers\FamilyTreeController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PersonController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\InvitationController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// דף הבית — מפנה לעץ או ל-onboarding
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('people.index');
    }
    return Inertia::render('Welcome', [
        'canLogin'      => Route::has('login'),
        'canRegister'   => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion'    => PHP_VERSION,
    ]);
});

// Onboarding — רק כשה-DB ריק
Route::get('/onboarding', [OnboardingController::class, 'show'])
    ->middleware(['auth'])
    ->name('onboarding');
Route::post('/onboarding', [OnboardingController::class, 'store'])
    ->middleware(['auth'])
    ->name('onboarding.store');

// Dashboard — redirect לעץ
Route::get('/dashboard', function () {
    return redirect()->route('people.index');
})->middleware(['auth', 'verified'])->name('dashboard');

// People CRUD + Family Tree
Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('people', PersonController::class);
    Route::post('/people/{person}/spouse', [PersonController::class, 'addSpouse'])->name('people.spouse');
    Route::get('/family-tree', [FamilyTreeController::class, 'index'])->name('family-tree');
});

// Profile
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Invitations
Route::get('/invite/{token}', [InvitationController::class, 'accept'])->name('invitation.accept');
Route::post('/invite/{token}', [InvitationController::class, 'register'])->name('invitation.register');
Route::middleware(['auth'])->post('/invitations', [InvitationController::class, 'send'])->name('invitation.send');

require __DIR__.'/auth.php';
