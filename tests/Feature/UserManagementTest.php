<?php

namespace Tests\Feature;

use App\AssignableUserRole;
use App\Models\User;
use App\Models\UserAudit;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AccessControlSeeder::class);
    }

    public function test_administrator_can_view_users_and_recent_audit_entries(): void
    {
        $administrator = $this->makeUserWithRole(AssignableUserRole::Administrator->value);
        $student = $this->makeUserWithRole(AssignableUserRole::Student->value);
        $this->assertTrue($administrator->is_active);
        UserAudit::create([
            'actor_id' => $administrator->id,
            'user_id' => $student->id,
            'action' => 'created',
            'details' => ['role' => AssignableUserRole::Student->value],
            'created_at' => now(),
        ]);

        $response = $this->actingAs($administrator)
            ->get(route('admin.users.index'));

        $response->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Users/UsersManagementPage')
                ->has('users.data', 2)
                ->where('users.data', fn (Collection $users): bool => $users
                    ->contains('email', $administrator->email))
                ->where('audits.0.action', 'created')
                ->where('roles.0.value', AssignableUserRole::Student->value)
                ->missing('users.data.0.password'));
    }

    public function test_student_cannot_access_account_management(): void
    {
        $student = $this->makeUserWithRole(AssignableUserRole::Student->value);

        $this->actingAs($student)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_administrator_can_create_account_and_send_password_setup_link(): void
    {
        Notification::fake();
        $administrator = $this->makeUserWithRole(AssignableUserRole::Administrator->value);

        $this->actingAs($administrator)
            ->post(route('admin.users.store'), [
                'name' => 'Nouvel étudiant',
                'email' => '  NOUVEAU@EXAMPLE.TEST ',
                'role' => AssignableUserRole::Student->value,
            ])
            ->assertRedirectToRoute('admin.users.index')
            ->assertSessionHas('status');

        $student = User::query()->where('email', 'nouveau@example.test')->firstOrFail();

        $this->assertTrue($student->hasRole(AssignableUserRole::Student->value));
        $this->assertTrue($student->is_active);
        Notification::assertSentTo($student, ResetPassword::class);
        $this->assertDatabaseHas('user_audits', [
            'actor_id' => $administrator->id,
            'user_id' => $student->id,
            'action' => 'created',
        ]);
    }

    public function test_administrator_can_change_a_students_role_without_granting_extra_roles(): void
    {
        $administrator = $this->makeUserWithRole(AssignableUserRole::Administrator->value);
        $student = $this->makeUserWithRole(AssignableUserRole::Student->value);
        $student->assignRole(AssignableUserRole::ClassManager->value);

        $this->actingAs($administrator)
            ->put(route('admin.users.update', $student), [
                'name' => $student->name,
                'email' => $student->email,
                'role' => AssignableUserRole::Administration->value,
            ])
            ->assertRedirectToRoute('admin.users.index');

        $student->refresh();
        $this->assertTrue($student->hasRole(AssignableUserRole::Administration->value));
        $this->assertFalse($student->hasRole(AssignableUserRole::Student->value));
        $this->assertFalse($student->hasRole(AssignableUserRole::ClassManager->value));
        $this->assertSame(1, $student->roles()->count());
        $this->assertDatabaseHas('user_audits', [
            'actor_id' => $administrator->id,
            'user_id' => $student->id,
            'action' => 'updated',
        ]);
    }

    public function test_account_management_rejects_unknown_roles_and_duplicate_email(): void
    {
        $administrator = $this->makeUserWithRole(AssignableUserRole::Administrator->value);
        $student = $this->makeUserWithRole(AssignableUserRole::Student->value);

        $this->actingAs($administrator)
            ->put(route('admin.users.update', $student), [
                'name' => $student->name,
                'email' => $student->email,
                'role' => 'super-administrator',
            ])
            ->assertSessionHasErrors('role');

        $otherStudent = $this->makeUserWithRole(AssignableUserRole::Student->value);

        $this->actingAs($administrator)
            ->put(route('admin.users.update', $student), [
                'name' => $student->name,
                'email' => $otherStudent->email,
                'role' => AssignableUserRole::Student->value,
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_last_administrator_cannot_be_demoted_deactivated_or_deleted(): void
    {
        $administrator = $this->makeUserWithRole(AssignableUserRole::Administrator->value);

        $this->actingAs($administrator)
            ->put(route('admin.users.update', $administrator), [
                'name' => $administrator->name,
                'email' => $administrator->email,
                'role' => AssignableUserRole::Student->value,
            ])
            ->assertSessionHasErrors('role');

        $this->actingAs($administrator)
            ->patch(route('admin.users.status', $administrator), ['is_active' => false])
            ->assertSessionHasErrors('is_active');

        $this->actingAs($administrator)
            ->delete(route('admin.users.destroy', $administrator))
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', [
            'id' => $administrator->id,
            'is_active' => true,
            'deleted_at' => null,
        ]);
    }

    public function test_deactivated_users_cannot_login_and_existing_sessions_are_invalidated(): void
    {
        $student = User::factory()->create([
            'password' => 'correct-horse-battery-staple',
            'is_active' => false,
        ]);
        $student->syncRoles(Role::findByName(AssignableUserRole::Student->value, 'web'));

        $this->post(route('login.store'), [
            'email' => $student->email,
            'password' => 'correct-horse-battery-staple',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        $student->update(['is_active' => true]);

        $this->actingAs($student)->get(route('dashboard'))->assertOk();

        $student->update(['is_active' => false]);

        $this->get(route('dashboard'))
            ->assertRedirectToRoute('login')
            ->assertSessionHas('status', 'Ce compte est désactivé. Contacte un administrateur.');

        $this->assertGuest();
    }

    public function test_administrator_can_soft_delete_and_restore_an_account(): void
    {
        $administrator = $this->makeUserWithRole(AssignableUserRole::Administrator->value);
        $student = $this->makeUserWithRole(AssignableUserRole::Student->value);

        $this->actingAs($administrator)
            ->delete(route('admin.users.destroy', $student))
            ->assertRedirectToRoute('admin.users.index');

        $this->assertSoftDeleted('users', ['id' => $student->id]);
        $this->assertDatabaseHas('user_audits', [
            'actor_id' => $administrator->id,
            'user_id' => $student->id,
            'action' => 'deleted',
        ]);

        $this->actingAs($administrator)
            ->patch(route('admin.users.restore', $student->id))
            ->assertRedirectToRoute('admin.users.index');

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('user_audits', [
            'actor_id' => $administrator->id,
            'user_id' => $student->id,
            'action' => 'restored',
        ]);
    }

    public function test_initial_administrator_command_creates_a_user_without_a_default_password(): void
    {
        $this->artisan('users:create-administrator')
            ->expectsQuestion('Adresse e-mail de l’administrateur', 'ADMIN@EXAMPLE.TEST')
            ->expectsQuestion('Nom complet', 'Administrateur initial')
            ->expectsQuestion('Mot de passe initial (minimum 15 caractères)', 'a-strong-initial-password')
            ->expectsQuestion('Confirmer le mot de passe initial', 'a-strong-initial-password')
            ->expectsOutput('Le compte admin@example.test a été créé avec le rôle administrateur.')
            ->assertExitCode(0);

        $administrator = User::query()->where('email', 'admin@example.test')->firstOrFail();

        $this->assertTrue($administrator->hasRole(AssignableUserRole::Administrator->value));
        $this->assertDatabaseHas('user_audits', [
            'user_id' => $administrator->id,
            'action' => 'initial_admin_created',
        ]);
    }

    public function test_initial_administrator_command_rejects_the_email_address_as_password(): void
    {
        $this->artisan('users:create-administrator')
            ->expectsQuestion('Adresse e-mail de l’administrateur', 'admin@example.test')
            ->expectsQuestion('Nom complet', 'Administrateur initial')
            ->expectsQuestion('Mot de passe initial (minimum 15 caractères)', 'admin@example.test')
            ->expectsQuestion('Confirmer le mot de passe initial', 'admin@example.test')
            ->expectsOutput('Le mot de passe ne peut pas être identique à l’adresse e-mail.')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['email' => 'admin@example.test']);
    }

    public function test_audit_pruning_command_deletes_entries_older_than_twelve_months(): void
    {
        $oldAudit = UserAudit::create([
            'action' => 'old_action',
            'details' => [],
            'created_at' => now()->subMonths(12)->subSecond(),
        ]);
        $recentAudit = UserAudit::create([
            'action' => 'recent_action',
            'details' => [],
            'created_at' => now()->subMonths(12)->addSecond(),
        ]);

        $this->artisan('user-audits:prune')
            ->expectsOutput('1 audit(s) de compte supprimé(s) selon la règle de rétention de 12 mois.')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('user_audits', ['id' => $oldAudit->id]);
        $this->assertDatabaseHas('user_audits', ['id' => $recentAudit->id]);
    }

    private function makeUserWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->syncRoles(Role::findByName($role, 'web'));

        return $user;
    }
}
