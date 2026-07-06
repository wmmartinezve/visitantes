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
            'refugio_id' => $this->refugio_id,
            'centro_acopio_id' => $this->centro_acopio_id,
            'refugio' => $this->whenLoaded('refugio', fn () => [
                'id' => $this->refugio?->id,
                'nombre' => $this->refugio?->nombre,
                'direccion_exacta' => $this->refugio?->direccion_exacta,
            ]),
            'centro_acopio' => $this->whenLoaded('centroAcopio', fn () => [
                'id' => $this->centroAcopio?->id,
                'nombre' => $this->centroAcopio?->nombre,
                'direccion_exacta' => $this->centroAcopio?->direccion_exacta,
            ]),
        ];
    }
}
