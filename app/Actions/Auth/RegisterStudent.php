<?php

namespace App\Actions\Auth;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RegisterStudent
{
    /**
     * @param  array{name: string, email: string, password: string}  $attributes
     */
    public function handle(array $attributes): User
    {
        $user = User::create($attributes);

        $dashboardPermission = Permission::findOrCreate('dashboard.access', 'web');
        $studentRole = Role::findOrCreate('etudiant', 'web');
        $studentRole->givePermissionTo($dashboardPermission);
        $user->assignRole($studentRole);

        return $user;
    }
}
