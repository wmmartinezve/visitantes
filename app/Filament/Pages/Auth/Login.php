<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Enums\UserRole;
use App\Models\User;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;
use Filament\Models\Contracts\FilamentUser;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return null;
        }

        $data = $this->form->getState();

        if (! Filament::auth()->attempt($this->getCredentialsFromFormData($data), $data['remember'] ?? false)) {
            $email = strtolower(trim((string) ($data['email'] ?? '')));

            if (
                User::query()->where('email', $email)->doesntExist()
                && User::query()->where('rol', UserRole::Admin)->doesntExist()
            ) {
                throw ValidationException::withMessages([
                    'data.email' => 'No hay usuarios en la base de datos. Ejecute: php artisan migrate:fresh --seed',
                ]);
            }

            $this->throwFailureValidationException();
        }

        $user = Filament::auth()->user();

        if (
            ($user instanceof FilamentUser) &&
            (! $user->canAccessPanel(Filament::getCurrentPanel()))
        ) {
            Filament::auth()->logout();

            throw ValidationException::withMessages([
                'data.email' => $this->mensajeSinAccesoAdmin($user->rol),
            ]);
        }

        session()->regenerate();

        return app(LoginResponse::class);
    }

    private function mensajeSinAccesoAdmin(UserRole $rol): string
    {
        return match ($rol) {
            UserRole::Anfitrion => 'Esta cuenta es de Anfitrión. Ingrese por /anfitrion — no usa el panel administrativo.',
            UserRole::CentroAcopio => 'Esta cuenta es de Centro de Acopio. Ingrese por /acopio — no usa el panel administrativo.',
            default => 'Esta cuenta no tiene permiso para el panel administrativo.',
        };
    }

    public function getSubheading(): ?string
    {
        if (! app()->environment('local')) {
            return null;
        }

        if (User::query()->where('rol', UserRole::Admin)->doesntExist()) {
            return 'Ejecute php artisan migrate:fresh --seed para crear usuarios demo.';
        }

        return 'Admin demo: admin@visitantes.test · contraseña: password';
    }
}
