<?php

declare(strict_types=1);

namespace App\Livewire\Anfitrion;

use App\Enums\InvitadoEstatus;
use App\Enums\RequerimientoEstatus;
use App\Models\Invitado;
use App\Models\Requerimiento;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.m3.anfitrion-shell')]
class Dashboard extends Component
{
    public function render()
    {
        $refugioId = auth()->user()->refugio_id;

        return view('livewire.anfitrion.dashboard', [
            'refugio' => auth()->user()->refugio,
            'invitadosActivos' => Invitado::query()
                ->where('refugio_id', $refugioId)
                ->where('estatus', InvitadoEstatus::Activo)
                ->count(),
            'requerimientosPendientes' => Requerimiento::query()
                ->whereHas('invitado', fn ($q) => $q->where('refugio_id', $refugioId))
                ->where('estatus', RequerimientoEstatus::Pendiente)
                ->count(),
        ]);
    }
}
