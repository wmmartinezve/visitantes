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
use App\Enums\CondicionInvitado;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use App\Enums\SituacionJefeFamilia;
use App\Enums\TipoAnfitrionHogar;
use App\Enums\TipoViviendaHogar;
use App\Support\InsumoCatalog;
use App\Support\InvitadoMencionesCatalog;

class OfflineCatalogService
{
    /**
     * @return array<string, mixed>
     */
    public function buildFor(User $user): array
    {
        $user = app(AnfitrionMobileProfileService::class)->normalize($user);

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

        $hogaresSolidarios = $this->hogaresSolidariosParaOperador($user);

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
            'requiere_registro_hogar' => app(AnfitrionMobileProfileService::class)->requiereRegistroHogar($user),
            'puede_registrar_otro_hogar' => app(AnfitrionMobileProfileService::class)->puedeRegistrarOtroHogar($user),
            'hogares_count' => app(AnfitrionMobileProfileService::class)->countHogares($user),
            'hogares' => $user->isAnfitrion()
                ? app(AnfitrionMobileProfileService::class)->hogaresParaApi($user)
                : [],
            'tiene_nucleo_familiar' => false,
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
            'condiciones' => collect(CondicionInvitado::cases())
                ->map(fn (CondicionInvitado $c) => ['value' => $c->value, 'label' => $c->label()])
                ->values()
                ->all(),
            'menciones_catalogo' => InvitadoMencionesCatalog::forApi(),
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

    /**
     * Hogares visibles en catálogo offline — mínimo privilegio por rol.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function hogaresSolidariosParaOperador(User $user): Collection
    {
        $query = HogarSolidario::query()
            ->with(['parroquia:id,nombre,municipio_id', 'comuna:id,nombre,parroquia_id']);

        if ($user->isAnfitrion()) {
            $query->where('anfitrion_user_id', $user->id);
        } elseif ($user->isCentroAcopio() && $user->centro_acopio_id !== null) {
            $centroId = (int) $user->centro_acopio_id;
            $query->whereHas(
                'invitados.requerimientos',
                fn (Builder $q) => $q->where('centro_acopio_id', $centroId),
            );
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query
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
    }
}
