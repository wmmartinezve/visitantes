<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CentroAcopio;
use App\Models\Inventario;
use App\Models\Municipio;
use App\Models\Refugio;
use App\Models\User;
use App\Support\InsumoCatalog;

class OfflineCatalogService
{
    /**
     * @return array<string, mixed>
     */
    public function buildFor(User $user): array
    {
        $municipios = Municipio::query()->orderBy('nombre')->get(['id', 'nombre']);
        $parroquias = Municipio::query()
            ->with(['parroquias:id,municipio_id,nombre'])
            ->orderBy('nombre')
            ->get()
            ->flatMap(fn (Municipio $m) => $m->parroquias->map(fn ($p) => [
                'id' => $p->id,
                'municipio_id' => $p->municipio_id,
                'nombre' => $p->nombre,
            ]))
            ->values();

        $refugios = Refugio::query()
            ->with('parroquia:id,nombre,municipio_id')
            ->orderBy('nombre')
            ->get()
            ->map(fn (Refugio $r) => [
                'id' => $r->id,
                'nombre' => $r->nombre,
                'parroquia_id' => $r->parroquia_id,
                'parroquia' => $r->parroquia?->nombre,
                'latitud' => (float) $r->latitud,
                'longitud' => (float) $r->longitud,
                'direccion_exacta' => $r->direccion_exacta,
            ])
            ->values();

        $centrosAcopio = CentroAcopio::query()
            ->with('parroquia:id,nombre,municipio_id')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get()
            ->map(fn (CentroAcopio $c) => [
                'id' => $c->id,
                'nombre' => $c->nombre,
                'parroquia_id' => $c->parroquia_id,
                'parroquia' => $c->parroquia?->nombre,
                'latitud' => (float) $c->latitud,
                'longitud' => (float) $c->longitud,
                'direccion_exacta' => $c->direccion_exacta,
                'contacto' => $c->contacto,
            ])
            ->values();

        $operador = [
            'rol' => $user->rol->value,
            'refugio_id' => $user->refugio_id,
            'centro_acopio_id' => $user->centro_acopio_id,
        ];

        if ($user->refugio_id !== null) {
            $operador['refugio'] = $refugios->firstWhere('id', $user->refugio_id);
        }

        if ($user->centro_acopio_id !== null) {
            $operador['centro_acopio'] = $centrosAcopio->firstWhere('id', $user->centro_acopio_id);
        }

        $inventarioLocal = $user->centro_acopio_id !== null
            ? Inventario::query()
                ->where('centro_acopio_id', $user->centro_acopio_id)
                ->orderBy('categoria')
                ->orderBy('subcategoria')
                ->get(['id', 'categoria', 'subcategoria', 'item_nombre', 'cantidad', 'unidad_medida'])
                ->map(fn (Inventario $i) => [
                    'id' => $i->id,
                    'categoria' => $i->categoria,
                    'subcategoria' => $i->subcategoria,
                    'item_nombre' => $i->item_nombre,
                    'cantidad' => $i->cantidad,
                    'unidad_medida' => $i->unidad_medida,
                ])
                ->values()
                ->all()
            : [];

        $version = md5(json_encode([
            $municipios->count(),
            $parroquias->count(),
            $refugios->count(),
            $centrosAcopio->count(),
            count($inventarioLocal),
        ]));

        return [
            'version' => $version,
            'generated_at' => now()->toIso8601String(),
            'estado' => config('visitantes.estado'),
            'municipios' => $municipios->map(fn ($m) => ['id' => $m->id, 'nombre' => $m->nombre])->values()->all(),
            'parroquias' => $parroquias->all(),
            'refugios' => $refugios->all(),
            'centros_acopio' => $centrosAcopio->all(),
            'unidades_medida' => config('visitantes.unidades_medida'),
            'insumos_catalogo' => InsumoCatalog::catalog(),
            'items_insumo_sugeridos' => InsumoCatalog::flatSubcategorias(),
            'parentescos' => config('visitantes.parentescos'),
            'operador' => $operador,
            'inventario_local' => $inventarioLocal,
        ];
    }
}
