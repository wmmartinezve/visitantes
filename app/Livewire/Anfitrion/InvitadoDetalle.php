<?php

declare(strict_types=1);

namespace App\Livewire\Anfitrion;

use App\Enums\RequerimientoEstatus;
use App\Models\Invitado;
use App\Models\Requerimiento;
use App\Support\InsumoCatalog;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.m3.anfitrion-shell')]
class InvitadoDetalle extends Component
{
    public Invitado $invitado;

    public string $categoria = '';

    public string $subcategoria = '';

    public int $cantidad = 1;

    public function mount(Invitado $invitado): void
    {
        $this->authorize('view', $invitado);

        $this->invitado = $invitado->load(['miembrosFamilia', 'requerimientos', 'refugio']);
    }

    public function updatedCategoria(): void
    {
        $this->subcategoria = '';
    }

    public function agregarRequerimiento(): void
    {
        $this->authorize('createForInvitado', $this->invitado);
        $validated = $this->validate([
            'categoria' => ['required', 'string', 'max:255'],
            'subcategoria' => ['required', 'string', 'max:255'],
            'cantidad' => ['required', 'integer', 'min:1'],
        ]);

        InsumoCatalog::assertPair($validated['categoria'], $validated['subcategoria']);

        Requerimiento::query()->create([
            'invitado_id' => $this->invitado->id,
            'anfitrion_id' => auth()->id(),
            'categoria' => $validated['categoria'],
            'subcategoria' => $validated['subcategoria'],
            'cantidad' => $validated['cantidad'],
            'estatus' => RequerimientoEstatus::Pendiente,
        ]);

        $this->reset(['categoria', 'subcategoria', 'cantidad']);
        $this->cantidad = 1;

        $this->invitado->load('requerimientos');

        session()->flash('success', 'Requerimiento registrado.');
    }

    public function render()
    {
        return view('livewire.anfitrion.invitado-detalle', [
            'insumosCatalogo' => InsumoCatalog::catalog(),
            'subcategoriasDisponibles' => $this->categoria !== ''
                ? InsumoCatalog::subcategorias($this->categoria)
                : [],
        ]);
    }
}
