<?php

declare(strict_types=1);

namespace App\Livewire\Anfitrion;

use App\Enums\RequerimientoEstatus;
use App\Models\Requerimiento;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.m3.anfitrion-shell')]
class Requerimientos extends Component
{
    public string $filtro = 'todos';

    public function render()
    {
        $this->authorize('viewAny', Requerimiento::class);

        $refugioId = auth()->user()->refugio_id;

        $query = Requerimiento::query()
            ->with(['invitado', 'centroAcopio'])
            ->whereHas('invitado', fn ($q) => $q->where('refugio_id', $refugioId))
            ->latest();

        if ($this->filtro !== 'todos') {
            $query->where('estatus', RequerimientoEstatus::from($this->filtro));
        }

        $requerimientos = $query->get();

        return view('livewire.anfitrion.requerimientos', [
            'requerimientos' => $requerimientos,
            'conteos' => [
                'pendiente' => Requerimiento::query()
                    ->whereHas('invitado', fn ($q) => $q->where('refugio_id', $refugioId))
                    ->where('estatus', RequerimientoEstatus::Pendiente)
                    ->count(),
                'asignado' => Requerimiento::query()
                    ->whereHas('invitado', fn ($q) => $q->where('refugio_id', $refugioId))
                    ->where('estatus', RequerimientoEstatus::Asignado)
                    ->count(),
                'entregado' => Requerimiento::query()
                    ->whereHas('invitado', fn ($q) => $q->where('refugio_id', $refugioId))
                    ->where('estatus', RequerimientoEstatus::Entregado)
                    ->count(),
            ],
        ]);
    }
}
