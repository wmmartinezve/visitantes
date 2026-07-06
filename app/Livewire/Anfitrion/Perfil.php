<?php

declare(strict_types=1);

namespace App\Livewire\Anfitrion;

use App\Livewire\Concerns\ManagesFieldOperatorProfile;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.m3.anfitrion-shell')]
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
            'contextLabel' => 'Refugio',
            'contextValue' => auth()->user()?->refugio?->nombre,
        ]);
    }
}
