<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Enums\RequerimientoEstatus;
use App\Models\CentroAcopio;
use App\Models\Inventario;
use App\Models\Invitado;
use App\Models\Requerimiento;
use App\Models\Refugio;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OperacionOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Invitados activos', (string) Invitado::query()->where('estatus', 'activo')->count())
                ->description('En '.config('visitantes.estado'))
                ->icon('heroicon-o-user-group')
                ->color('primary'),
            Stat::make('Refugios', (string) Refugio::query()->count())
                ->description('Puntos de atención')
                ->icon('heroicon-o-home-modern'),
            Stat::make('Centros de acopio', (string) CentroAcopio::query()->where('activo', true)->count())
                ->description('Activos')
                ->icon('heroicon-o-building-storefront')
                ->color('success'),
            Stat::make('Requerimientos pendientes', (string) Requerimiento::query()->where('estatus', RequerimientoEstatus::Pendiente)->count())
                ->description('Por asignar')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('warning'),
            Stat::make('Ítems con stock bajo', (string) Inventario::query()->where('cantidad', '<=', 5)->count())
                ->description('Cantidad ≤ 5')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
