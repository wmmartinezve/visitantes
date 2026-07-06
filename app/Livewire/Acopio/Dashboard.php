<?php

declare(strict_types=1);

namespace App\Livewire\Acopio;

use App\Enums\RequerimientoEstatus;
use App\Models\Inventario;
use App\Models\Requerimiento;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.m3.acopio-shell')]
class Dashboard extends Component
{
    public function render()
    {
        $centroId = auth()->user()->centro_acopio_id;

        return view('livewire.acopio.dashboard', [
            'centro' => auth()->user()->centroAcopio,
            'totalItems' => Inventario::query()->where('centro_acopio_id', $centroId)->count(),
            'stockBajo' => Inventario::query()
                ->where('centro_acopio_id', $centroId)
                ->where('cantidad', '<=', 5)
                ->count(),
            'entregasPendientes' => Requerimiento::query()
                ->where('centro_acopio_id', $centroId)
                ->where('estatus', RequerimientoEstatus::Asignado)
                ->count(),
        ]);
    }
}
