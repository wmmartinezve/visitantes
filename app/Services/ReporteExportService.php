<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Inventario;
use App\Models\Invitado;
use App\Models\Requerimiento;
use App\Support\OperacionFiltros;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteExportService
{
    public function __construct(
        private readonly OperacionMetricsService $metrics,
    ) {}

    public function dashboardPdf(OperacionFiltros $filtros): Response
    {
        $data = $this->metrics->reporteCompleto($filtros);

        $pdf = Pdf::loadView('reports.dashboard-operacion-pdf', $data)
            ->setPaper('letter', 'portrait')
            ->setOption('isRemoteEnabled', false)
            ->setOption('defaultFont', 'DejaVu Sans');

        $filename = sprintf(
            'reporte-operacion-%s-%s_%s.pdf',
            str(config('visitantes.estado'))->slug('-', 'es'),
            $filtros->desde->format('Y-m-d'),
            $filtros->hasta->format('Y-m-d'),
        );

        return $pdf->download($filename);
    }

    public function invitados(): StreamedResponse
    {
        return $this->csv('invitados-'.config('visitantes.estado').'-'.now()->format('Y-m-d').'.csv', [
            'Nombre',
            'Apellido',
            'Cédula',
            'Teléfono',
            'Fecha nacimiento',
            'Refugio',
            'Municipio',
            'Parroquia',
            'Estatus',
            'Registrado',
        ], function ($handle): void {
            Invitado::query()
                ->with(['refugio.parroquia.municipio'])
                ->whereNull('jefe_familia_id')
                ->orderBy('apellido')
                ->orderBy('nombre')
                ->chunk(100, function ($invitados) use ($handle): void {
                    foreach ($invitados as $invitado) {
                        fputcsv($handle, [
                            $invitado->nombre,
                            $invitado->apellido,
                            $invitado->cedula,
                            $invitado->telefono,
                            $invitado->fecha_nacimiento?->format('Y-m-d'),
                            $invitado->refugio?->nombre,
                            $invitado->refugio?->parroquia?->municipio?->nombre,
                            $invitado->refugio?->parroquia?->nombre,
                            $invitado->estatus?->label(),
                            $invitado->created_at?->format('Y-m-d H:i'),
                        ]);
                    }
                });
        });
    }

    public function requerimientos(): StreamedResponse
    {
        return $this->csv('requerimientos-'.config('visitantes.estado').'-'.now()->format('Y-m-d').'.csv', [
            'Ítem',
            'Cantidad',
            'Estatus',
            'Invitado',
            'Refugio',
            'Centro asignado',
            'Anfitrión',
            'Solicitado',
        ], function ($handle): void {
            Requerimiento::query()
                ->with(['invitado.refugio', 'centroAcopio', 'anfitrion'])
                ->latest()
                ->chunk(100, function ($requerimientos) use ($handle): void {
                    foreach ($requerimientos as $req) {
                        fputcsv($handle, [
                            $req->item_solicitado,
                            $req->cantidad,
                            $req->estatus?->label(),
                            $req->invitado?->nombreCompleto(),
                            $req->invitado?->refugio?->nombre,
                            $req->centroAcopio?->nombre,
                            $req->anfitrion?->name,
                            $req->created_at?->format('Y-m-d H:i'),
                        ]);
                    }
                });
        });
    }

    public function inventario(): StreamedResponse
    {
        return $this->csv('inventario-'.config('visitantes.estado').'-'.now()->format('Y-m-d').'.csv', [
            'Centro de acopio',
            'Municipio',
            'Parroquia',
            'Ítem',
            'Cantidad',
            'Unidad',
            'Activo',
        ], function ($handle): void {
            Inventario::query()
                ->with(['centroAcopio.parroquia.municipio'])
                ->orderBy('centro_acopio_id')
                ->orderBy('item_nombre')
                ->chunk(100, function ($items) use ($handle): void {
                    foreach ($items as $item) {
                        fputcsv($handle, [
                            $item->centroAcopio?->nombre,
                            $item->centroAcopio?->parroquia?->municipio?->nombre,
                            $item->centroAcopio?->parroquia?->nombre,
                            $item->item_nombre,
                            $item->cantidad,
                            $item->unidad_medida,
                            $item->centroAcopio?->activo ? 'Sí' : 'No',
                        ]);
                    }
                });
        });
    }

    /**
     * @param  list<string>  $headers
     * @param  callable(resource): void  $writer
     */
    private function csv(string $filename, array $headers, callable $writer): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $writer): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, $headers);
            $writer($handle);
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
