<?php

declare(strict_types=1);

namespace App\Livewire\Acopio;

use App\Models\Inventario;
use App\Support\InsumoCatalog;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.m3.acopio-shell')]
class GestionInventario extends Component
{
    public string $categoria = '';

    public string $subcategoria = '';

    public int $cantidad = 0;

    public string $unidad_medida = 'unidad';

    public ?int $editandoId = null;

    public int $editCantidad = 0;

    public function updatedCategoria(): void
    {
        $this->subcategoria = '';
    }

    public function guardar(): void
    {
        $this->authorize('create', Inventario::class);

        $validated = $this->validate([
            'categoria' => ['required', 'string', 'max:255'],
            'subcategoria' => ['required', 'string', 'max:255'],
            'cantidad' => ['required', 'integer', 'min:0'],
            'unidad_medida' => ['required', 'string', 'max:50'],
        ]);

        InsumoCatalog::assertPair($validated['categoria'], $validated['subcategoria']);

        Inventario::query()->create([
            'centro_acopio_id' => auth()->user()->centro_acopio_id,
            'categoria' => $validated['categoria'],
            'subcategoria' => $validated['subcategoria'],
            'cantidad' => $validated['cantidad'],
            'unidad_medida' => $validated['unidad_medida'],
        ]);

        $this->reset(['categoria', 'subcategoria', 'cantidad', 'unidad_medida']);
        $this->unidad_medida = 'unidad';

        session()->flash('status', 'Ítem agregado al inventario.');
    }

    public function iniciarEdicion(int $id): void
    {
        $item = $this->findItem($id);
        $this->authorize('update', $item);

        $this->editandoId = $item->id;
        $this->editCantidad = $item->cantidad;
    }

    public function actualizarCantidad(): void
    {
        $this->validate([
            'editCantidad' => ['required', 'integer', 'min:0'],
        ]);

        $item = $this->findItem((int) $this->editandoId);
        $this->authorize('update', $item);
        $item->update(['cantidad' => $this->editCantidad]);

        $this->cancelarEdicion();

        session()->flash('status', 'Cantidad actualizada.');
    }

    public function cancelarEdicion(): void
    {
        $this->reset(['editandoId', 'editCantidad']);
    }

    public function eliminar(int $id): void
    {
        $item = $this->findItem($id);
        $this->authorize('delete', $item);
        $item->delete();

        session()->flash('status', 'Ítem eliminado del inventario.');
    }

    public function render()
    {
        $this->authorize('viewAny', Inventario::class);

        return view('livewire.acopio.gestion-inventario', [
            'items' => Inventario::query()
                ->where('centro_acopio_id', auth()->user()->centro_acopio_id)
                ->orderBy('categoria')
                ->orderBy('subcategoria')
                ->get(),
            'insumosCatalogo' => InsumoCatalog::catalog(),
            'subcategoriasDisponibles' => $this->categoria !== ''
                ? InsumoCatalog::subcategorias($this->categoria)
                : [],
        ]);
    }

    private function findItem(int $id): Inventario
    {
        return Inventario::query()
            ->where('centro_acopio_id', auth()->user()->centro_acopio_id)
            ->whereKey($id)
            ->firstOrFail();
    }
}
