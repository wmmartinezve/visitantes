<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

/**
 * Perfil móvil del anfitrión: el hogar solo es válido si lo creó ese anfitrión (anfitrion_user_id).
 */
class AnfitrionMobileProfileService
{
    /**
     * Elimina asignaciones admin/demo/reutilizadas de hogares ajenos.
     */
    public function normalize(User $user): User
    {
        if (! $user->isAnfitrion() || $user->hogar_solidario_id === null) {
            return $user;
        }

        $user->loadMissing('hogarSolidario');

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
        if ($user->hogar_solidario_id === null) {
            return false;
        }

        $hogar = $user->hogarSolidario;

        return $hogar !== null
            && $hogar->anfitrion_user_id !== null
            && (int) $hogar->anfitrion_user_id === (int) $user->id;
    }
}
