<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class PasswordMustNotMatchEmail implements ValidationRule
{
    public function __construct(private readonly string $email) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (
            is_string($value)
            && Str::lower(trim($this->email)) === Str::lower($value)
        ) {
            $fail('Le mot de passe ne peut pas être identique à l’adresse e-mail.');
        }
    }
}
