<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Notifications\Auth\PasswordChangedNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class ResetUserPassword
{
    /**
     * @param  array{email: string, password: string, password_confirmation: string, token: string}  $credentials
     */
    public function handle(array $credentials, string $ipAddress): PasswordResetResult
    {
        $email = $credentials['email'];
        $identityFingerprint = hash_hmac('sha256', $email, (string) config('app.key'));
        $ipFingerprint = hash_hmac('sha256', $ipAddress, (string) config('app.key'));
        $identityIpKey = 'auth:password-reset:submit:identity-ip:'.hash_hmac(
            'sha256',
            $email.'|'.$ipAddress,
            (string) config('app.key'),
        );
        $ipKey = 'auth:password-reset:submit:ip:'.$ipFingerprint;

        if (RateLimiter::tooManyAttempts($identityIpKey, 5) || RateLimiter::tooManyAttempts($ipKey, 30)) {
            Log::notice('auth.password_reset.submit_throttled', [
                'identity_fingerprint' => $identityFingerprint,
                'ip_fingerprint' => $ipFingerprint,
            ]);

            return PasswordResetResult::Throttled;
        }

        RateLimiter::hit($identityIpKey, 900);
        RateLimiter::hit($ipKey, 60);

        $status = Password::reset(
            $credentials,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                $user->notify(new PasswordChangedNotification);

                Log::info('auth.password_reset.completed', [
                    'user_id' => $user->getKey(),
                ]);
            },
        );

        if ($status === Password::PASSWORD_RESET) {
            return PasswordResetResult::Reset;
        }

        Log::notice('auth.password_reset.rejected', [
            'identity_fingerprint' => $identityFingerprint,
            'ip_fingerprint' => $ipFingerprint,
        ]);

        return PasswordResetResult::InvalidOrExpired;
    }
}
