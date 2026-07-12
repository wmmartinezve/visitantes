<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\CentroAcopio;
use App\Models\Invitado;
use App\Models\Requerimiento;
use App\Models\HogarSolidario;
use App\Services\ReporteExportService;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportesOperacion extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'Logística';

    protected static ?string $navigationLabel = 'Reportes';

    protected static ?string $title = 'Reportes de operación';

    protected static ?int $navigationSort = 30;

    protected static string $view = 'filament.pages.reportes-operacion';

    public function exportInvitados(ReporteExportService $service): StreamedResponse
    {
        return $service->invitados();
    }

    public function exportRequerimientos(ReporteExportService $service): StreamedResponse
    {
        return $service->requerimientos();
    }

    public function exportInventario(ReporteExportService $service): StreamedResponse
    {
        return $service->inventario();
    }

    /**
     * @return array<string, int>
     */
    public function getResumenProperty(): array
    {
        return [
            'refugios' => HogarSolidario::query()->count(),
            'centros' => CentroAcopio::query()->where('activo', true)->count(),
            'invitados' => Invitado::query()->where('estatus', 'activo')->count(),
            'requerimientos' => Requerimiento::query()->count(),
        ];
    }
}
