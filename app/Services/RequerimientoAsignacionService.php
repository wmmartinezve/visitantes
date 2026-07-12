<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RequerimientoEstatus;
use App\Enums\ActivityAction;
use App\Models\CentroAcopio;
use App\Models\Inventario;
use App\Models\HogarSolidario;
use App\Models\Requerimiento;
use App\Support\GeoDistance;
use App\Support\InsumoCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RequerimientoAsignacionService
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
    ) {}

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

    /**
     * @return Collection<int, array{centro: CentroAcopio, inventario: Inventario, distancia_km: ?float, cantidad: int}>
     */
    public function buscarCentrosConStockConsolidado(
        HogarSolidario $refugio,
        ?string $categoria,
        ?string $subcategoria,
        string $itemSolicitado,
        int $cantidadTotal,
    ): Collection {
        $inventarios = $this->inventarioQueryForInsumo($categoria, $subcategoria, $itemSolicitado)
            ->with(['centroAcopio.parroquia'])
            ->whereHas('centroAcopio', fn ($q) => $q->where('activo', true))
            ->where('cantidad', '>=', $cantidadTotal)
            ->get();

        return $inventarios
            ->map(function (Inventario $inventario) use ($refugio): array {
                $centro = $inventario->centroAcopio;
                $distancia = null;

                if ($centro !== null) {
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

    /**
     * @param  EloquentCollection<int, Requerimiento>|list<Requerimiento>  $requerimientos
     */
    public function asignarLote(EloquentCollection|array $requerimientos, int $centroAcopioId): int
    {
        $coleccion = $requerimientos instanceof EloquentCollection
            ? $requerimientos
            : new EloquentCollection($requerimientos);

        if ($coleccion->isEmpty()) {
            return 0;
        }

        $coleccion->loadMissing('invitado.refugio');

        $pendientes = $coleccion->filter(
            fn (Requerimiento $r): bool => $r->estatus === RequerimientoEstatus::Pendiente,
        );

        if ($pendientes->count() !== $coleccion->count()) {
            throw new RuntimeException('Solo se pueden asignar requerimientos en estatus pendiente.');
        }

        /** @var Requerimiento $referencia */
        $referencia = $pendientes->first();
        $refugioIds = $pendientes
            ->map(fn (Requerimiento $r): ?int => $r->invitado?->hogar_solidario_id)
            ->unique()
            ->filter()
            ->values();

        if ($refugioIds->count() !== 1) {
            throw new RuntimeException('El lote debe pertenecer a un único refugio.');
        }

        $cantidadTotal = (int) $pendientes->sum('cantidad');

        $tieneStock = $this->inventarioQueryForInsumo(
            $referencia->categoria,
            $referencia->subcategoria,
            (string) $referencia->item_solicitado,
        )
            ->where('centro_acopio_id', $centroAcopioId)
            ->where('cantidad', '>=', $cantidadTotal)
            ->exists();

        if (! $tieneStock) {
            throw new RuntimeException(
                "El centro no tiene stock suficiente para el envío consolidado (se requieren {$cantidadTotal} unidades).",
            );
        }

        return DB::transaction(function () use ($pendientes, $centroAcopioId): int {
            $asignados = 0;

            foreach ($pendientes as $requerimiento) {
                $this->asignarSinValidarStock($requerimiento, $centroAcopioId);
                $asignados++;
            }

            return $asignados;
        });
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

        return $this->asignarSinValidarStock($requerimiento, $centro->id);
    }

    private function asignarSinValidarStock(Requerimiento $requerimiento, int $centroAcopioId): Requerimiento
    {
        $before = $this->activityLog->snapshot($requerimiento);

        $requerimiento->update([
            'centro_acopio_id' => $centroAcopioId,
            'estatus' => RequerimientoEstatus::Asignado,
        ]);

        $requerimiento->refresh();

        $diff = $this->activityLog->diff($before, $this->activityLog->snapshot($requerimiento));

        $this->activityLog->log(
            ActivityAction::Asignado,
            $requerimiento,
            'Requerimiento asignado a centro de acopio',
            $diff,
        );

        return $requerimiento->fresh(['centroAcopio', 'invitado']);
    }

    public function marcarEntregado(Requerimiento $requerimiento): Requerimiento
    {
        if ($requerimiento->centro_acopio_id === null) {
            throw new RuntimeException('El requerimiento no tiene centro asignado.');
        }

        if ($requerimiento->estatus === RequerimientoEstatus::Entregado) {
            return $requerimiento->fresh(['centroAcopio', 'invitado']);
        }

        if ($requerimiento->estatus !== RequerimientoEstatus::Asignado) {
            throw new RuntimeException('Solo se pueden entregar requerimientos en estatus asignado.');
        }

        return DB::transaction(function () use ($requerimiento): Requerimiento {
            $inventario = $this->inventarioQueryForRequerimiento($requerimiento)
                ->where('centro_acopio_id', $requerimiento->centro_acopio_id)
                ->lockForUpdate()
                ->first();

            if ($inventario === null || $inventario->cantidad < $requerimiento->cantidad) {
                throw new RuntimeException('Stock insuficiente para completar la entrega.');
            }

            $cantidadAnterior = $inventario->cantidad;
            $requerimientoBefore = $this->activityLog->snapshot($requerimiento);

            $inventario->decrement('cantidad', $requerimiento->cantidad);

            $requerimiento->update([
                'estatus' => RequerimientoEstatus::Entregado,
            ]);

            $requerimiento->refresh();
            $inventario->refresh();

            $this->activityLog->log(
                ActivityAction::Entregado,
                $requerimiento,
                'Requerimiento marcado como entregado',
                $this->activityLog->diff($requerimientoBefore, $this->activityLog->snapshot($requerimiento)),
            );

            $this->activityLog->log(
                ActivityAction::StockDecremented,
                $inventario,
                'Inventario descontado por entrega',
                [
                    'old' => ['cantidad' => $cantidadAnterior],
                    'new' => ['cantidad' => $inventario->cantidad],
                    'meta' => [
                        'requerimiento_id' => $requerimiento->id,
                        'cantidad_entregada' => $requerimiento->cantidad,
                    ],
                ],
            );

            return $requerimiento->fresh();
        });
    }

    /** @return Builder<Inventario> */
    private function inventarioQueryForRequerimiento(Requerimiento $requerimiento): Builder
    {
        return $this->inventarioQueryForInsumo(
            $requerimiento->categoria,
            $requerimiento->subcategoria,
            (string) $requerimiento->item_solicitado,
        );
    }

    /** @return Builder<Inventario> */
    private function inventarioQueryForInsumo(?string $categoria, ?string $subcategoria, string $itemSolicitado): Builder
    {
        $query = Inventario::query();

        if ($categoria && $subcategoria) {
            return $query
                ->where('categoria', $categoria)
                ->where('subcategoria', $subcategoria);
        }

        $pair = InsumoCatalog::guessFromLabel($itemSolicitado);

        if ($pair !== null) {
            return $query
                ->where('categoria', $pair['categoria'])
                ->where('subcategoria', $pair['subcategoria']);
        }

        $item = mb_strtolower(trim($itemSolicitado));

        return $query->whereRaw('LOWER(item_nombre) LIKE ?', ['%'.$item.'%']);
    }
}
