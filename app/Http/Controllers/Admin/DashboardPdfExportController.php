<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReporteExportService;
use App\Support\OperacionFiltros;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardPdfExportController extends Controller
{
    public function __invoke(Request $request, ReporteExportService $service): Response
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $filtros = OperacionFiltros::fromArray($request->only([
            'desde',
            'hasta',
            'municipio_id',
            'parroquia_id',
            'refugio_id',
            'centro_acopio_id',
        ]));

        return $service->dashboardPdf($filtros);
    }
}
