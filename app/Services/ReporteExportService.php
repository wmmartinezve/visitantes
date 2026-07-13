<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HogarSolidario;
use App\Models\Inventario;
use App\Models\Invitado;
use App\Models\Requerimiento;
use App\Support\InvitadoFotoStorage;
use App\Support\InvitadoMencionesCatalog;
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

    public function hogarSolidarioFichaPdf(HogarSolidario $hogar): Response
    {
        $hogar->load([
            'parroquia.municipio.estado',
            'comuna',
            'jefeFamilia.miembrosFamilia',
            'jefeFamilia.procedenciaEstado',
            'jefeFamilia.procedenciaMunicipio',
            'jefeFamilia.procedenciaParroquia',
            'anfitriones',
        ]);

        $jefe = $hogar->jefeFamilia;
        $fotoBase64 = $jefe !== null && filled($jefe->foto_ingreso)
            ? InvitadoFotoStorage::base64DataUri($jefe->foto_ingreso)
            : null;

        $miembros = $jefe?->miembrosFamilia
            ->sortBy(fn (Invitado $miembro): string => $miembro->nombreCompleto())
            ->values() ?? collect();

        $pdf = Pdf::loadView('reports.hogar-solidario-ficha-pdf', [
            'hogar' => $hogar,
            'jefe' => $jefe,
            'miembros' => $miembros,
            'fotoBase64' => $fotoBase64,
            'mencionesJefe' => $jefe !== null ? InvitadoMencionesCatalog::resourcePayload($jefe) : null,
            'generadoEn' => now()->timezone(config('app.timezone'))->format('d/m/Y H:i'),
        ])
            ->setPaper('letter', 'portrait')
            ->setOption('isRemoteEnabled', false)
            ->setOption('defaultFont', 'DejaVu Sans');

        $codigo = str($hogar->codigo ?? 'hogar-'.$hogar->id)->slug('-');
        $filename = sprintf('ficha-hogar-solidario-%s-%s.pdf', $codigo, now()->format('Y-m-d'));

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
            'HogarSolidario',
            'Municipio',
            'Parroquia',
            'Estatus',
            'Ayudas mencionadas',
            'Salud mencionada',
            'Trámites mencionados',
            'Nota menciones',
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
                            InvitadoMencionesCatalog::etiquetasCsv(
                                $invitado->menciones_ayudas,
                                InvitadoMencionesCatalog::CATEGORIA_AYUDAS,
                            ),
                            InvitadoMencionesCatalog::etiquetasCsv(
                                $invitado->menciones_salud,
                                InvitadoMencionesCatalog::CATEGORIA_SALUD,
                            ),
                            InvitadoMencionesCatalog::etiquetasCsv(
                                $invitado->menciones_tramites,
                                InvitadoMencionesCatalog::CATEGORIA_TRAMITES,
                            ),
                            $invitado->menciones_nota,
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
            'HogarSolidario',
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
