<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Invitado;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Invitado */
class MobileInvitadoResource extends JsonResource
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
            'cedula' => $this->cedula,
            'telefono' => $this->telefono,
            'fecha_nacimiento' => $this->fecha_nacimiento?->format('Y-m-d'),
            'estatus' => $this->estatus?->value,
            'estatus_label' => $this->estatus?->label(),
            'foto_url' => $this->foto_ingreso ? asset('storage/'.$this->foto_ingreso) : null,
            'miembros_familia' => MobileInvitadoMemberResource::collection(
                $this->whenLoaded('miembrosFamilia'),
            ),
            'requerimientos' => MobileRequerimientoResource::collection(
                $this->whenLoaded('requerimientos'),
            ),
        ];
    }
}
