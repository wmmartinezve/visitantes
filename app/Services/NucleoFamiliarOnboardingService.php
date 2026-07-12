<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivityAction;
use App\Enums\TipoAnfitrionHogar;
use App\Enums\TipoViviendaHogar;
use App\Models\Comuna;
use App\Models\HogarSolidario;
use App\Models\Parroquia;
use App\Models\Invitado;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class NucleoFamiliarOnboardingService
{
    public function __construct(
        private readonly InvitadoRegistrationService $invitadoRegistration,
        private readonly ActivityLogService $activityLog,
    ) {}

    /**
     * Crea el hogar solidario (si el anfitrión aún no tiene uno) y registra el núcleo familiar.
     *
     * @param  array<string, mixed>|null  $hogarData  Requerido cuando el anfitrión no tiene hogar asignado.
     * @param  array<string, mixed>  $jefeData
     * @param  list<array<string, mixed>>  $familiares
     * @return array{hogar: HogarSolidario, jefe: Invitado, hogar_creado: bool, anfitrion: User}
     */
    public function register(
        User $anfitrion,
        ?array $hogarData,
        array $jefeData,
        ?UploadedFile $foto,
        array $familiares = [],
    ): array {
        if (! $anfitrion->isAnfitrion()) {
            throw new InvalidArgumentException('Solo anfitriones pueden registrar un núcleo familiar.');
        }

        return DB::transaction(function () use ($anfitrion, $hogarData, $jefeData, $foto, $familiares): array {
            $hogarCreado = false;
            $registrarNuevoHogar = $hogarData !== null;

            if ($registrarNuevoHogar) {
                $hogar = $this->createHogar($hogarData, $anfitrion);
                $anfitrion->forceFill([
                    'hogar_solidario_id' => $hogar->id,
                    'hogar_vinculado_en' => now(),
                ])->save();
                $anfitrion->refresh();
                $hogarCreado = true;
            } else {
                if ($anfitrion->hogar_solidario_id === null) {
                    throw ValidationException::withMessages([
                        'hogar' => ['Debe registrar los datos del hogar solidario.'],
                    ]);
                }

                $hogar = HogarSolidario::query()->findOrFail($anfitrion->hogar_solidario_id);

                if ((int) $hogar->anfitrion_user_id !== (int) $anfitrion->id) {
                    throw ValidationException::withMessages([
                        'hogar' => ['El hogar activo no pertenece a este anfitrión.'],
                    ]);
                }

                if ($hogar->tieneNucleoFamiliar()) {
                    throw ValidationException::withMessages([
                        'hogar' => [
                            'Este hogar ya tiene un núcleo familiar. '
                            .'Registre un nuevo hogar solidario para otro núcleo.',
                        ],
                    ]);
                }
            }

            $anfitrion = $anfitrion->fresh(['hogarSolidario']);

            $jefe = $this->invitadoRegistration->registerForHogar($hogar->id, $jefeData, $foto, $familiares);

            return [
                'hogar' => $hogar->fresh(['jefeFamilia', 'comuna']),
                'jefe' => $jefe,
                'hogar_creado' => $hogarCreado,
                'anfitrion' => $anfitrion,
            ];
        });
    }

    /**
     * Registro desde panel admin: crea hogar + jefe + familiares (sin anfitrión obligatorio).
     *
     * @param  array<string, mixed>  $hogarData
     * @param  array<string, mixed>  $jefeData
     * @param  list<array<string, mixed>>  $familiares
     * @return array{hogar: HogarSolidario, jefe: Invitado}
     */
    public function registerFromAdmin(
        array $hogarData,
        array $jefeData,
        ?UploadedFile $foto,
        array $familiares = [],
        ?int $anfitrionId = null,
    ): array {
        return DB::transaction(function () use ($hogarData, $jefeData, $foto, $familiares, $anfitrionId): array {
            $hogar = $this->createHogar($hogarData, $anfitrionId !== null ? User::query()->find($anfitrionId) : null);

            if ($anfitrionId !== null) {
                $anfitrion = User::query()->findOrFail($anfitrionId);

                if (! $anfitrion->isAnfitrion()) {
                    throw ValidationException::withMessages([
                        'anfitrion_id' => ['El usuario seleccionado no es anfitrión.'],
                    ]);
                }

                if ($anfitrion->hogar_solidario_id !== null && $anfitrion->hogar_solidario_id !== $hogar->id) {
                    // El anfitrión puede tener varios hogares; activar el recién creado.
                }

                $anfitrion->forceFill([
                    'hogar_solidario_id' => $hogar->id,
                    'hogar_vinculado_en' => now(),
                ])->save();
                $anfitrion = $anfitrion->fresh();
            } else {
                $anfitrion = null;
            }

            $jefe = $this->invitadoRegistration->registerForHogar($hogar->id, $jefeData, $foto, $familiares);

            return [
                'hogar' => $hogar->fresh(['jefeFamilia', 'comuna']),
                'jefe' => $jefe,
            ];
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createHogar(array $data, ?User $anfitrion = null): HogarSolidario
    {
        $parroquiaId = (int) $data['parroquia_id'];
        $comunaId = filled($data['comuna_id'] ?? null) ? (int) $data['comuna_id'] : null;

        if ($comunaId !== null) {
            $comuna = Comuna::query()->findOrFail($comunaId);

            if ($parroquiaId !== (int) $comuna->parroquia_id) {
                throw ValidationException::withMessages([
                    'hogar.parroquia_id' => ['La comuna no pertenece a la parroquia seleccionada.'],
                ]);
            }
        } else {
            Parroquia::query()->findOrFail($parroquiaId);
        }

        $tipoAnfitrion = TipoAnfitrionHogar::from($data['tipo_anfitrion']);

        if ($tipoAnfitrion === TipoAnfitrionHogar::Familiar && blank($data['parentesco_anfitrion'] ?? null)) {
            throw ValidationException::withMessages([
                'hogar.parentesco_anfitrion' => ['Indique el parentesco cuando el hogar solidario es de un familiar.'],
            ]);
        }

        $hogar = HogarSolidario::query()->create([
            'anfitrion_user_id' => $anfitrion?->id,
            'parroquia_id' => $parroquiaId,
            'comuna_id' => $comunaId,
            'tipo_vivienda' => TipoViviendaHogar::from($data['tipo_vivienda']),
            'tipo_anfitrion' => $tipoAnfitrion,
            'parentesco_anfitrion' => $tipoAnfitrion === TipoAnfitrionHogar::Familiar
                ? ($data['parentesco_anfitrion'] ?? null)
                : null,
            'responsable_nombre' => $data['responsable_nombre'],
            'responsable_cedula' => $data['responsable_cedula'] ?? null,
            'responsable_telefono' => $data['responsable_telefono'] ?? null,
            'habitantes' => $data['habitantes'] ?? [],
            'latitud' => $data['latitud'],
            'longitud' => $data['longitud'],
            'direccion_exacta' => $data['direccion_exacta'],
        ]);

        $this->activityLog->log(
            ActivityAction::Created,
            $hogar,
            'Hogar solidario creado al registrar núcleo familiar',
        );

        return $hogar;
    }

}
