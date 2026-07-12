<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
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
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'rol' => $this->rol->value,
            'rol_label' => $this->rol->label(),
            'hogar_solidario_id' => $this->hogar_solidario_id,
            'refugio_id' => $this->hogar_solidario_id,
            'centro_acopio_id' => $this->centro_acopio_id,
            'hogar_solidario' => $this->whenLoaded('hogarSolidario', fn () => [
                'id' => $this->hogarSolidario?->id,
                'codigo' => $this->hogarSolidario?->codigo,
                'nombre' => $this->hogarSolidario?->codigo,
                'direccion_exacta' => $this->hogarSolidario?->direccion_exacta,
                'tiene_nucleo_familiar' => $this->hogarSolidario?->tieneNucleoFamiliar() ?? false,
            ]),
            'refugio' => $this->whenLoaded('hogarSolidario', fn () => [
                'id' => $this->hogarSolidario?->id,
                'codigo' => $this->hogarSolidario?->codigo,
                'nombre' => $this->hogarSolidario?->codigo,
                'direccion_exacta' => $this->hogarSolidario?->direccion_exacta,
                'tiene_nucleo_familiar' => $this->hogarSolidario?->tieneNucleoFamiliar() ?? false,
            ]),
            'centro_acopio' => $this->whenLoaded('centroAcopio', fn () => new MobileCentroAcopioResource($this->centroAcopio)),
        ];
    }
}
