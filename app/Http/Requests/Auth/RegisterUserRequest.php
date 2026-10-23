<?php

namespace App\Http\Requests\Auth;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class RegisterUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:254', 'unique:users,email'],
            'password' => [
                'required',
                'string',
                'min:15',
                'confirmed',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $maximumBytes = config('hashing.driver') === 'bcrypt' ? 72 : 1024;

                    if (is_string($value) && strlen($value) > $maximumBytes) {
                        $fail('Le mot de passe dépasse la longueur prise en charge par le hachage configuré.');
                    }
                },
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $email = $this->input('email');

        if (is_string($email)) {
            $this->merge(['email' => Str::lower(trim($email))]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Le nom est obligatoire.',
            'name.max' => 'Le nom est trop long.',
            'email.required' => 'L’adresse e-mail est obligatoire.',
            'email.email' => 'Saisis une adresse e-mail valide.',
            'email.max' => 'L’adresse e-mail est trop longue.',
            'email.unique' => 'Cette adresse e-mail possède déjà un compte.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins 15 caractères.',
            'password.confirmed' => 'Les deux mots de passe ne correspondent pas.',
        ];
    }
}
