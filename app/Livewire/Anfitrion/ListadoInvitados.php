<?php

declare(strict_types=1);

namespace App\Livewire\Anfitrion;

use App\Models\Invitado;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.m3.anfitrion-shell')]
class ListadoInvitados extends Component
{
    use WithPagination;

    public string $busqueda = '';

    public function updatedBusqueda(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $this->authorize('viewAny', Invitado::class);

        $refugioId = auth()->user()->hogar_solidario_id;

        $invitados = Invitado::query()
            ->with(['miembrosFamilia'])
            ->where('hogar_solidario_id', $refugioId)
            ->whereNull('jefe_familia_id')
            ->when($this->busqueda !== '', function ($query): void {
                $term = '%'.$this->busqueda.'%';
                $query->where(function ($q) use ($term): void {
                    $q->where('nombre', 'like', $term)
                        ->orWhere('apellido', 'like', $term)
                        ->orWhere('cedula', 'like', $term);
                });
            })
            ->latest()
            ->paginate(10);

        return view('livewire.anfitrion.listado-invitados', [
            'invitados' => $invitados,
        ]);
    }
}
