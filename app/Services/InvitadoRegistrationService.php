<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InvitadoEstatus;
use App\Models\Invitado;
use App\Models\User;
use App\Support\InvitadoFotoStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvitadoRegistrationService
{
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

            if ($foto !== null) {
                $jefe->update([
                    'foto_ingreso' => $this->storeFoto($foto, $jefe->id),
                ]);
            }

            foreach ($familiares as $familiar) {
                if (blank($familiar['nombre'] ?? null) || blank($familiar['apellido'] ?? null)) {
                    continue;
                }

                Invitado::query()->create([
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
