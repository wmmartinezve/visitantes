<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\HogarSolidario;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

/**
 * Perfil móvil del anfitrión.
 *
 * Modelo 1:N — anfitrión → hogares solidarios (sin relación entre hogares):
 * - Cada fila en `hogares_solidarios` es independiente (código, dirección, núcleo propios).
 * - `anfitrion_user_id` es la única vinculación de propiedad anfitrión ↔ hogar.
 * - `users.hogar_solidario_id` es solo el hogar **activo en la app** (puntero operativo), no une hogares entre sí.
 */
class AnfitrionMobileProfileService
{
    /**
     * Ajusta el hogar activo: debe pertenecer al anfitrión o el más reciente creado por él.
     */
    public function normalize(User $user): User
    {
        if (! $user->isAnfitrion()) {
            return $user;
        }

        $hogares = $this->hogaresDelAnfitrion($user);

        if ($hogares->isEmpty()) {
            if ($user->hogar_solidario_id !== null || $user->hogar_vinculado_en !== null) {
                $user->forceFill([
                    'hogar_solidario_id' => null,
                    'hogar_vinculado_en' => null,
                ])->save();
            }

            return $user->fresh(['hogarSolidario', 'centroAcopio']);
        }

        $activoValido = $user->hogar_solidario_id !== null
            && $hogares->contains('id', $user->hogar_solidario_id);

        if (! $activoValido) {
            /** @var HogarSolidario $reciente */
            $reciente = $hogares->first();
            $user->forceFill([
                'hogar_solidario_id' => $reciente->id,
                'hogar_vinculado_en' => $user->hogar_vinculado_en ?? now(),
            ])->save();
        }

        return $user->fresh(['hogarSolidario', 'centroAcopio']);
    }

    public function requiereRegistroHogar(User $user): bool
    {
        return $user->isAnfitrion() && $this->countHogares($user) === 0;
    }

    public function puedeRegistrarOtroHogar(User $user): bool
    {
        return $user->isAnfitrion() && $this->countHogares($user) > 0;
    }

    public function countHogares(User $user): int
    {
        if (! $user->isAnfitrion()) {
            return 0;
        }

        return HogarSolidario::query()
            ->where('anfitrion_user_id', $user->id)
            ->count();
    }

    /** @return Collection<int, HogarSolidario> */
    public function hogaresDelAnfitrion(User $user): Collection
    {
        if (! $user->isAnfitrion()) {
            return new Collection;
        }

        return HogarSolidario::query()
            ->where('anfitrion_user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();
    }

    public function hogarPerteneceAlAnfitrion(User $user, int $hogarId): bool
    {
        return HogarSolidario::query()
            ->whereKey($hogarId)
            ->where('anfitrion_user_id', $user->id)
            ->exists();
    }

    public function tieneNucleoFamiliar(User $user): bool
    {
        if (! $user->isAnfitrion() || $user->hogar_solidario_id === null) {
            return false;
        }

        $hogar = $user->hogarSolidario;

        return $hogar !== null && $hogar->tieneNucleoFamiliar();
    }

    public function hogarActivoTieneNucleo(User $user): bool
    {
        return $this->tieneNucleoFamiliar($user);
    }

    public function debeEnviarDatosHogar(User $user, bool $registrarNuevoHogar = false): bool
    {
        if ($registrarNuevoHogar) {
            return true;
        }

        return $this->requiereRegistroHogar($user);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function hogaresParaApi(User $user): array
    {
        return $this->hogaresDelAnfitrion($user)
            ->map(fn (HogarSolidario $hogar): array => [
                'id' => $hogar->id,
                'codigo' => $hogar->codigo,
                'nombre' => $hogar->codigo,
                'direccion_exacta' => $hogar->direccion_exacta,
                'tiene_nucleo_familiar' => $hogar->tieneNucleoFamiliar(),
                'activo' => $user->hogar_solidario_id === $hogar->id,
            ])
            ->values()
            ->all();
    }
}
