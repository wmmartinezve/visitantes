<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RequerimientoEstatus;
use App\Models\CentroAcopio;
use App\Models\Inventario;
use App\Models\Requerimiento;
use App\Support\GeoDistance;
use App\Support\InsumoCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RequerimientoAsignacionService
{
    /**
     * @return Collection<int, array{centro: CentroAcopio, inventario: Inventario, distancia_km: ?float, cantidad: int}>
     */
    public function buscarCentrosConStock(Requerimiento $requerimiento): Collection
    {
        $requerimiento->loadMissing('invitado.refugio');

        $refugio = $requerimiento->invitado?->refugio;

        $inventarios = $this->inventarioQueryForRequerimiento($requerimiento)
            ->with(['centroAcopio.parroquia'])
            ->whereHas('centroAcopio', fn ($q) => $q->where('activo', true))
            ->where('cantidad', '>', 0)
            ->get();

        return $inventarios
            ->map(function (Inventario $inventario) use ($refugio): array {
                $centro = $inventario->centroAcopio;
                $distancia = null;

                if ($refugio !== null && $centro !== null) {
                    $distancia = GeoDistance::kilometers(
                        (float) $refugio->latitud,
                        (float) $refugio->longitud,
                        (float) $centro->latitud,
                        (float) $centro->longitud,
                    );
                }

                return [
                    'centro' => $centro,
                    'inventario' => $inventario,
                    'distancia_km' => $distancia,
                    'cantidad' => $inventario->cantidad,
                ];
            })
            ->sortBy([
                fn (array $row) => $row['distancia_km'] ?? PHP_FLOAT_MAX,
                fn (array $row) => -$row['cantidad'],
            ])
            ->values();
    }

    public function asignar(Requerimiento $requerimiento, int $centroAcopioId): Requerimiento
    {
        $centro = CentroAcopio::query()->findOrFail($centroAcopioId);

        $tieneStock = $this->inventarioQueryForRequerimiento($requerimiento)
            ->where('centro_acopio_id', $centro->id)
            ->where('cantidad', '>=', $requerimiento->cantidad)
            ->exists();

        if (! $tieneStock) {
            throw new RuntimeException('El centro seleccionado no tiene stock suficiente para este ítem.');
        }

        $requerimiento->update([
            'centro_acopio_id' => $centro->id,
            'estatus' => RequerimientoEstatus::Asignado,
        ]);

        return $requerimiento->fresh(['centroAcopio', 'invitado']);
    }

    public function marcarEntregado(Requerimiento $requerimiento): Requerimiento
    {
        if ($requerimiento->centro_acopio_id === null) {
            throw new RuntimeException('El requerimiento no tiene centro asignado.');
        }

        return DB::transaction(function () use ($requerimiento): Requerimiento {
            $inventario = $this->inventarioQueryForRequerimiento($requerimiento)
                ->where('centro_acopio_id', $requerimiento->centro_acopio_id)
                ->lockForUpdate()
                ->first();

            if ($inventario === null || $inventario->cantidad < $requerimiento->cantidad) {
                throw new RuntimeException('Stock insuficiente para completar la entrega.');
            }

            $inventario->decrement('cantidad', $requerimiento->cantidad);

            $requerimiento->update([
                'estatus' => RequerimientoEstatus::Entregado,
            ]);

            return $requerimiento->fresh();
        });
    }

    /** @return Builder<Inventario> */
    private function inventarioQueryForRequerimiento(Requerimiento $requerimiento): Builder
    {
        $query = Inventario::query();

        if ($requerimiento->categoria && $requerimiento->subcategoria) {
            return $query
                ->where('categoria', $requerimiento->categoria)
                ->where('subcategoria', $requerimiento->subcategoria);
        }

        $pair = InsumoCatalog::guessFromLabel((string) $requerimiento->item_solicitado);

        if ($pair !== null) {
            return $query
                ->where('categoria', $pair['categoria'])
                ->where('subcategoria', $pair['subcategoria']);
        }

        $item = mb_strtolower(trim($requerimiento->item_solicitado));

        return $query->whereRaw('LOWER(item_nombre) LIKE ?', ['%'.$item.'%']);
    }
}
