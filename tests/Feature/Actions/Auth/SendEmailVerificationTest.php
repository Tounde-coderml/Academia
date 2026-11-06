<?php

namespace Tests\Feature\Actions\Auth;

use App\Actions\Auth\SendEmailVerification;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class SendEmailVerificationTest extends TestCase
{
    public function test_mail_transport_failure_is_logged_and_reported_as_unsent(): void
    {
        $user = new User;
        $user->forceFill(['id' => 42, 'email' => 'student@example.test']);
        Notification::shouldReceive('send')
            ->once()
            ->withArgs(
                fn (User $notifiable, VerifyEmail $notification): bool => $notifiable === $user
                    && $notification instanceof VerifyEmail,
            )
            ->andThrow(new TransportException('SMTP authentication failed'));
        Log::shouldReceive('warning')
            ->once()
            ->with('auth.email_verification.delivery_failed', [
                'user_id' => 42,
                'error_type' => TransportException::class,
            ]);

        $this->assertFalse(app(SendEmailVerification::class)->handle($user));
    }

    public function test_verification_notification_is_sent_when_mail_transport_is_available(): void
    {
        $user = new User;
        $user->forceFill(['id' => 42, 'email' => 'student@example.test']);
        Notification::fake();

        $this->assertTrue(app(SendEmailVerification::class)->handle($user));

        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
