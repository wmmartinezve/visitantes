<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Concerns\HidesWhenLogisticaDisabled;
use App\Models\CentroAcopio;
use App\Models\HogarSolidario;
use App\Models\Invitado;
use App\Models\Requerimiento;
use App\Services\ReporteExportService;
use App\Support\InvitadoMencionesCatalog;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportesOperacion extends Page
{
    use HidesWhenLogisticaDisabled;

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
        $invitadosActivos = Invitado::query()->where('estatus', 'activo');
        $invitadosConMenciones = InvitadoMencionesCatalog::columnasDisponibles()
            ? InvitadoMencionesCatalog::scopeConAlgunaMencion(clone $invitadosActivos)->count()
            : 0;

        return [
            'refugios' => HogarSolidario::query()->count(),
            'centros' => CentroAcopio::query()->where('activo', true)->count(),
            'invitados' => (clone $invitadosActivos)->count(),
            'invitados_con_menciones' => $invitadosConMenciones,
            'requerimientos' => Requerimiento::query()->count(),
        ];
    }
}
