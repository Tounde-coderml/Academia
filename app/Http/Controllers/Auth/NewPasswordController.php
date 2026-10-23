<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\PasswordResetResult;
use App\Actions\Auth\ResetUserPassword;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\NewPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NewPasswordController extends Controller
{
    public function create(Request $request, string $token): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function store(NewPasswordRequest $request, ResetUserPassword $resetPassword): RedirectResponse
    {
        $result = $resetPassword->handle($request->validated(), (string) $request->ip());

        if ($result === PasswordResetResult::Reset) {
            return to_route('login')->with('status', 'Votre mot de passe a été modifié. Vous pouvez vous connecter.');
        }

        return back()->withErrors([
            'email' => 'Ce lien de réinitialisation est invalide ou expiré. Demande un nouveau lien.',
        ])->onlyInput('email');
    }
}
