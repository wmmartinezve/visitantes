<?php

declare(strict_types=1);

namespace App\Livewire\Anfitrion;

use App\Concerns\ThrottlesAuthentication;
use App\Enums\UserRole;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.m3.guest-shell')]
class Login extends Component
{
    use ThrottlesAuthentication;

    public string $email = '';

    public string $password = '';

    public function mount(): void
    {
        $user = Auth::user();

        if ($user?->rol === UserRole::Anfitrion) {
            $this->redirectRoute(
                $user->hogar_solidario_id === null ? 'anfitrion.registrar' : 'anfitrion.dashboard',
                navigate: true,
            );
        }
    }

    public function login(): void
    {
        $credentials = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureIsNotRateLimited($credentials['email']);

        if (! Auth::attempt($credentials, remember: true)) {
            $this->hitRateLimiter($credentials['email']);
            $this->addError('email', 'Credenciales incorrectas.');

            return;
        }

        $this->clearRateLimiter($credentials['email']);

        $user = Auth::user();

        if ($user === null || $user->rol !== UserRole::Anfitrion) {
            Auth::logout();
            $this->addError('email', 'Esta cuenta no tiene acceso como anfitrión.');

            return;
        }

        session()->regenerate();

        $this->redirectRoute(
            $user->hogar_solidario_id === null ? 'anfitrion.registrar' : 'anfitrion.dashboard',
            navigate: true,
        );
    }

    public function render()
    {
        return view('livewire.anfitrion.login');
    }
}
