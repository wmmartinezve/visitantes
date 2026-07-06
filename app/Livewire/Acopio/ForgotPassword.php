<?php

declare(strict_types=1);

namespace App\Livewire\Acopio;

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
        return 'acopio.password.reset';
    }

    protected function loginRouteName(): string
    {
        return 'acopio.login';
    }

    protected function allowedRole(): UserRole
    {
        return UserRole::CentroAcopio;
    }

    public function render()
    {
        return view('livewire.shared.field-operator-forgot-password', [
            'title' => 'Centro de Acopio',
            'subtitle' => 'Recuperar contraseña',
            'loginRoute' => route('acopio.login'),
        ]);
    }
}
