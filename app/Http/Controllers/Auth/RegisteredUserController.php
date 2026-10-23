<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\RegisterStudent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterUserRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/RegisterPage');
    }

    public function store(RegisterUserRequest $request, RegisterStudent $registerStudent): RedirectResponse
    {
        $registerStudent->handle($request->safe()->only(['name', 'email', 'password']));

        return to_route('login')->with(
            'status',
            'Ton compte a été créé. Tu peux maintenant te connecter.',
        );
    }
}
