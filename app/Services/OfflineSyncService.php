<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivityChannel;
use App\Enums\CondicionInvitado;
use App\Enums\RequerimientoEstatus;
use App\Models\Invitado;
use App\Models\Inventario;
use App\Models\OfflineSyncRecord;
use App\Models\Requerimiento;
use App\Models\User;
use App\Services\AnfitrionMobileProfileService;
use App\Support\ActivityLogContext;
use App\Support\HogarSolidarioValidationRules;
use App\Support\InsumoCatalog;
use App\Support\InvitadoCedula;
use App\Support\InvitadoMencionesCatalog;
use App\Support\VisitantesFeatures;
use App\Support\WitnessPhotoDecoder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Support\StorageErrorMessage;
use InvalidArgumentException;
use RuntimeException;

class OfflineSyncService
{
    public function __construct(
        private readonly InvitadoRegistrationService $invitadoRegistration,
        private readonly NucleoFamiliarOnboardingService $nucleoOnboarding,
        private readonly RequerimientoAsignacionService $requerimientoAsignacion,
        private readonly ActivityLogService $activityLog,
    ) {}

    /**
     * @param  list<array{client_id: string, type: string, payload: array<string, mixed>}>  $items
     * @return list<array{client_id: string, status: string, server_id?: int, error?: string}>
     */
    public function sync(User $user, array $items): array
    {
        return ActivityLogContext::using(
            ActivityChannel::OfflineSync,
            function () use ($user, $items): array {
                $results = [];
                $idMap = [];

                foreach ($items as $item) {
                    $clientId = (string) ($item['client_id'] ?? '');
                    ActivityLogContext::setClientId($clientId !== '' ? $clientId : null);

                    $type = (string) ($item['type'] ?? '');
                    $payload = (array) ($item['payload'] ?? []);

                    try {
                        if (! VisitantesFeatures::logistica() && in_array($type, [
                            'requerimiento.create',
                            'inventario.create',
                            'inventario.update_cantidad',
                            'entrega.marcar',
                        ], true)) {
                            throw new RuntimeException('El módulo de logística está deshabilitado.');
                        }

                        $serverId = match ($type) {
                            'invitado.registro' => $this->syncInvitadoRegistro($user, $payload, $clientId, $idMap),
                            'invitado.menciones' => $this->syncInvitadoMenciones($user, $payload, $idMap),
                            'requerimiento.create' => $this->syncRequerimientoCreate($user, $payload, $idMap),
                            'inventario.create' => $this->syncInventarioCreate($user, $payload),
                            'inventario.update_cantidad' => $this->syncInventarioUpdate($user, $payload),
                            'entrega.marcar' => $this->syncEntregaMarcar($user, $payload),
                            default => throw new RuntimeException("Tipo de sincronización desconocido: {$type}"),
                        };

                        $results[] = [
                            'client_id' => $clientId,
                            'status' => 'ok',
                            'server_id' => $serverId,
                        ];
                    } catch (\Throwable $e) {
                        report($e);

                        $results[] = [
                            'client_id' => $clientId,
                            'status' => 'error',
                            'error' => $this->syncErrorMessage($e),
                        ];
                    }
                }

                return $results;
            },
        );
    }

