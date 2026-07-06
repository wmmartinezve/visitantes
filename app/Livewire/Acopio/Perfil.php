<?php

declare(strict_types=1);

namespace App\Livewire\Acopio;

use App\Livewire\Concerns\ManagesFieldOperatorProfile;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.m3.acopio-shell')]
class Perfil extends Component
{
    use ManagesFieldOperatorProfile;

    public function mount(): void
    {
        $this->mountFieldOperatorProfile();
    }

    public function render()
    {
        return view('livewire.shared.field-operator-perfil', [
            'contextLabel' => 'Centro de acopio',
            'contextValue' => auth()->user()?->centroAcopio?->nombre,
        ]);
    }
}
