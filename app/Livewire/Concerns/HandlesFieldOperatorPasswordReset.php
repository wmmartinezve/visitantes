<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

trait HandlesFieldOperatorPasswordReset
{
    public string $email = '';

    public string $token = '';

    public string $password = '';

    public string $password_confirmation = '';

    abstract protected function passwordResetRouteName(): string;

    abstract protected function loginRouteName(): string;

    abstract protected function allowedRole(): UserRole;

    public function sendResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()->where('email', $this->email)->first();

        if ($user !== null && $user->rol === $this->allowedRole()) {
            Password::sendResetLink(['email' => $this->email]);
        }

        session()->flash('reset_status', 'Si el correo está registrado, recibirá un enlace para restablecer su contraseña.');
        $this->reset('email');
    }

    public function mountResetPassword(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request()->query('email', '');
    }

    public function resetPassword(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ], [], [
            'password' => 'contraseña',
        ]);

        $user = User::query()->where('email', $this->email)->first();

        if ($user === null || $user->rol !== $this->allowedRole()) {
            $this->addError('email', __('passwords.user'));

            return;
        }

        $status = Password::reset(
            [
                'email' => $this->email,
                'password' => $this->password,
                'password_confirmation' => $this->password_confirmation,
                'token' => $this->token,
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            $this->addError('email', __($status));

            return;
        }

        session()->flash('reset_status', 'Contraseña restablecida. Ya puede iniciar sesión.');
        $this->redirectRoute($this->loginRouteName(), navigate: true);
    }
}
