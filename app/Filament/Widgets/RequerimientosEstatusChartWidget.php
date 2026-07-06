<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Services\OperacionMetricsService;
use App\Support\OperacionFiltros;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;

class RequerimientosEstatusChartWidget extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 2;

    protected static ?string $heading = 'Requerimientos por estatus';

    protected static ?string $maxHeight = '280px';

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $filtros = OperacionFiltros::fromArray($this->filters);
        $porEstatus = app(OperacionMetricsService::class)->requerimientosPorEstatus($filtros);

        return [
            'datasets' => [
                [
                    'data' => array_values($porEstatus),
                    'backgroundColor' => ['#FFCC00', '#002776', '#CF142B'],
                ],
            ],
            'labels' => array_keys($porEstatus),
        ];
    }
}
