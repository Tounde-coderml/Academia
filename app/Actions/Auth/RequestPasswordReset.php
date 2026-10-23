<?php

namespace App\Actions\Auth;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class RequestPasswordReset
{
    public function handle(string $email, string $ipAddress): void
    {
        $identityFingerprint = hash_hmac('sha256', $email, (string) config('app.key'));
        $ipFingerprint = hash_hmac('sha256', $ipAddress, (string) config('app.key'));
        $identityIpKey = 'auth:password-reset:request:identity-ip:'.hash_hmac(
            'sha256',
            $email.'|'.$ipAddress,
            (string) config('app.key'),
        );
        $ipKey = 'auth:password-reset:request:ip:'.$ipFingerprint;

        if (RateLimiter::tooManyAttempts($identityIpKey, 3) || RateLimiter::tooManyAttempts($ipKey, 20)) {
            Log::notice('auth.password_reset.request_throttled', [
                'identity_fingerprint' => $identityFingerprint,
                'ip_fingerprint' => $ipFingerprint,
            ]);

            return;
        }

        RateLimiter::hit($identityIpKey, 900);
        RateLimiter::hit($ipKey, 900);

        try {
            Password::sendResetLink(['email' => $email]);
        } catch (Throwable $exception) {
            Log::warning('auth.password_reset.delivery_failed', [
                'identity_fingerprint' => $identityFingerprint,
                'ip_fingerprint' => $ipFingerprint,
                'error_type' => $exception::class,
            ]);

            return;
        }

        Log::info('auth.password_reset.requested', [
            'identity_fingerprint' => $identityFingerprint,
            'ip_fingerprint' => $ipFingerprint,
        ]);
    }
}
