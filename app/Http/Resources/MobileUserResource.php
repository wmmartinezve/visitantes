<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use App\Services\AnfitrionMobileProfileService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class MobileUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var User $user */
        $user = $this->resource;
        $profile = app(AnfitrionMobileProfileService::class);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'rol' => $user->rol->value,
            'rol_label' => $user->rol->label(),
            'hogar_solidario_id' => $user->hogar_solidario_id,
            'refugio_id' => $user->hogar_solidario_id,
            'hogar_vinculado_en' => $user->hogar_vinculado_en?->toIso8601String(),
            'centro_acopio_id' => $user->centro_acopio_id,
            'hogar_solidario' => $this->whenLoaded('hogarSolidario', fn () => [
                'id' => $user->hogarSolidario?->id,
                'codigo' => $user->hogarSolidario?->codigo,
                'nombre' => $user->hogarSolidario?->codigo,
                'direccion_exacta' => $user->hogarSolidario?->direccion_exacta,
                'tiene_nucleo_familiar' => $user->hogarSolidario?->tieneNucleoFamiliar() ?? false,
            ]),
            'refugio' => $this->whenLoaded('hogarSolidario', fn () => [
                'id' => $user->hogarSolidario?->id,
                'codigo' => $user->hogarSolidario?->codigo,
                'nombre' => $user->hogarSolidario?->codigo,
                'direccion_exacta' => $user->hogarSolidario?->direccion_exacta,
                'tiene_nucleo_familiar' => $user->hogarSolidario?->tieneNucleoFamiliar() ?? false,
            ]),
            'requiere_registro_hogar' => $profile->requiereRegistroHogar($user),
            'tiene_nucleo_familiar' => $profile->tieneNucleoFamiliar($user),
            'centro_acopio' => $this->whenLoaded('centroAcopio', fn () => new MobileCentroAcopioResource($user->centroAcopio)),
        ];
    }
}
