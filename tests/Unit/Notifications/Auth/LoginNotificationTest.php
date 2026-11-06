<?php

namespace Tests\Unit\Notifications\Auth;

use App\Notifications\Auth\LoginNotification;
use PHPUnit\Framework\TestCase;

class LoginNotificationTest extends TestCase
{
    public function test_notification_uses_email_and_explains_the_login_alert(): void
    {
        $notification = new LoginNotification;
        $notifiable = new \stdClass;
        $mail = $notification->toMail($notifiable);

        $this->assertSame(['mail'], $notification->via($notifiable));
        $this->assertSame('Nouvelle connexion à votre compte Academia', $mail->subject);
        $this->assertSame('Bonjour,', $mail->greeting);
        $this->assertContains(
            'Une connexion vient d’être effectuée sur votre compte Academia.',
            $mail->introLines,
        );
        $this->assertContains(
            'Vous recevez automatiquement ce message à la suite de cette connexion.',
            $mail->introLines,
        );
        $this->assertContains(
            'Si vous n’êtes pas à l’origine de cette connexion, modifiez votre mot de passe et contactez un administrateur.',
            $mail->introLines,
        );
        $this->assertSame('L’équipe Academia', $mail->salutation);
    }
}
