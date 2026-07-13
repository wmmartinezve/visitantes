<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\CentroAcopio;
use App\Models\HogarSolidario;
use App\Models\Invitado;
use App\Models\Requerimiento;
use App\Services\ReporteExportService;
use App\Support\InvitadoMencionesCatalog;
use App\Support\VisitantesFeatures;
use Filament\Pages\Page;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportesOperacion extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'Operación';

    protected static ?string $navigationLabel = 'Reportes';

    protected static ?string $title = 'Reportes de operación';

    protected static ?int $navigationSort = 40;

    protected static string $view = 'filament.pages.reportes-operacion';

    public function exportInvitados(ReporteExportService $service): StreamedResponse
    {
        return $service->invitados();
    }

    public function exportRequerimientos(ReporteExportService $service): StreamedResponse
    {
        abort_unless(VisitantesFeatures::logistica(), 404);

        return $service->requerimientos();
    }

    public function exportInventario(ReporteExportService $service): StreamedResponse
    {
        abort_unless(VisitantesFeatures::logistica(), 404);

        return $service->inventario();
    }

    public function getLogisticaHabilitadaProperty(): bool
    {
        return VisitantesFeatures::logistica();
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

        $resumen = [
            'hogares' => HogarSolidario::query()->count(),
            'invitados' => (clone $invitadosActivos)->count(),
            'invitados_con_menciones' => $invitadosConMenciones,
        ];

        if (VisitantesFeatures::logistica()) {
            $resumen['centros'] = CentroAcopio::query()->where('activo', true)->count();
            $resumen['requerimientos'] = Requerimiento::query()->count();
        }

        return $resumen;
    }
}
