<?php

declare(strict_types=1);

namespace App\Livewire\Anfitrion;

use App\Enums\UserRole;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.m3.guest-shell')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public function mount(): void
    {
        $user = Auth::user();

        if ($user?->rol === UserRole::Anfitrion && $user->refugio_id !== null) {
            $this->redirectRoute('anfitrion.dashboard', navigate: true);
        }
    }

    public function login(): void
    {
        $credentials = $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, remember: true)) {
            $this->addError('email', 'Credenciales incorrectas.');

            return;
        }

        $user = Auth::user();

        if ($user === null || $user->rol !== UserRole::Anfitrion || $user->refugio_id === null) {
            Auth::logout();
            $this->addError('email', 'Esta cuenta no tiene acceso como anfitrión.');

            return;
        }

        session()->regenerate();

        $this->redirectRoute('anfitrion.dashboard', navigate: true);
    }

    public function render()
    {
        return view('livewire.anfitrion.login');
    }
}
