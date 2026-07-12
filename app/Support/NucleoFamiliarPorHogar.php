<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Invitado;
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
}
