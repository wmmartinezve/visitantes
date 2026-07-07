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
            'edad' => $this->fecha_nacimiento?->age,
            'registrado_el' => $this->created_at?->format('Y-m-d'),
            'es_jefe_familia' => $this->jefe_familia_id === null,
            'parentesco' => $this->parentesco,
            'jefe_familia_id' => $this->jefe_familia_id,
            'detail_invitado_id' => $this->jefe_familia_id ?? $this->id,
            'estatus' => $this->estatus?->value,
            'estatus_label' => $this->estatus?->label(),
            'foto_url' => $this->fotoUrl('api.mobile.invitados.foto'),
            'miembros_familia' => MobileInvitadoMemberResource::collection(
                $this->whenLoaded('miembrosFamilia'),
            ),
            'requerimientos' => MobileRequerimientoResource::collection(
                $this->whenLoaded('requerimientos'),
            ),
        ];
    }
}
