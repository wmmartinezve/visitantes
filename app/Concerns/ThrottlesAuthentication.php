<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

trait ThrottlesAuthentication
{
    protected function ensureIsNotRateLimited(string $email): void
    {
        $key = 'login|'.strtolower(trim($email)).'|'.request()->ip();

        if (! RateLimiter::tooManyAttempts($key, 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($key);

        throw ValidationException::withMessages([
            'email' => "Demasiados intentos. Espere {$seconds} segundos.",
        ]);
    }

    protected function hitRateLimiter(string $email): void
    {
        RateLimiter::hit('login|'.strtolower(trim($email)).'|'.request()->ip(), 60);
    }

    protected function clearRateLimiter(string $email): void
    {
        RateLimiter::clear('login|'.strtolower(trim($email)).'|'.request()->ip());
    }
}
