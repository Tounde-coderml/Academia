<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\RegisterStudent;
use App\Actions\Auth\SendEmailVerification;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Notifications\Auth\LoginNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/RegisterPage');
    }

    public function store(
        RegisterUserRequest $request,
        RegisterStudent $registerStudent,
        SendEmailVerification $sendEmailVerification,
    ): RedirectResponse {
        $user = $registerStudent->handle($request->safe()->only(['name', 'email', 'password']));
        Auth::login($user);
        $request->session()->regenerate();
        $user->notify(new LoginNotification);
        $emailSent = $sendEmailVerification->handle($user);

        if (! $emailSent) {
            return to_route('verification.notice')->with(
                'email_error',
                'Ton compte est créé, mais le courriel de vérification n’a pas pu être envoyé. Vérifie le service de messagerie puis réessaie.',
            );
        }

        return to_route('verification.notice')
            ->with('status', 'Un lien de vérification vient d’être envoyé à ton adresse e-mail.');
    }
}
