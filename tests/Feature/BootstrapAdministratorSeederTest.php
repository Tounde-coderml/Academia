<?php

namespace Tests\Feature;

use App\AssignableUserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BootstrapAdministratorSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_configured_bootstrap_administrator_is_created_once_and_verified(): void
    {
        config()->set('app.bootstrap_admin', [
            'name' => 'Administrateur de démonstration',
            'email' => '  ADMIN@EXAMPLE.TEST ',
            'password' => 'secure-demo-admin-password',
        ]);

        $this->seed();

        $administrator = User::query()->where('email', 'admin@example.test')->firstOrFail();
        $originalPasswordHash = $administrator->password;

        $this->assertTrue($administrator->hasRole(AssignableUserRole::Administrator->value));
        $this->assertNotNull($administrator->email_verified_at);
        $this->assertDatabaseHas('user_audits', [
            'user_id' => $administrator->id,
            'action' => 'initial_admin_created',
        ]);

        config()->set('app.bootstrap_admin.password', 'a-different-password-for-admin');
        $this->seed();

        $this->assertSame($originalPasswordHash, $administrator->fresh()->password);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('user_audits', 1);
    }
}
