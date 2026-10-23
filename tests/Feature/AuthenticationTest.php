<?php

namespace Tests\Feature;

use App\Actions\Auth\PasswordResetResult;
use App\Actions\Auth\ResetUserPassword;
use App\Models\User;
use App\Notifications\Auth\PasswordChangedNotification;
use Database\Seeders\AccessControlSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Inertia\Testing\AssertableInertia;
use RuntimeException;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_the_login_form(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Auth/LoginPage'));
    }

    public function test_valid_credentials_authenticate_and_rotate_the_session(): void
    {
        $user = User::factory()->create([
            'password' => 'correct-horse-battery-staple-2026',
        ]);
        $sessionIdBeforeLogin = $this->app['session']->driver()->getId();

        $this->post(route('login.store'), [
            'email' => strtoupper($user->email),
            'password' => 'correct-horse-battery-staple-2026',
        ])->assertRedirectToRoute('dashboard');

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($sessionIdBeforeLogin, $this->app['session']->driver()->getId());
    }

    public function test_invalid_credentials_return_the_same_generic_error_for_known_and_unknown_accounts(): void
    {
        $user = User::factory()->create();
        $genericError = 'Adresse e-mail ou mot de passe incorrect.';

        $this->from(route('login'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => $genericError])
            ->assertSessionHas('_old_input.email', $user->email)
            ->assertSessionMissing('_old_input.password');

        $this->from(route('login'))->post(route('login.store'), [
            'email' => 'unknown@example.test',
            'password' => 'incorrect-password',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => $genericError]);

        $this->assertGuest();
    }

    public function test_login_temporarily_rejects_credentials_after_repeated_failures(): void
    {
        $user = User::factory()->create([
            'password' => 'correct-horse-battery-staple-2026',
        ]);

        foreach (range(1, 5) as $attempt) {
            $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'incorrect-password',
            ])->assertSessionHasErrors('email');
        }

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'correct-horse-battery-staple-2026',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->travel(61)->seconds();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'correct-horse-battery-staple-2026',
        ])->assertRedirectToRoute('dashboard');

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_temporarily_rejects_credentials_after_ip_wide_failures(): void
    {
        $user = User::factory()->create([
            'password' => 'correct-horse-battery-staple-2026',
        ]);
        $genericError = 'Adresse e-mail ou mot de passe incorrect.';

        foreach (range(1, 30) as $attempt) {
            $this->post(route('login.store'), [
                'email' => "unknown-{$attempt}@example.test",
                'password' => 'incorrect-password',
            ])->assertSessionHasErrors(['email' => $genericError]);
        }

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'correct-horse-battery-staple-2026',
        ])->assertSessionHasErrors(['email' => $genericError]);

        $this->assertGuest();
    }

    public function test_logout_invalidates_the_authenticated_session(): void
    {
        $this->seed(AccessControlSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('etudiant');

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $this->post(route('logout'))->assertRedirectToRoute('login');

        $this->assertGuest();
        $this->get(route('dashboard'))->assertRedirectToRoute('login');
    }

    public function test_guests_are_redirected_from_the_dashboard_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirectToRoute('login');
    }

    public function test_logout_does_not_accept_a_get_request(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('logout'))
            ->assertMethodNotAllowed();
    }

    public function test_password_reset_request_does_not_reveal_whether_the_account_exists(): void
    {
        $user = User::factory()->create();
        Notification::fake();
        $confirmation = 'Si un compte correspond à cette adresse, un lien de réinitialisation lui sera envoyé.';

        $this->from(route('password.request'))->post(route('password.email'), [
            'email' => $user->email,
        ])->assertRedirect(route('password.request'))
            ->assertSessionHas('status', $confirmation);

        $this->from(route('password.request'))->post(route('password.email'), [
            'email' => 'unknown@example.test',
        ])->assertRedirect(route('password.request'))
            ->assertSessionHas('status', $confirmation);

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_password_reset_request_keeps_a_generic_response_when_email_delivery_fails(): void
    {
        User::factory()->create();
        $confirmation = 'Si un compte correspond à cette adresse, un lien de réinitialisation lui sera envoyé.';
        Password::shouldReceive('sendResetLink')
            ->once()
            ->andThrow(new RuntimeException('Mail transport unavailable'));

        $this->from(route('password.request'))->post(route('password.email'), [
            'email' => 'student@example.test',
        ])->assertRedirect(route('password.request'))
            ->assertSessionHas('status', $confirmation)
            ->assertSessionMissing('_old_input.password');
    }

    public function test_password_reset_requests_are_limited_without_changing_the_public_response(): void
    {
        $user = User::factory()->create();
        $confirmation = 'Si un compte correspond à cette adresse, un lien de réinitialisation lui sera envoyé.';
        Password::shouldReceive('sendResetLink')->times(3);

        foreach (range(1, 4) as $attempt) {
            $this->from(route('password.request'))->post(route('password.email'), [
                'email' => $user->email,
            ])->assertRedirect(route('password.request'))
                ->assertSessionHas('status', $confirmation);
        }
    }

    public function test_password_reset_requests_are_limited_by_ip_without_changing_the_public_response(): void
    {
        $user = User::factory()->create();
        $confirmation = 'Si un compte correspond à cette adresse, un lien de réinitialisation lui sera envoyé.';
        Password::shouldReceive('sendResetLink')->times(20);

        foreach (range(1, 20) as $attempt) {
            $this->from(route('password.request'))->post(route('password.email'), [
                'email' => "unknown-{$attempt}@example.test",
            ])->assertRedirect(route('password.request'))
                ->assertSessionHas('status', $confirmation);
        }

        $this->from(route('password.request'))->post(route('password.email'), [
            'email' => $user->email,
        ])->assertRedirect(route('password.request'))
            ->assertSessionHas('status', $confirmation);
    }

    public function test_valid_password_reset_updates_the_password_and_notifies_the_user(): void
    {
        $user = User::factory()->create([
            'password' => 'old-password-for-academia',
        ]);
        $token = Password::createToken($user);
        Notification::fake();

        $this->post(route('password.update'), [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-password-for-academia-2026',
            'password_confirmation' => 'new-password-for-academia-2026',
        ])->assertRedirectToRoute('login')
            ->assertSessionHas('status', 'Votre mot de passe a été modifié. Vous pouvez vous connecter.');

        $this->assertTrue(Hash::check('new-password-for-academia-2026', $user->fresh()->password));
        Notification::assertSentTo($user, PasswordChangedNotification::class);

        $this->post(route('password.update'), [
            'email' => $user->email,
            'token' => $token,
            'password' => 'another-new-password-2026',
            'password_confirmation' => 'another-new-password-2026',
        ])->assertSessionHasErrors('email');
    }

    public function test_invalid_reset_token_does_not_change_the_password(): void
    {
        $user = User::factory()->create([
            'password' => 'old-password-for-academia',
        ]);
        Notification::fake();

        $this->from(route('password.reset', ['token' => 'invalid-token']))
            ->post(route('password.update'), [
                'email' => $user->email,
                'token' => 'invalid-token',
                'password' => 'new-password-for-academia-2026',
                'password_confirmation' => 'new-password-for-academia-2026',
            ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('old-password-for-academia', $user->fresh()->password));
        Notification::assertNothingSent();
    }

    public function test_expired_reset_token_does_not_change_the_password(): void
    {
        $user = User::factory()->create([
            'password' => 'old-password-for-academia',
        ]);
        $token = Password::createToken($user);
        $this->travel(61)->minutes();

        $this->from(route('password.reset', ['token' => $token]))
            ->post(route('password.update'), [
                'email' => $user->email,
                'token' => $token,
                'password' => 'new-password-for-academia-2026',
                'password_confirmation' => 'new-password-for-academia-2026',
            ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('old-password-for-academia', $user->fresh()->password));
    }

    public function test_password_reset_submission_is_limited_after_repeated_invalid_tokens(): void
    {
        $user = User::factory()->create([
            'password' => 'old-password-for-academia',
        ]);
        $token = Password::createToken($user);

        foreach (range(1, 5) as $attempt) {
            $this->post(route('password.update'), [
                'email' => $user->email,
                'token' => 'invalid-token',
                'password' => 'new-password-for-academia-2026',
                'password_confirmation' => 'new-password-for-academia-2026',
            ])->assertSessionHasErrors('email');
        }

        $this->post(route('password.update'), [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-password-for-academia-2026',
            'password_confirmation' => 'new-password-for-academia-2026',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('old-password-for-academia', $user->fresh()->password));
    }

    public function test_password_reset_submission_is_limited_by_ip_after_repeated_invalid_tokens(): void
    {
        $user = User::factory()->create([
            'password' => 'old-password-for-academia',
        ]);
        $token = Password::createToken($user);

        foreach (range(1, 30) as $attempt) {
            $this->post(route('password.update'), [
                'email' => "unknown-{$attempt}@example.test",
                'token' => 'invalid-token',
                'password' => 'new-password-for-academia-2026',
                'password_confirmation' => 'new-password-for-academia-2026',
            ])->assertSessionHasErrors('email');
        }

        $this->post(route('password.update'), [
            'email' => $user->email,
            'token' => $token,
            'password' => 'new-password-for-academia-2026',
            'password_confirmation' => 'new-password-for-academia-2026',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('old-password-for-academia', $user->fresh()->password));
    }

    public function test_password_reset_requires_a_long_confirmed_password(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'email' => $user->email,
            'token' => $token,
            'password' => 'too-short',
            'password_confirmation' => 'different-short-value',
        ])->assertSessionHasErrors(['password']);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_existing_sessions_are_rejected_after_a_password_change(): void
    {
        $this->seed(AccessControlSeeder::class);
        $user = User::factory()->create([
            'password' => 'old-password-for-academia',
        ]);
        $user->assignRole('etudiant');

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $user->forceFill(['password' => 'new-password-for-academia-2026'])->save();
        Auth::forgetGuards();

        $this->get(route('dashboard'))->assertRedirectToRoute('login');
        $this->assertGuest();
    }

    public function test_existing_session_is_rejected_after_a_password_reset(): void
    {
        $this->seed(AccessControlSeeder::class);
        $user = User::factory()->create([
            'password' => 'old-password-for-academia',
        ]);
        $user->assignRole('etudiant');
        $token = Password::createToken($user);
        Notification::fake();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $result = app(ResetUserPassword::class)->handle([
            'email' => $user->email,
            'password' => 'new-password-for-academia-2026',
            'password_confirmation' => 'new-password-for-academia-2026',
            'token' => $token,
        ], '127.0.0.1');

        $this->assertSame(PasswordResetResult::Reset, $result);

        Auth::forgetGuards();

        $this->get(route('dashboard'))->assertRedirectToRoute('login');
        $this->assertGuest();
    }
}
