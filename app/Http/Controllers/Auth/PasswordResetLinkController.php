<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\RequestPasswordReset;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordResetLinkRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function store(PasswordResetLinkRequest $request, RequestPasswordReset $sendResetLink): RedirectResponse
    {
        $sendResetLink->handle($request->validated('email'), (string) $request->ip());

        return back()->with(
            'status',
            'Si un compte correspond à cette adresse, un lien de réinitialisation lui sera envoyé.',
        );
    }
}
