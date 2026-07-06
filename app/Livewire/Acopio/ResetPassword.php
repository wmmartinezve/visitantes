<?php

declare(strict_types=1);

namespace App\Livewire\Acopio;

use App\Enums\UserRole;
use App\Livewire\Concerns\HandlesFieldOperatorPasswordReset;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.m3.guest-shell')]
class ResetPassword extends Component
{
    use HandlesFieldOperatorPasswordReset;

    public function mount(string $token): void
    {
        $this->mountResetPassword($token);
    }

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
        return view('livewire.shared.field-operator-reset-password', [
            'title' => 'Centro de Acopio',
            'subtitle' => 'Nueva contraseña',
            'loginRoute' => route('acopio.login'),
        ]);
    }
}
