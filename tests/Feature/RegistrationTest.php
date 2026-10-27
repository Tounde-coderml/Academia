<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

    public function test_guest_can_register_a_student_account_and_sign_in_later(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Tounde D.',
            'email' => '  TOUNDEDY@GMAIL.COM ',
            'password' => 'correct-horse-battery-staple',
            'password_confirmation' => 'correct-horse-battery-staple',
        ])->assertRedirectToRoute('login')
            ->assertSessionHas('status', 'Ton compte a été créé. Tu peux maintenant te connecter.');

        $user = User::query()->where('email', 'toundedy@gmail.com')->firstOrFail();

        $this->assertSame('Tounde D.', $user->name);
        $this->assertTrue(Hash::check('correct-horse-battery-staple', $user->password));
        $this->assertTrue($user->hasRole('etudiant'));

        $this->post(route('login.store'), [
            'email' => 'toundedy@gmail.com',
            'password' => 'correct-horse-battery-staple',
        ])->assertRedirectToRoute('dashboard');

        $this->assertAuthenticatedAs($user);
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
