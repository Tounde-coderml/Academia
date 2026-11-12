<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Notifications\Auth\LoginNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class SendLoginNotification
{
    public function handle(User $user): void
    {
        try {
            Notification::send($user, new LoginNotification);
        } catch (TransportExceptionInterface $exception) {
            Log::warning('auth.login_notification.delivery_failed', [
                'user_id' => $user->getKey(),
                'error_type' => $exception::class,
            ]);
        }
    }
}
