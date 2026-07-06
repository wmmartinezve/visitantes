<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MobileCentroAcopioResource;
use App\Models\CentroAcopio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MobileCentroAcopioController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $centro = $this->centroDelOperador($request);

        return response()->json([
            'data' => new MobileCentroAcopioResource($centro),
        ]);
    }

    public function updateGeolocalizacion(Request $request): JsonResponse
    {
        $centro = $this->centroDelOperador($request);

        if (! $centro->geolocalizacionEditableDesdeApp()) {
            throw ValidationException::withMessages([
                'geolocalizacion' => ['La georreferenciación del centro ya fue fijada y no puede modificarse desde la app.'],
            ]);
        }

        $this->authorize('updateGeolocalizacion', $centro);

        $data = $request->validate([
            'latitud' => ['required', 'numeric', 'between:8,11.5'],
            'longitud' => ['required', 'numeric', 'between:-67,-62'],
            'direccion_exacta' => ['nullable', 'string', 'max:500'],
        ]);

        $centro->update([
            'latitud' => $data['latitud'],
            'longitud' => $data['longitud'],
            'direccion_exacta' => $data['direccion_exacta'] ?? $centro->direccion_exacta,
            'geolocalizacion_fijada_en' => now(),
        ]);

        return response()->json([
            'message' => 'Georreferenciación del centro registrada correctamente. No podrá modificarse nuevamente.',
            'data' => new MobileCentroAcopioResource($centro->fresh()),
        ]);
    }

    private function centroDelOperador(Request $request): CentroAcopio
    {
        $user = $request->user();

        abort_unless($user?->isCentroAcopio() && $user->centro_acopio_id, 403, 'Solo operadores de centro de acopio.');

        return CentroAcopio::query()->findOrFail($user->centro_acopio_id);
    }
}