    private function syncErrorMessage(\Throwable $e): string
    {
        if ($e instanceof ValidationException) {
            $first = collect($e->errors())->flatten()->first();

            return is_string($first) ? $first : 'Datos del registro inválidos.';
        }

        $message = $e->getMessage();

        if ($e instanceof QueryException || str_contains($message, 'SQLSTATE')) {
            if (str_contains($message, 'offline_sync_records')) {
                return 'El servidor no está actualizado (migraciones pendientes). Contacte al administrador.';
            }

            return 'Error al guardar en la base de datos. Contacte al administrador.';
        }

        if (
            str_contains($message, 'InvalidAccessKeyId')
            || str_contains($message, 'SignatureDoesNotMatch')
            || str_contains($message, 'NoSuchBucket')
            || StorageErrorMessage::isStorageFailure($e)
        ) {
            return StorageErrorMessage::for($e);
        }

        if ($e instanceof InvalidArgumentException || str_contains($message, 'Formato de imagen')) {
            return 'Formato de foto no permitido. Use JPG, PNG o WebP.';
        }

        if (str_contains($message, 'decodificar') || str_contains($message, 'imagen válida')) {
            return 'La foto no pudo procesarse. Registre de nuevo con otra captura.';
        }

        if (str_contains($message, 'tamaño máximo')) {
            return 'La foto supera el tamaño máximo permitido (8 MB).';
        }

        if (str_contains($message, 'almacenamiento') || str_contains($message, 'storage')) {
            return 'No se pudo guardar la foto en el almacenamiento. Contacte al administrador.';
        }

        if (app()->environment('local', 'testing')) {
            return $message;
        }

        return 'No se pudo procesar la operación.';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, int>  $idMap
     */
    private function syncInvitadoRegistro(User $user, array $payload, string $clientId, array &$idMap): int
    {
        if (! $user->isAnfitrion()) {
            throw new RuntimeException('Solo anfitriones pueden registrar Invitados offline.');
        }

        $existing = OfflineSyncRecord::query()
            ->where('client_id', $clientId)
            ->where('user_id', $user->id)
            ->where('type', 'invitado.registro')
            ->first();

        if ($existing !== null) {
            $idMap[$clientId] = (int) $existing->server_id;

            return (int) $existing->server_id;
        }

        $profile = app(AnfitrionMobileProfileService::class);
        $registrarNuevoHogar = (bool) ($payload['registrar_nuevo_hogar'] ?? false);
        $payload = InvitadoCedula::normalizePayload($payload);

        $rules = [
            'registrar_nuevo_hogar' => ['sometimes', 'boolean'],
            'nombre' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'cedula' => InvitadoCedula::rules(),
            'telefono' => ['nullable', 'string', 'max:30'],
            'fecha_nacimiento' => ['required', 'date', 'before_or_equal:today'],
            'procedencia_estado_id' => ['required', 'integer', 'exists:estados,id'],
            'procedencia_municipio_id' => ['required', 'integer', 'exists:municipios,id'],
            'procedencia_parroquia_id' => ['required', 'integer', 'exists:parroquias,id'],
            'situacion_jefe' => ['required', 'string', 'in:trabajando,desempleado,pensionado,estudiante,otro'],
            'condicion' => CondicionInvitado::validationRules(),
            'familiares' => ['array'],
            'familiares.*.nombre' => ['required_with:familiares.*.apellido', 'string', 'max:255'],
            'familiares.*.apellido' => ['required_with:familiares.*.nombre', 'string', 'max:255'],
            'familiares.*.cedula' => InvitadoCedula::rules(),
            'familiares.*.telefono' => ['nullable', 'string', 'max:30'],
            'familiares.*.parentesco' => ['required_with:familiares.*.nombre', 'string', 'max:50'],
            'familiares.*.condicion' => array_merge(
                ['required_with:familiares.*.nombre'],
                CondicionInvitado::validationRules(false),
            ),
            'familiares.*.fecha_nacimiento' => ['required_with:familiares.*.nombre', 'date', 'before_or_equal:today'],
            'foto_base64' => ['nullable', 'string', 'max:12000000'],
            'foto_mime' => ['nullable', 'string', 'in:image/jpeg,image/png,image/webp'],
        ];

        if ($profile->debeEnviarDatosHogar($user, $registrarNuevoHogar)) {
            $rules = array_merge($rules, HogarSolidarioValidationRules::forPayload('hogar'));
        }

        $validator = Validator::make($payload, $rules, InvitadoCedula::validationMessages());
        $validator->after(function (\Illuminate\Validation\Validator $validator) use ($payload): void {
            InvitadoCedula::validateDistinctInPayload($validator, $payload);
        });
        $validated = $validator->validate();

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

        $jefe = DB::transaction(function () use ($user, $validated, $foto, $clientId, $hogarData): Invitado {
            $result = $this->nucleoOnboarding->register(
                $user->fresh(),
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

            $jefe = $result['jefe'];

            OfflineSyncRecord::query()->create([
                'client_id' => $clientId,
                'type' => 'invitado.registro',
                'server_id' => $jefe->id,
                'user_id' => $user->id,
            ]);

            return $jefe;
        });

        $idMap[$clientId] = $jefe->id;

        return $jefe->id;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, int>  $idMap
     */
    private function syncInvitadoMenciones(User $user, array $payload, array $idMap): int
    {
        if (! $user->isAnfitrion()) {
            throw new RuntimeException('Solo anfitriones pueden actualizar menciones offline.');
        }

        $invitadoId = $payload['invitado_id'] ?? null;
        if ($invitadoId === null && ! empty($payload['invitado_client_id'])) {
            $invitadoId = $idMap[$payload['invitado_client_id']] ?? null;
        }

        $validated = Validator::make(
            array_merge($payload, ['invitado_id' => $invitadoId]),
            array_merge(
                ['invitado_id' => ['required', 'integer', 'exists:invitados,id']],
                InvitadoMencionesCatalog::validationRules(),
            ),
        )->validate();

        $invitado = Invitado::query()->findOrFail((int) $validated['invitado_id']);

        Gate::authorize('update', $invitado);

        $before = $this->activityLog->snapshot($invitado);
        $invitado = app(InvitadoMencionesService::class)->update($invitado, $validated);

        $this->activityLog->updated(
            $invitado,
            $before,
            $this->activityLog->snapshot($invitado),
            'Menciones actualizadas (sync offline)',
        );

        return $invitado->id;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, int>  $idMap
     */
    private function syncRequerimientoCreate(User $user, array $payload, array $idMap): int
    {
        if (! $user->isAnfitrion()) {
            throw new RuntimeException('Solo anfitriones pueden crear requerimientos offline.');
        }

        $invitadoId = $payload['invitado_id'] ?? null;
        if ($invitadoId === null && ! empty($payload['invitado_client_id'])) {
            $invitadoId = $idMap[$payload['invitado_client_id']] ?? null;
        }

        $validated = Validator::make(array_merge($payload, ['invitado_id' => $invitadoId]), [
            'invitado_id' => ['required', 'integer', 'exists:invitados,id'],
            'categoria' => ['nullable', 'string', 'max:255'],
            'subcategoria' => ['nullable', 'string', 'max:255'],
            'item_solicitado' => ['nullable', 'string', 'max:255'],
            'cantidad' => ['required', 'integer', 'min:1'],
        ])->validate();

        $insumo = InsumoCatalog::normalizeRequerimiento($validated);

        $invitado = Invitado::query()->findOrFail($validated['invitado_id']);

        if ($user->hogar_solidario_id !== $invitado->hogar_solidario_id) {
            throw new RuntimeException('El Invitado no pertenece a su refugio.');
        }

        $req = Requerimiento::query()->create([
            'invitado_id' => $invitado->id,
            'anfitrion_id' => $user->id,
            'categoria' => $insumo['categoria'],
            'subcategoria' => $insumo['subcategoria'],
            'item_solicitado' => $insumo['item_solicitado'],
            'cantidad' => $validated['cantidad'],
            'estatus' => RequerimientoEstatus::Pendiente,
        ]);

        $this->activityLog->created($req, 'Requerimiento creado (sync offline)');

        return $req->id;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function syncInventarioCreate(User $user, array $payload): int
    {
        if (! $user->isCentroAcopio() || $user->centro_acopio_id === null) {
            throw new RuntimeException('Solo operadores de acopio pueden registrar inventario offline.');
        }

        $validated = Validator::make($payload, [
            'categoria' => ['nullable', 'string', 'max:255'],
            'subcategoria' => ['nullable', 'string', 'max:255'],
            'item_nombre' => ['nullable', 'string', 'max:255'],
            'cantidad' => ['required', 'integer', 'min:0'],
            'unidad_medida' => ['required', 'string', 'max:50'],
        ])->validate();

        $insumo = InsumoCatalog::normalizeInventario($validated);

        $item = Inventario::query()->create([
            'centro_acopio_id' => $user->centro_acopio_id,
            'categoria' => $insumo['categoria'],
            'subcategoria' => $insumo['subcategoria'],
            'item_nombre' => $insumo['item_nombre'],
            'cantidad' => $validated['cantidad'],
            'unidad_medida' => $validated['unidad_medida'],
        ]);

        $this->activityLog->created($item, 'Ítem de inventario creado (sync offline)');

        return $item->id;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function syncInventarioUpdate(User $user, array $payload): int
    {
        if (! $user->isCentroAcopio() || $user->centro_acopio_id === null) {
            throw new RuntimeException('Solo operadores de acopio pueden actualizar inventario offline.');
        }

        $validated = Validator::make($payload, [
            'inventario_id' => ['required', 'integer'],
            'cantidad' => ['required', 'integer', 'min:0'],
        ])->validate();

        $item = Inventario::query()
            ->where('centro_acopio_id', $user->centro_acopio_id)
            ->whereKey($validated['inventario_id'])
            ->firstOrFail();

        $before = $this->activityLog->snapshot($item);
        $item->update(['cantidad' => $validated['cantidad']]);
        $item->refresh();

        $diff = $this->activityLog->diff($before, $this->activityLog->snapshot($item));
        $this->activityLog->updated($item, $diff['old'], $diff['new'], 'Cantidad de inventario actualizada (sync offline)');

        return $item->id;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function syncEntregaMarcar(User $user, array $payload): int
    {
        if (! $user->isCentroAcopio() || $user->centro_acopio_id === null) {
            throw new RuntimeException('Solo operadores de acopio pueden marcar entregas offline.');
        }

        $validated = Validator::make($payload, [
            'requerimiento_id' => ['required', 'integer'],
        ])->validate();

        $requerimiento = Requerimiento::query()
            ->where('centro_acopio_id', $user->centro_acopio_id)
            ->whereKey($validated['requerimiento_id'])
            ->firstOrFail();

        Gate::forUser($user)->authorize('entregar', $requerimiento);

        $this->requerimientoAsignacion->marcarEntregado($requerimiento);

        return $requerimiento->id;
    }
}
