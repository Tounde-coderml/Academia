<?php

namespace Tests\Feature;

use App\Actions\Auth\SendEmailVerification;
use App\Models\User;
use App\Notifications\Auth\LoginNotification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_the_registration_form(): void
    {
        $this->get(route('register'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Auth/RegisterPage'));
    }

    public function test_registration_endpoint_is_rate_limited_per_ip(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->from(route('register'))
                ->post(route('register.store'), [])
                ->assertRedirect(route('register'))
                ->assertSessionHasErrors(['name', 'email', 'password']);
        }

        $this->post(route('register.store'), [])
            ->assertTooManyRequests();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_registration_authenticates_student_but_requires_email_verification(): void
    {
        Notification::fake();

        $this->post(route('register.store'), [
            'name' => 'Tounde D.',
            'email' => '  TOUNDEDY@GMAIL.COM ',
            'password' => 'correct-horse-battery-staple',
            'password_confirmation' => 'correct-horse-battery-staple',
        ])->assertRedirectToRoute('verification.notice');

        $user = User::query()->where('email', 'toundedy@gmail.com')->firstOrFail();

        $this->assertSame('Tounde D.', $user->name);
        $this->assertTrue(Hash::check('correct-horse-battery-staple', $user->password));
        $this->assertTrue($user->hasRole('etudiant'));
        $this->assertNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
        Notification::assertSentTo($user, LoginNotification::class);
        Notification::assertSentTo($user, VerifyEmail::class);

        $this->get(route('dashboard'))
            ->assertRedirectToRoute('verification.notice');
    }

    public function test_registration_reports_when_verification_email_cannot_be_sent(): void
    {
        Notification::fake();
        $this->mock(SendEmailVerification::class)
            ->shouldReceive('handle')
            ->once()
            ->andReturnFalse();

        $this->post(route('register.store'), [
            'name' => 'Tounde D.',
            'email' => 'toundedy@example.test',
            'password' => 'correct-horse-battery-staple',
            'password_confirmation' => 'correct-horse-battery-staple',
        ])->assertRedirectToRoute('verification.notice');

        $user = User::query()->where('email', 'toundedy@example.test')->firstOrFail();

        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->email_verified_at);
        $this->get(route('verification.notice'))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Auth/VerifyEmail')
                ->where(
                    'emailError',
                    'Ton compte est créé, mais le courriel de vérification n’a pas pu être envoyé. Vérifie le service de messagerie puis réessaie.',
                ));
    }

    public function test_signed_verification_link_confirms_email_and_allows_dashboard_access(): void
    {
        $this->seed();
        $user = User::factory()->unverified()->create();
        $user->assignRole('etudiant');
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(30),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ],
        );

        $this->actingAs($user)
            ->get($verificationUrl)
            ->assertRedirectToRoute('dashboard')
            ->assertSessionHas('status', 'Ton adresse e-mail est confirmée.');

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->get(route('dashboard'))->assertOk();
    }

    public function test_signed_verification_link_with_an_invalid_email_hash_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(30),
            [
                'id' => $user->getKey(),
                'hash' => sha1('different@example.test'),
            ],
        );

        $this->actingAs($user)
            ->get($verificationUrl)
            ->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_expired_signed_verification_link_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->subMinute(),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ],
        );

        $this->actingAs($user)
            ->get($verificationUrl)
            ->assertForbidden();

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_unverified_user_can_request_another_verification_email(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->from(route('verification.notice'))
            ->post(route('verification.send'))
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas('status', 'Un nouveau lien de confirmation vient de t’être envoyé.');

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_verified_user_cannot_trigger_another_verification_email(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertRedirectToRoute('dashboard');

        Notification::assertNothingSent();
    }

    public function test_verification_email_resend_is_rate_limited_per_ip(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        foreach (range(1, 6) as $attempt) {
            $this->actingAs($user)
                ->from(route('verification.notice'))
                ->post(route('verification.send'))
                ->assertRedirect(route('verification.notice'));
        }

        $this->actingAs($user)
            ->post(route('verification.send'))
            ->assertTooManyRequests();

        Notification::assertSentToTimes($user, VerifyEmail::class, 6);
    }

    public function test_resend_reports_when_verification_email_cannot_be_sent(): void
    {
        $user = User::factory()->unverified()->create();
        $this->mock(SendEmailVerification::class)
            ->shouldReceive('handle')
            ->once()
            ->with($user)
            ->andReturnFalse();

        $this->actingAs($user)
            ->from(route('verification.notice'))
            ->post(route('verification.send'))
            ->assertRedirect(route('verification.notice'))
            ->assertSessionHas(
                'email_error',
                'Le courriel de vérification n’a pas pu être envoyé. Vérifie le service de messagerie puis réessaie.',
            );
    }

    public function test_registration_rejects_duplicate_email_short_password_and_mismatched_confirmation(): void
    {
        User::factory()->create(['email' => 'toundedy@gmail.com']);

        $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Tounde D.',
            'email' => 'TOUNDEDY@gmail.com',
            'password' => 'correct-horse-battery-staple',
            'password_confirmation' => 'correct-horse-battery-staple',
        ])->assertSessionHasErrors('email');

        $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Tounde D.',
            'email' => 'new-student@example.test',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Tounde D.',
            'email' => 'new-student@example.test',
            'password' => 'correct-horse-battery-staple',
            'password_confirmation' => 'different-password-value',
        ])->assertSessionHasErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'new-student@example.test']);
    }

    public function test_registration_rejects_the_email_address_as_password_case_insensitively(): void
    {
        $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Tounde D.',
            'email' => '  Tounde.LongAddress@example.test ',
            'password' => 'TOUNDE.LONGADDRESS@EXAMPLE.TEST',
            'password_confirmation' => 'TOUNDE.LONGADDRESS@EXAMPLE.TEST',
        ])->assertSessionHasErrors([
            'password' => 'Le mot de passe ne peut pas être identique à l’adresse e-mail.',
        ]);

        $this->assertDatabaseMissing('users', ['email' => 'tounde.longaddress@example.test']);
    }
}
