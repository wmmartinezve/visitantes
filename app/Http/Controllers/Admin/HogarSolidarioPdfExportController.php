<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HogarSolidario;
use App\Services\ReporteExportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HogarSolidarioPdfExportController extends Controller
{
    public function __invoke(
        Request $request,
        HogarSolidario $hogarSolidario,
        ReporteExportService $service,
    ): Response {
        abort_unless($request->user()?->isAdmin(), 403);

        return $service->hogarSolidarioFichaPdf($hogarSolidario);
    }
}
