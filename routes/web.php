<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\FiliereController;
use App\Http\Controllers\Admin\MatiereController;
use App\Http\Controllers\Admin\NiveauController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\ReferentielValuesController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/', '/login')->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');

    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('register.store');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->get('/referentiels/actifs', ReferentielValuesController::class)
    ->name('referentiels.actifs');

Route::middleware(['auth', 'can:academic_referentials.manage'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::resource('filieres', FiliereController::class)->except(['show']);
        Route::post('filieres/{filiere}/desactiver', [FiliereController::class, 'deactivate'])
            ->name('filieres.deactivate');
        Route::post('filieres/{filiere}/reactiver', [FiliereController::class, 'reactivate'])
            ->name('filieres.reactivate');

        Route::resource('niveaux', NiveauController::class)->except(['show']);
        Route::post('niveaux/{niveau}/desactiver', [NiveauController::class, 'deactivate'])
            ->name('niveaux.deactivate');
        Route::post('niveaux/{niveau}/reactiver', [NiveauController::class, 'reactivate'])
            ->name('niveaux.reactivate');

        Route::resource('matieres', MatiereController::class)->except(['show']);
        Route::post('matieres/{matiere}/desactiver', [MatiereController::class, 'deactivate'])
            ->name('matieres.deactivate');
        Route::post('matieres/{matiere}/reactiver', [MatiereController::class, 'reactivate'])
            ->name('matieres.reactivate');
        Route::get('matieres/{matiere}/associations', [MatiereController::class, 'associations'])
            ->name('matieres.associations');
        Route::post('matieres/{matiere}/associations', [MatiereController::class, 'storeAssociation'])
            ->name('matieres.associations.store');
        Route::delete(
            'matieres/{matiere}/associations/{association}',
            [MatiereController::class, 'destroyAssociation'],
        )->name('matieres.associations.destroy');
    });

Route::get('/dashboard', function () {
    return Inertia::render('AcademiaWelcome', [
        'title' => 'Espace Academia',
        'userName' => request()->user()?->name,
        'isAuthenticated' => true,
        'canManageUsers' => request()->user()?->can('users.manage') ?? false,
        'canManageReferentials' => request()->user()?->can('academic_referentials.manage') ?? false,
    ]);
})->middleware(['auth', 'can:dashboard.access'])->name('dashboard');

Route::middleware(['auth', 'can:users.manage'])
    ->prefix('admin/utilisateurs')
    ->name('admin.users.')
    ->group(function (): void {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::patch('/{user}/statut', [UserController::class, 'updateStatus'])->name('status');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
        Route::patch('/{user}/restaurer', [UserController::class, 'restore'])
            ->withTrashed()
            ->name('restore');
    });
