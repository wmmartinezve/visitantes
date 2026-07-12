<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InvitadoEstatus;
use App\Enums\RequerimientoEstatus;
use App\Enums\UserRole;
use App\Models\CentroAcopio;
use App\Models\Inventario;
use App\Models\Invitado;
use App\Models\HogarSolidario;
use App\Models\Requerimiento;
use App\Models\User;
use App\Support\OperacionFiltros;
use App\Support\VisitantesFeatures;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class OperacionMetricsService
{
    private const STOCK_BAJO_UMBRAL = 5;

    /**
     * @return array<string, int|float|string>
     */
    public function kpis(OperacionFiltros $filtros): array
    {
        $reqCreados = $this->requerimientosEnPeriodo($filtros)->count();
        $reqEntregados = $this->requerimientosEntregadosEnPeriodo($filtros)->count();
        $unidadesEntregadas = (int) $this->requerimientosEntregadosEnPeriodo($filtros)->sum('cantidad');

        $hogaresQuery = $this->refugios($filtros);
        $hogaresTotal = (clone $hogaresQuery)->count();
        $anfitrionesRegistrados = User::query()->where('rol', UserRole::Anfitrion)->count();
        $anfitrionesDesplegadosGlobal = User::query()
            ->where('rol', UserRole::Anfitrion)
            ->whereNotNull('hogar_solidario_id')
            ->count();

        return [
            'hogares_solidarios' => $hogaresTotal,
            'refugios' => $hogaresTotal,
            'hogares_con_nucleo' => (clone $hogaresQuery)->whereHas('jefeFamilia')->count(),
            'hogares_sin_nucleo' => (clone $hogaresQuery)->whereDoesntHave('jefeFamilia')->count(),
            'hogares_nuevos_periodo' => (clone $hogaresQuery)
                ->whereBetween('created_at', [$filtros->desde, $filtros->hasta])
                ->count(),
            'anfitriones_registrados' => $anfitrionesRegistrados,
            'anfitriones_desplegados' => $this->anfitrionesDesplegados($filtros)->count(),
            'anfitriones_sin_asignar' => User::query()
                ->where('rol', UserRole::Anfitrion)
                ->whereNull('hogar_solidario_id')
                ->count(),
            'tasa_despliegue_anfitriones' => $anfitrionesRegistrados > 0
                ? round(($anfitrionesDesplegadosGlobal / $anfitrionesRegistrados) * 100, 1)
                : 0.0,
            'invitados_activos' => $this->invitados($filtros)->where('estatus', InvitadoEstatus::Activo)->count(),
            'invitados_registrados' => $this->invitadosRegistradosEnPeriodo($filtros)->count(),
            'nuevas_familias' => $this->invitadosRegistradosEnPeriodo($filtros)->whereNull('jefe_familia_id')->count(),
            'miembros_familia' => $this->invitadosRegistradosEnPeriodo($filtros)->whereNotNull('jefe_familia_id')->count(),
            'invitados_egresados' => $this->invitados($filtros)->where('estatus', InvitadoEstatus::Egresado)->count(),
            'centros_activos' => $this->centrosAcopio($filtros)->where('activo', true)->count(),
            'requerimientos_creados' => $reqCreados,
            'requerimientos_pendientes' => $this->requerimientos($filtros)->where('estatus', RequerimientoEstatus::Pendiente)->count(),
            'requerimientos_asignados' => $this->requerimientos($filtros)->where('estatus', RequerimientoEstatus::Asignado)->count(),
            'requerimientos_entregados' => $reqEntregados,
            'unidades_solicitadas' => (int) $this->requerimientosEnPeriodo($filtros)->sum('cantidad'),
            'unidades_entregadas' => $unidadesEntregadas,
            'tasa_cumplimiento' => $reqCreados > 0
                ? round(($reqEntregados / $reqCreados) * 100, 1)
                : 0.0,
            'stock_bajo' => $this->inventario($filtros)->where('cantidad', '<=', self::STOCK_BAJO_UMBRAL)->count(),
            'unidades_inventario' => (int) $this->inventario($filtros)->sum('cantidad'),
        ];
    }

    /**
     * Filas de KPIs para el PDF del dashboard (orden y etiquetas unificadas).
     *
     * @return list<array{0: string, 1: int|float|string}>
     */
    public function filasKpisParaPdf(array $kpis, ?bool $logistica = null): array
    {
        $logistica ??= VisitantesFeatures::logistica();

        $filas = [
            ['Hogares solidarios', $kpis['hogares_solidarios']],
            ['Hogares con núcleo hospedado', $kpis['hogares_con_nucleo']],
            ['Hogares sin núcleo', $kpis['hogares_sin_nucleo']],
            ['Hogares nuevos en período', $kpis['hogares_nuevos_periodo']],
            ['Anfitriones registrados', $kpis['anfitriones_registrados']],
            ['Anfitriones desplegados', $kpis['anfitriones_desplegados']],
            ['Anfitriones sin asignar', $kpis['anfitriones_sin_asignar']],
            ['Tasa de despliegue anfitriones', $kpis['tasa_despliegue_anfitriones'].'%'],
            ['Invitados activos', $kpis['invitados_activos']],
            ['Registrados en período', $kpis['invitados_registrados']],
            ['Nuevas familias (jefes)', $kpis['nuevas_familias']],
            ['Miembros de familia', $kpis['miembros_familia']],
            ['Invitados egresados', $kpis['invitados_egresados']],
        ];

        if ($logistica) {
            $filas = array_merge($filas, [
                ['Centros de acopio activos', $kpis['centros_activos']],
                ['Requerimientos creados', $kpis['requerimientos_creados']],
                ['Requerimientos pendientes', $kpis['requerimientos_pendientes']],
                ['Requerimientos asignados', $kpis['requerimientos_asignados']],
                ['Requerimientos entregados', $kpis['requerimientos_entregados']],
                ['Tasa de cumplimiento', $kpis['tasa_cumplimiento'].'%'],
                ['Unidades solicitadas', $kpis['unidades_solicitadas']],
                ['Unidades entregadas', $kpis['unidades_entregadas']],
                ['Ítems con stock bajo', $kpis['stock_bajo']],
                ['Unidades en inventario', $kpis['unidades_inventario']],
            ]);
        }

        return $filas;
    }

    /**
     * @return array<string, int>
     */
    public function requerimientosPorEstatus(OperacionFiltros $filtros): array
    {
        $base = $this->requerimientosEnPeriodo($filtros);

        return [
            RequerimientoEstatus::Pendiente->label() => (clone $base)->where('estatus', RequerimientoEstatus::Pendiente)->count(),
            RequerimientoEstatus::Asignado->label() => (clone $base)->where('estatus', RequerimientoEstatus::Asignado)->count(),
            RequerimientoEstatus::Entregado->label() => (clone $base)->where('estatus', RequerimientoEstatus::Entregado)->count(),
        ];
    }

    /**
     * @return Collection<int, object{nombre: string, total: int}>
     */
    public function topRefugiosPorInvitados(OperacionFiltros $filtros, int $limit = 10): Collection
    {
        return $this->refugios($filtros)
            ->withCount(['invitados as activos_count' => fn (Builder $q) => $this->aplicarFiltroInvitado($q, $filtros)
                ->where('estatus', InvitadoEstatus::Activo)])
            ->orderByDesc('activos_count')
            ->limit($limit)
            ->get(['id', 'nombre'])
            ->map(fn (HogarSolidario $refugio): object => (object) [
                'nombre' => $refugio->nombre,
                'total' => (int) $refugio->activos_count,
            ]);
    }

    /**
     * @return Collection<int, object{nombre: string, entregados: int, unidades: int}>
     */
    public function topCentrosPorEntregas(OperacionFiltros $filtros, int $limit = 10): Collection
    {
        return $this->requerimientosEntregadosEnPeriodo($filtros)
            ->selectRaw('centro_acopio_id, COUNT(*) as entregados, SUM(cantidad) as unidades')
            ->whereNotNull('centro_acopio_id')
            ->groupBy('centro_acopio_id')
            ->orderByDesc('entregados')
            ->limit($limit)
            ->get()
            ->map(function ($row): object {
                $centro = CentroAcopio::query()->find($row->centro_acopio_id);

                return (object) [
                    'nombre' => $centro?->nombre ?? '—',
                    'entregados' => (int) $row->entregados,
                    'unidades' => (int) $row->unidades,
                ];
            });
    }

    /**
     * @return Collection<int, Inventario>
     */
    public function inventarioStockBajo(OperacionFiltros $filtros, int $limit = 20): Collection
    {
        return $this->inventario($filtros)
            ->with(['centroAcopio.parroquia.municipio'])
            ->where('cantidad', '<=', self::STOCK_BAJO_UMBRAL)
            ->orderBy('cantidad')
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Requerimiento>
     */
    public function requerimientosRecientes(OperacionFiltros $filtros, int $limit = 15): Collection
    {
        return $this->requerimientosEnPeriodo($filtros)
            ->with(['invitado.refugio', 'centroAcopio', 'anfitrion'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * @return Collection<int, Invitado>
     */
    public function invitadosRegistradosRecientes(OperacionFiltros $filtros, int $limit = 20): Collection
    {
        return $this->invitadosRegistradosEnPeriodo($filtros)
            ->with(['refugio.parroquia.municipio'])
            ->whereNull('jefe_familia_id')
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function reporteCompleto(OperacionFiltros $filtros): array
    {
        $kpis = $this->kpis($filtros);
        $logistica = VisitantesFeatures::logistica();

        return [
            'filtros' => $filtros,
            'etiquetas_filtros' => $filtros->descripcionEtiquetas(),
            'kpis' => $kpis,
            'kpi_filas' => $this->filasKpisParaPdf($kpis, $logistica),
            'logistica_habilitada' => $logistica,
            'requerimientos_por_estatus' => $this->requerimientosPorEstatus($filtros),
            'top_refugios' => $this->topRefugiosPorInvitados($filtros),
            'top_centros' => $this->topCentrosPorEntregas($filtros),
            'stock_bajo' => $this->inventarioStockBajo($filtros, 50),
            'requerimientos_recientes' => $this->requerimientosRecientes($filtros, 30),
            'invitados_recientes' => $this->invitadosRegistradosRecientes($filtros, 30),
            'generado_en' => now()->timezone(config('app.timezone'))->format('d/m/Y H:i'),
        ];
    }

    /** @return Builder<Invitado> */
    private function invitados(OperacionFiltros $filtros): Builder
    {
        return $this->aplicarFiltroInvitado(Invitado::query(), $filtros);
    }

    /** @return Builder<Invitado> */
    private function invitadosRegistradosEnPeriodo(OperacionFiltros $filtros): Builder
    {
        return $this->invitados($filtros)
            ->whereBetween('created_at', [$filtros->desde, $filtros->hasta]);
    }

    /** @return Builder<Requerimiento> */
    private function requerimientos(OperacionFiltros $filtros): Builder
    {
        $query = Requerimiento::query();

        $this->aplicarFiltroRequerimiento($query, $filtros);

        return $query;
    }

    /** @return Builder<Requerimiento> */
    private function requerimientosEnPeriodo(OperacionFiltros $filtros): Builder
    {
        return $this->requerimientos($filtros)
            ->whereBetween('created_at', [$filtros->desde, $filtros->hasta]);
    }

    /** @return Builder<Requerimiento> */
    private function requerimientosEntregadosEnPeriodo(OperacionFiltros $filtros): Builder
    {
        return $this->requerimientos($filtros)
            ->where('estatus', RequerimientoEstatus::Entregado)
            ->whereBetween('updated_at', [$filtros->desde, $filtros->hasta]);
    }

    /** @return Builder<HogarSolidario> */
    private function refugios(OperacionFiltros $filtros): Builder
    {
        $query = HogarSolidario::query();

        if ($filtros->refugioId) {
            return $query->whereKey($filtros->refugioId);
        }

        if ($filtros->parroquiaId) {
            return $query->where('parroquia_id', $filtros->parroquiaId);
        }

        if ($filtros->municipioId) {
            return $query->whereHas('parroquia', fn (Builder $q) => $q->where('municipio_id', $filtros->municipioId));
        }

        return $query;
    }

    /** @return Builder<CentroAcopio> */
    private function centrosAcopio(OperacionFiltros $filtros): Builder
    {
        $query = CentroAcopio::query();

        if ($filtros->centroAcopioId) {
            return $query->whereKey($filtros->centroAcopioId);
        }

        if ($filtros->parroquiaId) {
            return $query->where('parroquia_id', $filtros->parroquiaId);
        }

        if ($filtros->municipioId) {
            return $query->whereHas('parroquia', fn (Builder $q) => $q->where('municipio_id', $filtros->municipioId));
        }

        return $query;
    }

    /** @return Builder<Inventario> */
    private function inventario(OperacionFiltros $filtros): Builder
    {
        $query = Inventario::query();

        if ($filtros->centroAcopioId) {
            return $query->where('centro_acopio_id', $filtros->centroAcopioId);
        }

        if ($filtros->parroquiaId || $filtros->municipioId) {
            return $query->whereHas('centroAcopio', function (Builder $q) use ($filtros): void {
                if ($filtros->parroquiaId) {
                    $q->where('parroquia_id', $filtros->parroquiaId);
                } elseif ($filtros->municipioId) {
                    $q->whereHas('parroquia', fn (Builder $pq) => $pq->where('municipio_id', $filtros->municipioId));
                }
            });
        }

        return $query;
    }

    /** @param  Builder<Invitado>  $query */
    private function aplicarFiltroInvitado(Builder $query, OperacionFiltros $filtros): Builder
    {
        if ($filtros->refugioId) {
            return $query->where('hogar_solidario_id', $filtros->refugioId);
        }

        if ($filtros->parroquiaId || $filtros->municipioId) {
            return $query->whereHas('refugio', function (Builder $q) use ($filtros): void {
                if ($filtros->parroquiaId) {
                    $q->where('parroquia_id', $filtros->parroquiaId);
                } elseif ($filtros->municipioId) {
                    $q->whereHas('parroquia', fn (Builder $pq) => $pq->where('municipio_id', $filtros->municipioId));
                }
            });
        }

        return $query;
    }

    /** @return Builder<User> */
    private function anfitrionesDesplegados(OperacionFiltros $filtros): Builder
    {
        $query = User::query()
            ->where('rol', UserRole::Anfitrion)
            ->whereNotNull('hogar_solidario_id');

        if ($filtros->refugioId) {
            return $query->where('hogar_solidario_id', $filtros->refugioId);
        }

        if ($filtros->parroquiaId) {
            return $query->whereHas(
                'hogarSolidario',
                fn (Builder $q) => $q->where('parroquia_id', $filtros->parroquiaId),
            );
        }

        if ($filtros->municipioId) {
            return $query->whereHas(
                'hogarSolidario.parroquia',
                fn (Builder $q) => $q->where('municipio_id', $filtros->municipioId),
            );
        }

        return $query;
    }

    /** @param  Builder<Requerimiento>  $query */
    private function aplicarFiltroRequerimiento(Builder $query, OperacionFiltros $filtros): Builder
    {
        if ($filtros->centroAcopioId) {
            $query->where('centro_acopio_id', $filtros->centroAcopioId);
        }

        if ($filtros->refugioId || $filtros->parroquiaId || $filtros->municipioId) {
            $query->whereHas('invitado', function (Builder $q) use ($filtros): void {
                $this->aplicarFiltroInvitado($q, $filtros);
            });
        }

        return $query;
    }
}
