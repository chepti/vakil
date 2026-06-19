<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\FamilyPhotoController;
use App\Http\Controllers\FamilyTreeController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\StatsController;
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
    Route::post('/people/{person}/photo', [PersonController::class, 'uploadPhoto'])->name('people.photo');
    Route::post('/people/{person}/parent', [PersonController::class, 'addParent'])->name('people.parent');
    Route::post('/people/{person}/sibling', [PersonController::class, 'addSibling'])->name('people.sibling');
    Route::post('/people/{person}/reorder-children', [PersonController::class, 'reorderChildren'])->name('people.reorder-children');
    Route::post('/people/{person}/resend-invite', [InvitationController::class, 'resend'])->name('people.resend-invite');

    // Family photos
    Route::get('/family-photos', [FamilyPhotoController::class, 'index'])->name('family-photos.index');
    Route::post('/family-photos', [FamilyPhotoController::class, 'store'])->name('family-photos.store');
    Route::get('/family-photos/{familyPhoto}', [FamilyPhotoController::class, 'show'])->name('family-photos.show');
    Route::delete('/family-photos/{familyPhoto}', [FamilyPhotoController::class, 'destroy'])->name('family-photos.destroy');
    Route::post('/family-photos/{familyPhoto}/tags', [FamilyPhotoController::class, 'addTag'])->name('family-photos.tag');
    Route::delete('/family-photos/{familyPhoto}/tags/{photoTag}', [FamilyPhotoController::class, 'removeTag'])->name('family-photos.tag.remove');

    Route::get('/family-tree', [FamilyTreeController::class, 'index'])->name('family-tree');

    // אירועים + לוח שנה
    Route::resource('events', EventController::class);
    Route::post('/events/{event}/blessings', [EventController::class, 'addBlessing'])->name('events.blessings');
    Route::post('/events/{event}/reactions', [EventController::class, 'toggleReaction'])->name('events.reactions');

    // סטטיסטיקות — פתוח לכל המשתמשים
    Route::get('/stats', [StatsController::class, 'index'])->name('stats');

    // הדפסה ל-PDF — תצוגת ענף ידידותית להדפסה
    Route::get('/print/tree', [FamilyTreeController::class, 'printable'])->name('print.tree');

    // פאנל ניהול (אדמין בלבד — נאכף בקונטרולר)
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::post('/users/{user}/toggle-role', [AdminController::class, 'toggleRole'])->name('users.toggle-role');
        Route::delete('/users/{user}', [AdminController::class, 'deleteUser'])->name('users.delete');
        Route::post('/documents', [AdminController::class, 'uploadDocument'])->name('documents.upload');
        Route::delete('/documents/{document}', [AdminController::class, 'deleteDocument'])->name('documents.delete');
        Route::get('/export/family', [AdminController::class, 'exportFamily'])->name('export.family');
        Route::get('/export/users', [AdminController::class, 'exportUsers'])->name('export.users');
        Route::get('/export/birthdays', [AdminController::class, 'exportBirthdays'])->name('export.birthdays');

        // ניהול הזמנות
        Route::post('/invitations/{invitation}/extend', [InvitationController::class, 'extend'])->name('invitations.extend');
        Route::delete('/invitations/{invitation}', [InvitationController::class, 'destroy'])->name('invitations.delete');
    });

    // משחק — "הדרך אל סבתא ואקיל"
    Route::get('/game', [GameController::class, 'index'])->name('game');
    Route::get('/api/game/round', [GameController::class, 'round'])->name('game.round');

    // JSON API — inline tree editing (no page reload)
    Route::get('/api/family-tree', [FamilyTreeController::class, 'apiData'])->name('api.tree');
    Route::post('/api/family-tree/person', [FamilyTreeController::class, 'apiSavePerson'])->name('api.tree.save');
    Route::delete('/api/family-tree/person/{id}', [FamilyTreeController::class, 'apiDeletePerson'])->name('api.tree.delete');
    Route::post('/api/family-tree/set-main/{id}', [FamilyTreeController::class, 'apiSetMain'])->name('api.tree.main');
    Route::put('/api/family-tree/person/{id}/details', [FamilyTreeController::class, 'apiUpdateDetails'])->name('api.tree.details');
});

// Profile
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Invitations
Route::get('/invite/{token}', [InvitationController::class, 'show'])->name('invitation.accept');
Route::post('/invite/{token}', [InvitationController::class, 'register'])->name('invitation.register');
Route::middleware(['auth'])->post('/invitations', [InvitationController::class, 'send'])->name('invitation.send');

require __DIR__.'/auth.php';
