<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Invitado;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Invitado */
class MobileInvitadoMemberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'nombre_completo' => $this->nombreCompleto(),
            'parentesco' => $this->parentesco,
            'cedula' => $this->cedula,
            'telefono' => $this->telefono,
            'fecha_nacimiento' => $this->fecha_nacimiento?->format('Y-m-d'),
        ];
    }
}
