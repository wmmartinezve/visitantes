<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CentroAcopio;
use App\Models\Comuna;
use App\Models\Estado;
use App\Models\Inventario;
use App\Models\HogarSolidario;
use App\Models\Municipio;
use App\Models\User;
use App\Enums\SituacionJefeFamilia;
use App\Enums\TipoAnfitrionHogar;
use App\Enums\TipoViviendaHogar;
use App\Support\InsumoCatalog;

class OfflineCatalogService
{
    /**
     * @return array<string, mixed>
     */
    public function buildFor(User $user): array
    {
        $estados = Estado::query()->orderBy('nombre')->get(['id', 'nombre']);
        $municipios = Municipio::query()->orderBy('nombre')->get(['id', 'nombre', 'estado_id']);
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

        $comunas = Comuna::query()
            ->orderBy('nombre')
            ->get(['id', 'parroquia_id', 'nombre'])
            ->map(fn (Comuna $c) => [
                'id' => $c->id,
                'parroquia_id' => $c->parroquia_id,
                'nombre' => $c->nombre,
            ])
            ->values();

        $hogaresSolidarios = HogarSolidario::query()
            ->with(['parroquia:id,nombre,municipio_id', 'comuna:id,nombre,parroquia_id'])
            ->orderBy('codigo')
            ->get()
            ->map(fn (HogarSolidario $h) => [
                'id' => $h->id,
                'codigo' => $h->codigo,
                'nombre' => $h->codigo,
                'parroquia_id' => $h->parroquia_id,
                'comuna_id' => $h->comuna_id,
                'parroquia' => $h->parroquia?->nombre,
                'comuna' => $h->comuna?->nombre,
                'tipo_vivienda' => $h->tipo_vivienda?->value,
                'tipo_anfitrion' => $h->tipo_anfitrion?->value,
                'parentesco_anfitrion' => $h->parentesco_anfitrion,
                'latitud' => (float) $h->latitud,
                'longitud' => (float) $h->longitud,
                'direccion_exacta' => $h->direccion_exacta,
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
            'hogar_solidario_id' => $user->hogar_solidario_id,
            'centro_acopio_id' => $user->centro_acopio_id,
            'requiere_registro_hogar' => $user->isAnfitrion() && $user->hogar_solidario_id === null,
        ];

        if ($user->hogar_solidario_id !== null) {
            $hogar = $hogaresSolidarios->firstWhere('id', $user->hogar_solidario_id);
            $operador['hogar_solidario'] = $hogar;
            $operador['refugio'] = $hogar;
            $operador['tiene_nucleo_familiar'] = \App\Models\Invitado::query()
                ->where('hogar_solidario_id', $user->hogar_solidario_id)
                ->whereNull('jefe_familia_id')
                ->exists();
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
            $estados->count(),
            $municipios->count(),
            $parroquias->count(),
            $comunas->count(),
            $hogaresSolidarios->count(),
            $centrosAcopio->count(),
            count($inventarioLocal),
        ]));

        return [
            'version' => $version,
            'generated_at' => now()->toIso8601String(),
            'estado' => config('visitantes.estado'),
            'estados' => $estados->map(fn ($e) => ['id' => $e->id, 'nombre' => $e->nombre])->values()->all(),
            'municipios' => $municipios->map(fn ($m) => [
                'id' => $m->id,
                'estado_id' => $m->estado_id,
                'nombre' => $m->nombre,
            ])->values()->all(),
            'parroquias' => $parroquias->all(),
            'comunas' => $comunas->all(),
            'hogares_solidarios' => $hogaresSolidarios->all(),
            'refugios' => $hogaresSolidarios->all(),
            'centros_acopio' => $centrosAcopio->all(),
            'unidades_medida' => config('visitantes.unidades_medida'),
            'insumos_catalogo' => InsumoCatalog::catalog(),
            'items_insumo_sugeridos' => InsumoCatalog::flatSubcategorias(),
            'parentescos' => config('visitantes.parentescos'),
            'situaciones_jefe' => collect(SituacionJefeFamilia::cases())
                ->map(fn (SituacionJefeFamilia $s) => ['value' => $s->value, 'label' => $s->label()])
                ->values()
                ->all(),
            'tipos_vivienda' => collect(TipoViviendaHogar::cases())
                ->map(fn (TipoViviendaHogar $t) => ['value' => $t->value, 'label' => $t->label()])
                ->values()
                ->all(),
            'tipos_anfitrion' => collect(TipoAnfitrionHogar::cases())
                ->map(fn (TipoAnfitrionHogar $t) => ['value' => $t->value, 'label' => $t->label()])
                ->values()
                ->all(),
            'operador' => $operador,
            'inventario_local' => $inventarioLocal,
        ];
    }
}
