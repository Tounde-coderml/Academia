<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoginNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nouvelle connexion à votre compte Academia')
            ->greeting('Bonjour,')
            ->line('Une connexion vient d’être effectuée sur votre compte Academia.')
            ->line('Vous recevez automatiquement ce message à la suite de cette connexion.')
            ->line('Si vous n’êtes pas à l’origine de cette connexion, modifiez votre mot de passe et contactez un administrateur.')
            ->salutation('L’équipe Academia');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
