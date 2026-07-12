<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MobileUserResource;
use App\Models\HogarSolidario;
use App\Services\AnfitrionMobileProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileHogarController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user->isAnfitrion(), 403);

        $profile = app(AnfitrionMobileProfileService::class);
        $user = $profile->normalize($user);

        return response()->json([
            'data' => $profile->hogaresParaApi($user),
            'hogar_activo_id' => $user->hogar_solidario_id,
            'hogares_count' => $profile->countHogares($user),
            'invitados_count' => $profile->countInvitadosDelAnfitrion($user),
        ]);
    }

    public function show(Request $request, HogarSolidario $hogarSolidario): JsonResponse
    {
        $user = $request->user();

        abort_unless($user->isAnfitrion(), 403);

        $profile = app(AnfitrionMobileProfileService::class);

        abort_unless(
            $profile->hogarPerteneceAlAnfitrion($user, $hogarSolidario->id),
            403,
            'El hogar no pertenece a este anfitrión.',
        );

        return response()->json([
            'data' => $profile->hogarDetalleParaApi($hogarSolidario),
        ]);
    }

    public function activar(Request $request): MobileUserResource
    {
        $user = $request->user();

        abort_unless($user->isAnfitrion(), 403);

        $validated = $request->validate([
            'hogar_solidario_id' => ['required', 'integer', 'exists:hogares_solidarios,id'],
        ]);

        $profile = app(AnfitrionMobileProfileService::class);

        abort_unless(
            $profile->hogarPerteneceAlAnfitrion($user, (int) $validated['hogar_solidario_id']),
            403,
            'El hogar no pertenece a este anfitrión.',
        );

        $user->forceFill([
            'hogar_solidario_id' => (int) $validated['hogar_solidario_id'],
        ])->save();

        $user = $profile->normalize($user->fresh(['hogarSolidario', 'centroAcopio']));

        return new MobileUserResource($user);
    }
}
