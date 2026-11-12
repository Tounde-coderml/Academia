<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\DevCommands;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            DevCommands::register(
                'php artisan serve:uploads',
                'server',
            );
        }

        $resetUrl = static function (CanResetPassword $notifiable, string $token): string {
            return route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);
        };

        ResetPassword::createUrlUsing($resetUrl);
        ResetPassword::toMailUsing(function (CanResetPassword $notifiable, string $token) use ($resetUrl): MailMessage {
            $expiresInMinutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

            return (new MailMessage)
                ->subject('Réinitialisez votre mot de passe Academia')
                ->view([
                    'html' => 'emails.auth.action',
                    'text' => 'emails.auth.action-text',
                ], [
                    'title' => 'Réinitialisation du mot de passe',
                    'introduction' => 'Nous avons reçu une demande de réinitialisation du mot de passe associé à votre compte Academia.',
                    'actionLabel' => 'Réinitialiser mon mot de passe',
                    'instructions' => "Ce lien expirera dans {$expiresInMinutes} minutes.",
                    'closing' => 'Si vous n’êtes pas à l’origine de cette demande, ignorez ce courriel.',
                    'url' => $resetUrl($notifiable, $token),
                ]);
        });

        VerifyEmail::toMailUsing(function (MustVerifyEmail $notifiable, string $url): MailMessage {
            $expiresInMinutes = config('auth.verification.expire', 60);

            return (new MailMessage)
                ->subject('Confirmez votre adresse e-mail Academia')
                ->view([
                    'html' => 'emails.auth.action',
                    'text' => 'emails.auth.action-text',
                ], [
                    'title' => 'Confirmation de votre adresse e-mail',
                    'introduction' => 'Confirmez votre adresse e-mail pour activer votre compte Academia.',
                    'actionLabel' => 'Confirmer mon adresse e-mail',
                    'instructions' => "Ce lien expirera dans {$expiresInMinutes} minutes.",
                    'closing' => 'Si vous n’avez pas créé de compte Academia, ignorez ce courriel.',
                    'url' => $url,
                ]);
        });
    }
}
