<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class SendEmailVerification
{
    public function handle(User $user): bool
    {
        try {
            Notification::send($user, new VerifyEmail);

            return true;
        } catch (TransportExceptionInterface $exception) {
            Log::warning('auth.email_verification.delivery_failed', [
                'user_id' => $user->getKey(),
                'error_type' => $exception::class,
            ]);

            return false;
        }
    }
}
