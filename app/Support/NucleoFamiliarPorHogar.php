<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Invitado;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class NucleoFamiliarPorHogar
{
    public static function jefeEnHogar(int $hogarSolidarioId, ?int $exceptInvitadoId = null): ?Invitado
    {
        return Invitado::query()
            ->where('hogar_solidario_id', $hogarSolidarioId)
            ->whereNull('jefe_familia_id')
            ->when($exceptInvitadoId !== null, fn ($q) => $q->where('id', '!=', $exceptInvitadoId))
            ->first();
    }

    public static function hogarTieneNucleo(int $hogarSolidarioId, ?int $exceptInvitadoId = null): bool
    {
        return self::jefeEnHogar($hogarSolidarioId, $exceptInvitadoId) !== null;
    }

    /**
     * @throws ValidationException
     */
    public static function assertPuedeRegistrarJefe(int $hogarSolidarioId, ?int $exceptInvitadoId = null): void
    {
        if (! self::hogarTieneNucleo($hogarSolidarioId, $exceptInvitadoId)) {
            return;
        }

        throw ValidationException::withMessages([
            'hogar_solidario_id' => [
                'Este hogar solidario ya tiene un núcleo familiar registrado. '
                .'Solo puede existir un jefe de familia por hogar; agregue familiares al núcleo existente.',
            ],
        ]);
    }

    /**
     * Corrige hogares con más de un jefe (jefe_familia_id NULL). Conserva el registro con menor id.
     *
     * @return int Número de jefes duplicados reasignados como familiares
     */
    public static function deduplicarJefesPorHogar(): int
    {
        $reasignados = 0;

        $hogaresConDuplicados = DB::table('invitados')
            ->select('hogar_solidario_id')
            ->whereNull('jefe_familia_id')
            ->whereNull('deleted_at')
            ->whereNotNull('hogar_solidario_id')
            ->groupBy('hogar_solidario_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('hogar_solidario_id');

        foreach ($hogaresConDuplicados as $hogarId) {
            $jefes = DB::table('invitados')
                ->where('hogar_solidario_id', $hogarId)
                ->whereNull('jefe_familia_id')
                ->whereNull('deleted_at')
                ->orderBy('id')
                ->get(['id', 'parentesco']);

            if ($jefes->count() <= 1) {
                continue;
            }

            $jefePrincipal = $jefes->first();

            foreach ($jefes->slice(1) as $duplicado) {
                DB::table('invitados')
                    ->where('jefe_familia_id', $duplicado->id)
                    ->whereNull('deleted_at')
                    ->update(['jefe_familia_id' => $jefePrincipal->id]);

                DB::table('invitados')
                    ->where('id', $duplicado->id)
                    ->update([
                        'jefe_familia_id' => $jefePrincipal->id,
                        'parentesco' => filled($duplicado->parentesco) ? $duplicado->parentesco : 'Otro',
                    ]);

                $reasignados++;
            }
        }

        return $reasignados;
    }
}
