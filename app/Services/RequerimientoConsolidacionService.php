<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RequerimientoEstatus;
use App\Models\CentroAcopio;
use App\Models\Inventario;
use App\Models\HogarSolidario;
use App\Models\Requerimiento;
use App\Support\GeoDistance;
use App\Support\InsumoCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use RuntimeException;

class RequerimientoConsolidacionService
{
    /**
     * Agrupa requerimientos por refugio + ítem para planificar envíos consolidados.
     *
     * @return Collection<int, array{
     *     hogar_solidario_id: int,
     *     refugio_nombre: string,
     *     parroquia_nombre: string|null,
     *     categoria: string|null,
     *     subcategoria: string|null,
     *     item_solicitado: string,
     *     cantidad_total: int,
     *     requerimientos_count: int,
     *     requerimiento_ids: list<int>,
     *     invitados: list<string>
     * }>
     */
    public function demandaPorRefugio(
        RequerimientoEstatus $estatus = RequerimientoEstatus::Pendiente,
        ?int $refugioId = null,
    ): Collection {
        $requerimientos = Requerimiento::query()
            ->with(['invitado.refugio.parroquia'])
            ->where('estatus', $estatus)
            ->when($refugioId !== null, function (Builder $query) use ($refugioId): void {
                $query->whereHas('invitado', fn (Builder $q) => $q->where('hogar_solidario_id', $refugioId));
            })
            ->orderBy('id')
            ->get()
            ->filter(fn (Requerimiento $requerimiento): bool => $requerimiento->invitado?->refugio !== null);

        return $requerimientos
            ->groupBy(function (Requerimiento $requerimiento): string {
                $refugioId = (int) $requerimiento->invitado->hogar_solidario_id;

                return implode('|', [
                    (string) $refugioId,
                    (string) ($requerimiento->categoria ?? ''),
                    (string) ($requerimiento->subcategoria ?? ''),
                ]);
            })
            ->map(function (EloquentCollection $grupo): array {
                /** @var Requerimiento $primero */
                $primero = $grupo->first();
                $refugio = $primero->invitado->refugio;

                return [
                    'hogar_solidario_id' => (int) $refugio->id,
                    'refugio_nombre' => $refugio->nombre,
                    'parroquia_nombre' => $refugio->parroquia?->nombre,
                    'categoria' => $primero->categoria,
                    'subcategoria' => $primero->subcategoria,
                    'item_solicitado' => (string) $primero->item_solicitado,
                    'cantidad_total' => (int) $grupo->sum('cantidad'),
                    'requerimientos_count' => $grupo->count(),
                    'requerimiento_ids' => $grupo->pluck('id')->map(fn ($id): int => (int) $id)->values()->all(),
                    'invitados' => $grupo
                        ->map(fn (Requerimiento $r): string => $r->invitado->nombreCompleto().' (×'.$r->cantidad.')')
                        ->values()
                        ->all(),
                ];
            })
            ->sortBy([
                ['refugio_nombre', 'asc'],
                ['item_solicitado', 'asc'],
            ])
            ->values();
    }

    public function grupoKey(int $refugioId, ?string $categoria, ?string $subcategoria): string
    {
        return implode('|', [
            (string) $refugioId,
            (string) ($categoria ?? ''),
            (string) ($subcategoria ?? ''),
        ]);
    }

    /**
     * @param  list<int>  $requerimientoIds
     * @return EloquentCollection<int, Requerimiento>
     */
    public function requerimientosDelGrupo(array $requerimientoIds, RequerimientoEstatus $estatus = RequerimientoEstatus::Pendiente): EloquentCollection
    {
        return Requerimiento::query()
            ->with(['invitado.refugio'])
            ->whereIn('id', $requerimientoIds)
            ->where('estatus', $estatus)
            ->orderBy('id')
            ->get();
    }
}
