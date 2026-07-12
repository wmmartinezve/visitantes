<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MobileInvitadoFotoRequest;
use App\Http\Requests\MobileInvitadoStoreRequest;
use App\Http\Resources\MobileInvitadoResource;
use App\Models\Invitado;
use App\Services\InvitadoRegistrationService;
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

        $query = Invitado::query()
            ->with(['jefeFamilia'])
            ->where('hogar_solidario_id', $user->hogar_solidario_id);

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
        InvitadoRegistrationService $registration,
    ): JsonResponse {
        $this->authorize('create', Invitado::class);

        $validated = $request->validated();
        $user = $request->user();

        $foto = null;
        if (! empty($validated['foto_base64'])) {
            $foto = WitnessPhotoDecoder::toUploadedFile(
                $validated['foto_base64'],
                $validated['foto_mime'] ?? 'image/jpeg',
            );
        }

        $jefe = $registration->register(
            $user,
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
            ],
            $foto,
            $validated['familiares'] ?? [],
        );

        return (new MobileInvitadoResource($jefe->load(['miembrosFamilia', 'hogarSolidario'])))
            ->response()
            ->setStatusCode(201);
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
}
