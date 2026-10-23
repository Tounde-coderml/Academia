<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_access_control_seeder_assigns_the_approved_permission_matrix_idempotently(): void
    {
        $this->seed(AccessControlSeeder::class);
        $this->seed(AccessControlSeeder::class);

        $expectedPermissions = [
            'etudiant' => [
                'dashboard.access',
                'exams.view_published',
            ],
            'responsable_classe' => [
                'dashboard.access',
                'dashboard.view_class_stats',
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
                'exams.submit',
                'exams.view_published',
                'users.manage',
            ],
        ];

        $this->assertDatabaseCount('roles', 4);
        $this->assertDatabaseCount('permissions', 8);

        foreach ($expectedPermissions as $roleName => $permissions) {
            $role = Role::findByName($roleName, 'web');

            $this->assertEqualsCanonicalizing(
                $permissions,
                $role->permissions()->pluck('name')->all(),
            );
        }
    }

    public function test_student_role_can_access_the_private_dashboard(): void
    {
        $this->seed(AccessControlSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('etudiant');

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_authenticated_user_without_dashboard_permission_is_forbidden(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertForbidden();
    }
}
