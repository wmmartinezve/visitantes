<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\RequerimientoEstatus;
use App\Models\Invitado;
use App\Models\Inventario;
use App\Models\OfflineSyncRecord;
use App\Models\Requerimiento;
use App\Models\User;
use App\Support\InsumoCatalog;
use App\Support\WitnessPhotoDecoder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use League\Flysystem\FilesystemException;
use RuntimeException;

class OfflineSyncService
{
    public function __construct(
        private readonly InvitadoRegistrationService $invitadoRegistration,
        private readonly RequerimientoAsignacionService $requerimientoAsignacion,
    ) {}

    /**
     * @param  list<array{client_id: string, type: string, payload: array<string, mixed>}>  $items
     * @return list<array{client_id: string, status: string, server_id?: int, error?: string}>
     */
    public function sync(User $user, array $items): array
    {
        $results = [];
        $idMap = [];

        foreach ($items as $item) {
            $clientId = (string) ($item['client_id'] ?? '');
            $type = (string) ($item['type'] ?? '');
            $payload = (array) ($item['payload'] ?? []);

            try {
                $serverId = match ($type) {
                    'invitado.registro' => $this->syncInvitadoRegistro($user, $payload, $clientId, $idMap),
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
    }

    private function syncErrorMessage(\Throwable $e): string
    {
        if ($e instanceof ValidationException) {
            $first = collect($e->errors())->flatten()->first();

            return is_string($first) ? $first : 'Datos del registro inválidos.';
        }

        $message = $e->getMessage();

        if ($e instanceof FilesystemException || str_contains($message, 'Unable to write')) {
            return 'No se pudo guardar la foto en el almacenamiento. Contacte al administrador.';
        }

        if (str_contains($message, 'decodificar') || str_contains($message, 'imagen válida')) {
            return 'La foto no pudo procesarse. Registre de nuevo con otra captura.';
        }

        if (str_contains($message, 'tamaño máximo')) {
            return 'La foto supera el tamaño máximo permitido (8 MB).';
        }

        if (app()->environment('local')) {
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
        if (! $user->isAnfitrion() || $user->refugio_id === null) {
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

        $validated = Validator::make($payload, [
            'nombre' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'cedula' => ['nullable', 'string', 'max:20'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'fecha_nacimiento' => ['required', 'date', 'before_or_equal:today'],
            'familiares' => ['array'],
            'familiares.*.nombre' => ['required_with:familiares.*.apellido', 'string', 'max:255'],
            'familiares.*.apellido' => ['required_with:familiares.*.nombre', 'string', 'max:255'],
            'familiares.*.cedula' => ['nullable', 'string', 'max:20'],
            'familiares.*.telefono' => ['nullable', 'string', 'max:30'],
            'familiares.*.parentesco' => ['required_with:familiares.*.nombre', 'string', 'max:50'],
            'familiares.*.fecha_nacimiento' => ['required_with:familiares.*.nombre', 'date', 'before_or_equal:today'],
            'foto_base64' => ['nullable', 'string', 'max:12000000'],
            'foto_mime' => ['nullable', 'string', 'in:image/jpeg,image/png,image/webp'],
        ])->validate();

        $foto = null;
        if (! empty($validated['foto_base64'])) {
            $foto = WitnessPhotoDecoder::toUploadedFile(
                $validated['foto_base64'],
                $validated['foto_mime'] ?? 'image/jpeg',
            );
        }

        $jefe = DB::transaction(function () use ($user, $validated, $foto, $clientId): Invitado {
            $jefe = $this->invitadoRegistration->register(
                $user,
                [
                    'nombre' => $validated['nombre'],
                    'apellido' => $validated['apellido'],
                    'cedula' => $validated['cedula'] ?? null,
                    'telefono' => $validated['telefono'] ?? null,
                    'fecha_nacimiento' => $validated['fecha_nacimiento'],
                ],
                $foto,
                $validated['familiares'] ?? [],
            );

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

        if ($user->refugio_id !== $invitado->refugio_id) {
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

        $item->update(['cantidad' => $validated['cantidad']]);

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
