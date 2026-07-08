<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InvitadoEstatus;
use App\Enums\ActivityAction;
use App\Models\Invitado;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Support\InvitadoFotoStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class InvitadoRegistrationService
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
    ) {}

    /**
     * @param  array<string, mixed>  $jefeData
     * @param  list<array<string, mixed>>  $familiares
     */
    public function register(User $anfitrion, array $jefeData, ?UploadedFile $foto, array $familiares = []): Invitado
    {
        return DB::transaction(function () use ($anfitrion, $jefeData, $foto, $familiares): Invitado {
            $jefe = Invitado::query()->create([
                'nombre' => $jefeData['nombre'],
                'apellido' => $jefeData['apellido'],
                'cedula' => $jefeData['cedula'] ?? null,
                'fecha_nacimiento' => $jefeData['fecha_nacimiento'],
                'telefono' => $jefeData['telefono'] ?? null,
                'refugio_id' => $anfitrion->refugio_id,
                'estatus' => InvitadoEstatus::Activo,
                'jefe_familia_id' => null,
            ]);

            $this->activityLog->created(
                $jefe,
                'Jefe de familia registrado',
            );

            if ($foto !== null) {
                $path = $this->storeFoto($foto, $jefe->id);
                $jefe->update(['foto_ingreso' => $path]);

                $this->activityLog->log(
                    ActivityAction::FotoAttached,
                    $jefe->fresh(),
                    'Foto testigo de ingreso',
                    ['new' => ['foto_ingreso' => $path]],
                );
            }

            foreach ($familiares as $familiar) {
                if (blank($familiar['nombre'] ?? null) || blank($familiar['apellido'] ?? null)) {
                    continue;
                }

                $miembro = Invitado::query()->create([
                    'nombre' => $familiar['nombre'],
                    'apellido' => $familiar['apellido'],
                    'parentesco' => $familiar['parentesco'] ?? null,
                    'cedula' => $familiar['cedula'] ?? null,
                    'fecha_nacimiento' => $familiar['fecha_nacimiento'],
                    'telefono' => $familiar['telefono'] ?? null,
                    'refugio_id' => $anfitrion->refugio_id,
                    'estatus' => InvitadoEstatus::Activo,
                    'jefe_familia_id' => $jefe->id,
                ]);

                $this->activityLog->created(
                    $miembro,
                    'Familiar registrado',
                );
            }

            return $jefe->fresh(['miembrosFamilia', 'refugio']);
        });
    }

    public function attachFoto(Invitado $jefe, UploadedFile $foto): Invitado
    {
        if (! $jefe->esJefeDeFamilia()) {
            throw new InvalidArgumentException('Solo el jefe de familia puede tener foto testigo.');
        }

        return DB::transaction(function () use ($jefe, $foto): Invitado {
            $previous = $jefe->foto_ingreso;
            $path = $this->storeFoto($foto, $jefe->id);

            $jefe->update(['foto_ingreso' => $path]);

            $this->activityLog->log(
                ActivityAction::FotoAttached,
                $jefe->fresh(),
                'Foto testigo de ingreso',
                [
                    'old' => ['foto_ingreso' => $previous],
                    'new' => ['foto_ingreso' => $path],
                ],
            );

            if ($previous !== null && $previous !== $path) {
                $disk = InvitadoFotoStorage::diskForPath($previous);
                if ($disk !== null) {
                    Storage::disk($disk)->delete($previous);
                }
            }

            return $jefe->fresh(['miembrosFamilia', 'refugio']);
        });
    }

    private function storeFoto(UploadedFile $foto, int $invitadoId): string
    {
        $extension = strtolower($foto->extension() ?: 'jpg');
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (! in_array($extension, $allowed, true)) {
            throw new \InvalidArgumentException('Formato de imagen no permitido.');
        }

        $filename = Str::uuid().'.'.$extension;

        return InvitadoFotoStorage::storeUploadedFile(
            $foto,
            $invitadoId,
            $filename,
        );
    }
}
