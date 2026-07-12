<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\OperacionMetricsService;
use App\Support\OperacionFiltros;
use App\Support\VisitantesFeatures;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OperacionOverviewWidget extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $filtros = OperacionFiltros::fromArray($this->filters);
        $kpis = app(OperacionMetricsService::class)->kpis($filtros);
        $periodo = $filtros->desde->format('d/m/Y').' — '.$filtros->hasta->format('d/m/Y');
        $geoFiltrado = $filtros->tieneFiltroGeografico();
        $ambito = $geoFiltrado ? 'En el ámbito filtrado' : 'Todo '.config('visitantes.estado');

        $stats = [
            Stat::make('Hogares solidarios', (string) $kpis['hogares_solidarios'])
                ->description($ambito)
                ->icon('heroicon-o-home-modern')
                ->color('primary'),

            Stat::make('Con núcleo hospedado', (string) $kpis['hogares_con_nucleo'])
                ->description($ambito)
                ->icon('heroicon-o-user-group')
                ->color('success'),

            Stat::make('Sin núcleo', (string) $kpis['hogares_sin_nucleo'])
                ->description($ambito)
                ->icon('heroicon-o-home')
                ->color('gray'),

            Stat::make('Hogares nuevos', (string) $kpis['hogares_nuevos_periodo'])
                ->description($periodo)
                ->icon('heroicon-o-plus-circle')
                ->color('info'),

            Stat::make('Anfitriones registrados', (string) $kpis['anfitriones_registrados'])
                ->description($geoFiltrado ? 'Desplegados en el ámbito' : 'Usuarios app anfitrión')
                ->icon('heroicon-o-identification')
                ->color('primary'),

            Stat::make('Anfitriones desplegados', (string) $kpis['anfitriones_desplegados'])
                ->description($ambito)
                ->icon('heroicon-o-map-pin')
                ->color('success'),

            Stat::make('Anfitriones sin asignar', (string) $kpis['anfitriones_sin_asignar'])
                ->description($geoFiltrado ? 'Solo visible a nivel estatal' : 'Pendientes de despliegue')
                ->icon('heroicon-o-clock')
                ->color('warning'),

            Stat::make(
                $geoFiltrado ? 'Cobertura de hogares' : 'Tasa de despliegue',
                $kpis['tasa_despliegue_anfitriones'].'%',
            )
                ->description($geoFiltrado ? 'Hogares con núcleo / total hogares' : 'Desplegados / registrados')
                ->icon('heroicon-o-chart-bar')
                ->color($kpis['tasa_despliegue_anfitriones'] >= 70 ? 'success' : 'warning'),

            Stat::make('Invitados activos', (string) $kpis['invitados_activos'])
                ->description($ambito)
                ->icon('heroicon-o-users')
                ->color('primary'),

            Stat::make('Registrados en período', (string) $kpis['invitados_registrados'])
                ->description($periodo)
                ->icon('heroicon-o-user-plus')
                ->color('info'),

            Stat::make('Nuevas familias', (string) $kpis['nuevas_familias'])
                ->description('Jefes de familia · '.$periodo)
                ->icon('heroicon-o-users'),

            Stat::make('Miembros de familia', (string) $kpis['miembros_familia'])
                ->description('Registrados · '.$periodo)
                ->icon('heroicon-o-user'),

            Stat::make('Invitados egresados', (string) $kpis['invitados_egresados'])
                ->description('Total acumulado')
                ->icon('heroicon-o-arrow-right-on-rectangle')
                ->color('gray'),
        ];

        if (VisitantesFeatures::logistica()) {
            $stats = array_merge($stats, [
                Stat::make('Centros de acopio', (string) $kpis['centros_activos'])
                    ->description('Activos')
                    ->icon('heroicon-o-building-storefront')
                    ->color('success'),

                Stat::make('Requerimientos creados', (string) $kpis['requerimientos_creados'])
                    ->description($periodo)
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('warning'),

                Stat::make('Pendientes', (string) $kpis['requerimientos_pendientes'])
                    ->description('Por asignar')
                    ->icon('heroicon-o-clock')
                    ->color('warning'),

                Stat::make('Asignados', (string) $kpis['requerimientos_asignados'])
                    ->description('En despacho')
                    ->icon('heroicon-o-truck')
                    ->color('info'),

                Stat::make('Entregados', (string) $kpis['requerimientos_entregados'])
                    ->description('Completados · '.$periodo)
                    ->icon('heroicon-o-check-circle')
                    ->color('success'),

                Stat::make('Tasa de cumplimiento', $kpis['tasa_cumplimiento'].'%')
                    ->description('Entregados / creados · '.$periodo)
                    ->icon('heroicon-o-chart-bar')
                    ->color($kpis['tasa_cumplimiento'] >= 70 ? 'success' : 'danger'),

                Stat::make('Unidades solicitadas', (string) $kpis['unidades_solicitadas'])
                    ->description($periodo)
                    ->icon('heroicon-o-cube'),

                Stat::make('Unidades entregadas', (string) $kpis['unidades_entregadas'])
                    ->description($periodo)
                    ->icon('heroicon-o-gift')
                    ->color('success'),

                Stat::make('Stock bajo', (string) $kpis['stock_bajo'])
                    ->description('Ítems con cantidad ≤ 5')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger'),

                Stat::make('Unidades en inventario', (string) $kpis['unidades_inventario'])
                    ->description('Total acumulado')
                    ->icon('heroicon-o-archive-box'),
            ]);
        }

        return $stats;
    }
}
