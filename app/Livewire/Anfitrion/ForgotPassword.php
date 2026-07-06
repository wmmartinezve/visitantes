<?php

declare(strict_types=1);

namespace App\Livewire\Anfitrion;

use App\Enums\UserRole;
use App\Livewire\Concerns\HandlesFieldOperatorPasswordReset;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.m3.guest-shell')]
class ForgotPassword extends Component
{
    use HandlesFieldOperatorPasswordReset;

    protected function passwordResetRouteName(): string
    {
        return 'anfitrion.password.reset';
    }

    protected function loginRouteName(): string
    {
        return 'anfitrion.login';
    }

    protected function allowedRole(): UserRole
    {
        return UserRole::Anfitrion;
    }

    public function render()
    {
        return view('livewire.shared.field-operator-forgot-password', [
            'title' => 'App Anfitrión',
            'subtitle' => 'Recuperar contraseña',
            'loginRoute' => route('anfitrion.login'),
        ]);
    }
}
