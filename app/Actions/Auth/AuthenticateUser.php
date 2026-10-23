<?php

namespace App\Actions\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class AuthenticateUser
{
    public function handle(string $email, string $password, string $ipAddress): LoginAttemptResult
    {
        $identityFingerprint = hash_hmac('sha256', $email, (string) config('app.key'));
        $ipFingerprint = hash_hmac('sha256', $ipAddress, (string) config('app.key'));
        $identityIpKey = 'auth:login:identity-ip:'.hash_hmac(
            'sha256',
            $email.'|'.$ipAddress,
            (string) config('app.key'),
        );
        $ipKey = 'auth:login:ip:'.$ipFingerprint;

        if (RateLimiter::tooManyAttempts($identityIpKey, 5) || RateLimiter::tooManyAttempts($ipKey, 30)) {
            Log::notice('auth.login.throttled', [
                'identity_fingerprint' => $identityFingerprint,
                'ip_fingerprint' => $ipFingerprint,
            ]);

            return LoginAttemptResult::Throttled;
        }

        if (! Auth::attempt(['email' => $email, 'password' => $password])) {
            RateLimiter::hit($identityIpKey, 60);
            RateLimiter::hit($ipKey, 60);

            Log::notice('auth.login.rejected', [
                'identity_fingerprint' => $identityFingerprint,
                'ip_fingerprint' => $ipFingerprint,
            ]);

            return LoginAttemptResult::Rejected;
        }

        RateLimiter::clear($identityIpKey);

        Log::info('auth.login.succeeded', [
            'user_id' => Auth::id(),
            'ip_fingerprint' => $ipFingerprint,
        ]);

        return LoginAttemptResult::Authenticated;
    }
}
