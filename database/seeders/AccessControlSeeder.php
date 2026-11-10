<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AccessControlSeeder extends Seeder
{
    /**
     * Seed the application's access roles and permissions.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = [
            'etudiant' => [
                'dashboard.access',
                'exams.submit',
                'exams.view_published',
            ],
            'responsable_classe' => [
                'dashboard.access',
                'exams.review',
                'exams.submit',
                'exams.view_published',
            ],
            'administration' => [
                'dashboard.access',
                'dashboard.view_school_stats',
                'exams.review',
                'exams.submit',
                'exams.view_published',
            ],
            'administrateur' => [
                'academic_referentials.manage',
                'dashboard.access',
                'dashboard.view_school_stats',
                'exams.review',
                'exams.manage',
                'exams.submit',
                'exams.view_published',
                'users.manage',
            ],
        ];

        $permissions = collect($roles)
            ->flatten()
            ->unique()
            ->values();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($roles as $roleName => $rolePermissions) {
            Role::findOrCreate($roleName, 'web')->syncPermissions($rolePermissions);
        }
    }
}
