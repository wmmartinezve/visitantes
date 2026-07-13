<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MobileInvitadoFotoRequest;
use App\Http\Requests\MobileInvitadoMencionesRequest;
use App\Http\Requests\MobileInvitadoStoreRequest;
use App\Http\Resources\MobileInvitadoResource;
use App\Http\Resources\MobileUserResource;
use App\Models\Invitado;
use App\Services\AnfitrionMobileProfileService;
use App\Services\InvitadoMencionesService;
use App\Services\InvitadoRegistrationService;
use App\Services\NucleoFamiliarOnboardingService;
use App\Support\WitnessPhotoDecoder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MobileInvitadoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Invitado::class);

        $user = $request->user();
        $busqueda = trim((string) $request->query('q', ''));

        if (! $user->isAnfitrion()) {
            return MobileInvitadoResource::collection(collect());
        }

        $profile = app(AnfitrionMobileProfileService::class);

        $query = $profile->invitadosDelAnfitrionQuery($user)
            ->with(['jefeFamilia', 'hogarSolidario']);

        if ($busqueda !== '') {
            $term = '%'.mb_strtolower($busqueda).'%';
            $query->where(function ($q) use ($term): void {
                $q->whereRaw('LOWER(nombre) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(apellido) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(cedula) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(parentesco) LIKE ?', [$term]);
            });
        }

        return MobileInvitadoResource::collection(
            $query
                ->orderByRaw('COALESCE(jefe_familia_id, id)')
                ->orderByRaw('CASE WHEN jefe_familia_id IS NULL THEN 0 ELSE 1 END')
                ->latest('id')
                ->limit(200)
                ->get(),
        );
    }

    public function show(Request $request, Invitado $invitado): MobileInvitadoResource
    {
        $this->authorize('view', $invitado);

        $invitado->load(['miembrosFamilia', 'requerimientos.centroAcopio']);

        return new MobileInvitadoResource($invitado);
    }

    public function store(
        MobileInvitadoStoreRequest $request,
        NucleoFamiliarOnboardingService $onboarding,
    ): JsonResponse {
        $this->authorize('create', Invitado::class);

        $validated = $request->validated();
        $user = $request->user();
        $profile = app(AnfitrionMobileProfileService::class);
        $registrarNuevoHogar = $request->boolean('registrar_nuevo_hogar');

        $foto = null;
        if (! empty($validated['foto_base64'])) {
            $foto = WitnessPhotoDecoder::toUploadedFile(
                $validated['foto_base64'],
                $validated['foto_mime'] ?? 'image/jpeg',
            );
        }

        $hogarData = $profile->debeEnviarDatosHogar($user, $registrarNuevoHogar)
            ? ($validated['hogar'] ?? null)
            : null;

        $result = $onboarding->register(
            $user,
            $hogarData,
            [
                'nombre' => $validated['nombre'],
                'apellido' => $validated['apellido'],
                'cedula' => $validated['cedula'] ?? null,
                'telefono' => $validated['telefono'] ?? null,
                'fecha_nacimiento' => $validated['fecha_nacimiento'],
                'procedencia_estado_id' => $validated['procedencia_estado_id'],
                'procedencia_municipio_id' => $validated['procedencia_municipio_id'],
                'procedencia_parroquia_id' => $validated['procedencia_parroquia_id'],
                'situacion_jefe' => $validated['situacion_jefe'],
                'condicion' => $validated['condicion'],
            ],
            $foto,
            $validated['familiares'] ?? [],
        );

        $jefe = $result['jefe']->load(['miembrosFamilia', 'hogarSolidario']);

        $payload = [
            'data' => new MobileInvitadoResource($jefe),
            'hogar_creado' => $result['hogar_creado'],
        ];

        if ($result['hogar_creado']) {
            $payload['user'] = new MobileUserResource(
                $result['anfitrion']->load('hogarSolidario'),
            );
        } else {
            $payload['user'] = new MobileUserResource(
                $result['anfitrion']->fresh(['hogarSolidario']),
            );
        }

        return response()->json($payload, 201);
    }

    public function updateFoto(
        MobileInvitadoFotoRequest $request,
        Invitado $invitado,
        InvitadoRegistrationService $registration,
    ): MobileInvitadoResource {
        $validated = $request->validated();

        $foto = WitnessPhotoDecoder::toUploadedFile(
            $validated['foto_base64'],
            $validated['foto_mime'],
        );

        $jefe = $registration->attachFoto($invitado, $foto);

        return new MobileInvitadoResource($jefe->load(['miembrosFamilia', 'hogarSolidario']));
    }

    public function updateMenciones(
        MobileInvitadoMencionesRequest $request,
        Invitado $invitado,
        InvitadoMencionesService $menciones,
    ): MobileInvitadoResource {
        $this->authorize('update', $invitado);

        $invitado = $menciones->update($invitado, $request->validated());

        return new MobileInvitadoResource($invitado->load(['miembrosFamilia', 'hogarSolidario']));
    }
}
