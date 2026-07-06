<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Enums\RequerimientoEstatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\MobileRequerimientoResource;
use App\Models\Requerimiento;
use App\Services\RequerimientoAsignacionService;
use App\Support\GeoDistance;
use App\Support\GeoNavigation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MobileEntregaController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Requerimiento::class);

        $user = $request->user();
        $centro = $user->centroAcopio;

        $asignados = Requerimiento::query()
            ->with(['invitado.refugio', 'anfitrion'])
            ->where('centro_acopio_id', $user->centro_acopio_id)
            ->where('estatus', RequerimientoEstatus::Asignado)
            ->latest()
            ->get()
            ->map(function (Requerimiento $req) use ($centro): Requerimiento {
                $refugio = $req->invitado?->refugio;

                if ($centro !== null && $refugio !== null) {
                    $req->setAttribute('centro_latitud', (float) $centro->latitud);
                    $req->setAttribute('centro_longitud', (float) $centro->longitud);

                    if ($centro->latitud !== null && $centro->longitud !== null) {
                        $req->setAttribute('distancia_km', GeoDistance::kilometers(
                            (float) $centro->latitud,
                            (float) $centro->longitud,
                            (float) $refugio->latitud,
                            (float) $refugio->longitud,
                        ));
                    }
                }

                if ($refugio !== null) {
                    $geo = self::geoLinksFor(
                        $req,
                        $centro?->latitud !== null ? (float) $centro->latitud : null,
                        $centro?->longitud !== null ? (float) $centro->longitud : null,
                    );
                    $req->setAttribute('ruta_url', $geo['ruta_url'] ?? null);
                    $req->setAttribute('refugio_url', $geo['refugio_url'] ?? null);
                }

                return $req;
            })
            ->sortBy(fn (Requerimiento $req) => $req->distancia_km ?? PHP_FLOAT_MAX)
            ->values();

        return MobileRequerimientoResource::collection($asignados);
    }

    public function entregar(Request $request, Requerimiento $requerimiento, RequerimientoAsignacionService $service): JsonResponse
    {
        $this->authorize('entregar', $requerimiento);

        $service->marcarEntregado($requerimiento);

        return response()->json([
            'message' => 'Entrega registrada correctamente.',
            'data' => new MobileRequerimientoResource($requerimiento->fresh(['invitado', 'centroAcopio'])),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public static function geoLinksFor(Requerimiento $requerimiento, ?float $centroLat, ?float $centroLng): array
    {
        $refugio = $requerimiento->invitado?->refugio;
        if ($refugio === null) {
            return [];
        }

        $links = [
            'refugio_url' => GeoNavigation::mapsQueryUrl(
                (float) $refugio->latitud,
                (float) $refugio->longitud,
            ),
        ];

        if ($centroLat !== null && $centroLng !== null) {
            $links['ruta_url'] = GeoNavigation::directionsUrl(
                $centroLat,
                $centroLng,
                (float) $refugio->latitud,
                (float) $refugio->longitud,
            );
        }

        return $links;
    }
}
