<?php

namespace App\Console\Commands;

use App\AssignableUserRole;
use App\Models\User;
use App\Models\UserAudit;
use App\Rules\PasswordMustNotMatchEmail;
use Closure;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

#[Signature('users:create-administrator')]
#[Description('Create or promote the initial Academia administrator account')]
class CreateInitialAdministrator extends Command
{
    public function handle(): int
    {
        app(AccessControlSeeder::class)->run();

        $email = Str::lower(trim((string) $this->ask('Adresse e-mail de l’administrateur')));
        $user = User::withTrashed()->where('email', $email)->first();

        if ($user !== null) {
            if (! $this->confirm("Promouvoir le compte {$email} et lui attribuer le rôle administrateur ?")) {
                $this->info('Aucune modification effectuée.');

                return self::SUCCESS;
            }

            DB::transaction(function () use ($user): void {
                $role = Role::query()
                    ->where('name', AssignableUserRole::Administrator->value)
                    ->where('guard_name', 'web')
                    ->lockForUpdate()
                    ->firstOrFail();

                $user = User::withTrashed()->lockForUpdate()->findOrFail($user->getKey());

                if ($user->trashed()) {
                    $user->restore();
                }

                $user->update(['is_active' => true]);
                $user->syncRoles($role);

                UserAudit::create([
                    'user_id' => $user->getKey(),
                    'action' => 'initial_admin_promoted',
                    'details' => ['role' => $role->name],
                    'created_at' => now(),
                ]);
            });

            $this->info("Le compte {$email} est maintenant administrateur.");

            return self::SUCCESS;
        }

        $name = trim((string) $this->ask('Nom complet'));
        $password = $this->secret('Mot de passe initial (minimum 15 caractères)');
        $passwordConfirmation = $this->secret('Confirmer le mot de passe initial');
        $maximumBytes = config('hashing.driver') === 'bcrypt' ? 72 : 1024;

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:254', 'unique:users,email'],
            'password' => [
                'required',
                'string',
                'min:15',
                'confirmed',
                new PasswordMustNotMatchEmail($email),
                function (string $attribute, mixed $value, Closure $fail) use ($maximumBytes): void {
                    if (is_string($value) && strlen($value) > $maximumBytes) {
                        $fail('Le mot de passe dépasse la longueur prise en charge par le hachage configuré.');
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        DB::transaction(function () use ($name, $email, $password): void {
            $role = Role::query()
                ->where('name', AssignableUserRole::Administrator->value)
                ->where('guard_name', 'web')
                ->lockForUpdate()
                ->firstOrFail();

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'is_active' => true,
            ]);
            $user->syncRoles($role);

            UserAudit::create([
                'user_id' => $user->getKey(),
                'action' => 'initial_admin_created',
                'details' => ['role' => $role->name],
                'created_at' => now(),
            ]);
        });

        $this->info("Le compte {$email} a été créé avec le rôle administrateur.");

        return self::SUCCESS;
    }
}
