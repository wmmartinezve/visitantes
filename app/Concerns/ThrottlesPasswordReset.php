<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

trait ThrottlesPasswordReset
{
    protected function ensurePasswordResetNotRateLimited(string $email): void
    {
        $key = $this->passwordResetRateLimitKey($email);

        if (! RateLimiter::tooManyAttempts($key, 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            'email' => "Demasiados intentos. Espere {$seconds} segundos.",
        ]);
    }

    protected function hitPasswordResetRateLimiter(string $email): void
    {
        RateLimiter::hit($this->passwordResetRateLimitKey($email), 60);
    }

    private function passwordResetRateLimitKey(string $email): string
    {
        return 'password-reset|'.strtolower(trim($email)).'|'.request()->ip();
    }
}
