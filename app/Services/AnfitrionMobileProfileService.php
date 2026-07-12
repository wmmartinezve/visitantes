<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

/**
 * Perfil móvil del anfitrión: el hogar solo es válido si fue vinculado vía wizard (hogar_vinculado_en).
 */
class AnfitrionMobileProfileService
{
    /**
     * Elimina asignaciones admin/demo sin wizard (sin hogar_vinculado_en).
     */
    public function normalize(User $user): User
    {
        if (! $user->isAnfitrion() || $user->hogar_solidario_id === null) {
            return $user;
        }

        if ($this->asignacionHogarEsValida($user)) {
            return $user;
        }

        $user->forceFill([
            'hogar_solidario_id' => null,
            'hogar_vinculado_en' => null,
        ])->save();

        return $user->fresh(['hogarSolidario', 'centroAcopio']);
    }

    public function requiereRegistroHogar(User $user): bool
    {
        return $user->isAnfitrion() && $user->hogar_solidario_id === null;
    }

    public function tieneNucleoFamiliar(User $user): bool
    {
        if (! $user->isAnfitrion() || $user->hogar_solidario_id === null) {
            return false;
        }

        $hogar = $user->hogarSolidario;

        return $hogar !== null && $hogar->tieneNucleoFamiliar();
    }

    private function asignacionHogarEsValida(User $user): bool
    {
        return $user->hogar_solidario_id !== null && $user->hogar_vinculado_en !== null;
    }
}
