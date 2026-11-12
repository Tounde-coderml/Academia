<?php

namespace Database\Seeders;

use App\AssignableUserRole;
use App\Models\User;
use App\Models\UserAudit;
use App\Rules\PasswordMustNotMatchEmail;
use Closure;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class BootstrapAdministratorSeeder extends Seeder
{
    public function run(): void
    {
        $attributes = config('app.bootstrap_admin');

        if (blank($attributes['name']) && blank($attributes['email']) && blank($attributes['password'])) {
            return;
        }

        $email = mb_strtolower(trim((string) $attributes['email']));
        $maximumPasswordBytes = config('hashing.driver') === 'bcrypt' ? 72 : 1024;
        $validated = Validator::make([
            'name' => $attributes['name'],
            'email' => $email,
            'password' => $attributes['password'],
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:254'],
            'password' => [
                'required',
                'string',
                'min:15',
                new PasswordMustNotMatchEmail($email),
                function (string $attribute, mixed $value, Closure $fail) use ($maximumPasswordBytes): void {
                    if (is_string($value) && strlen($value) > $maximumPasswordBytes) {
                        $fail('Le mot de passe dépasse la longueur prise en charge par le hachage configuré.');
                    }
                },
            ],
        ])->validate();

        if (User::withTrashed()->where('email', $email)->exists()) {
            return;
        }

        DB::transaction(function () use ($validated): void {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'email_verified_at' => now(),
                'is_active' => true,
            ]);
            $user->syncRoles(Role::findByName(AssignableUserRole::Administrator->value, 'web'));

            UserAudit::create([
                'user_id' => $user->getKey(),
                'action' => 'initial_admin_created',
                'details' => ['role' => AssignableUserRole::Administrator->value],
                'created_at' => now(),
            ]);
        });
    }
}
