<?php

use App\Actions\Auth\SendEmailVerification;
use App\Http\Controllers\Admin\FiliereController;
use App\Http\Controllers\Admin\MatiereController;
use App\Http\Controllers\Admin\NiveauController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\EpreuveController;
use App\Http\Controllers\ReferentielValuesController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
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

Route::middleware('auth')->group(function (): void {
    Route::get('/email/verify', function (Request $request) {
        return $request->user()->hasVerifiedEmail()
            ? to_route('dashboard')
            : Inertia::render('Auth/VerifyEmail', [
                'email' => $request->user()->email,
                'status' => $request->session()->get('status'),
                'emailError' => $request->session()->get('email_error'),
            ]);
    })->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return to_route('dashboard')->with('status', 'Ton adresse e-mail est confirmée.');
    })->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

    Route::post('/email/verification-notification', function (
        Request $request,
        SendEmailVerification $sendEmailVerification,
    ) {
        if ($request->user()->hasVerifiedEmail()) {
            return to_route('dashboard');
        }

        $emailSent = $sendEmailVerification->handle($request->user());

        if (! $emailSent) {
            return back()->with(
                'email_error',
                'Le courriel de vérification n’a pas pu être envoyé. Vérifie le service de messagerie puis réessaie.',
            );
        }

        return back()->with('status', 'Un nouveau lien de confirmation vient de t’être envoyé.');
    })->middleware('throttle:6,1')->name('verification.send');
});

Route::middleware(['auth', 'verified'])->get('/referentiels/actifs', ReferentielValuesController::class)
    ->name('referentiels.actifs');

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/mes-epreuves', [EpreuveController::class, 'index'])
        ->name('epreuves.mine');
    Route::get('/epreuves/deposer', [EpreuveController::class, 'create'])
        ->middleware('can:exams.submit')
        ->name('epreuves.create');
    Route::post('/epreuves', [EpreuveController::class, 'store'])
        ->middleware(['can:exams.submit', 'throttle:10,1'])
        ->name('epreuves.store');
    Route::get('/admin/revue-epreuves', [EpreuveController::class, 'reviewIndex'])
        ->middleware('can:exams.review')
        ->name('epreuves.review');
    Route::post('/admin/revue-epreuves/{epreuve}/publier', [EpreuveController::class, 'publish'])
        ->middleware('can:exams.review')
        ->name('epreuves.publish');
    Route::post('/admin/revue-epreuves/{epreuve}/refuser', [EpreuveController::class, 'reject'])
        ->middleware('can:exams.review')
        ->name('epreuves.reject');
    Route::get('/epreuves/{epreuve}/fichier', [EpreuveController::class, 'file'])
        ->middleware('can:exams.view_published')
        ->name('epreuves.file');
});

Route::middleware(['auth', 'verified', 'can:exams.manage'])
    ->prefix('admin/epreuves')
    ->name('admin.epreuves.')
    ->group(function (): void {
        Route::get('/', [EpreuveController::class, 'adminIndex'])->name('index');
        Route::get('/{epreuve}/modifier', [EpreuveController::class, 'edit'])->name('edit');
        Route::put('/{epreuve}', [EpreuveController::class, 'update'])->name('update');
        Route::delete('/{epreuve}', [EpreuveController::class, 'destroy'])->name('destroy');
    });

Route::middleware(['auth', 'verified', 'can:academic_referentials.manage'])
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
        'canSubmitExams' => request()->user()?->can('exams.submit') ?? false,
        'canReviewExams' => request()->user()?->can('exams.review') ?? false,
        'canManageExams' => request()->user()?->can('exams.manage') ?? false,
        'canManageUsers' => request()->user()?->can('users.manage') ?? false,
        'canManageReferentials' => request()->user()?->can('academic_referentials.manage') ?? false,
    ]);
})->middleware(['auth', 'verified', 'can:dashboard.access'])->name('dashboard');

Route::middleware(['auth', 'verified', 'can:users.manage'])
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
