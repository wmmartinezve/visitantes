<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Invitado;
use App\Support\InvitadoMencionesCatalog;
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
            'condicion' => $this->condicion?->value,
            'condicion_label' => $this->condicion?->label(),
            'jefe_familia_id' => $this->jefe_familia_id,
            'detail_invitado_id' => $this->jefe_familia_id ?? $this->id,
            'estatus' => $this->estatus?->value,
            'estatus_label' => $this->estatus?->label(),
            'hogar_solidario_id' => $this->hogar_solidario_id,
            'hogar_codigo' => $this->whenLoaded(
                'hogarSolidario',
                fn () => $this->hogarSolidario?->codigo,
            ),
            'foto_url' => $this->fotoUrl('api.mobile.invitados.foto'),
            'miembros_familia' => $this->when(
                $this->relationLoaded('miembrosFamilia'),
                fn () => MobileInvitadoMemberResource::collection($this->miembrosFamilia)->toArray($request),
            ),
            'requerimientos' => $this->when(
                $this->relationLoaded('requerimientos'),
                fn () => MobileRequerimientoResource::collection($this->requerimientos)->toArray($request),
            ),
            ...InvitadoMencionesCatalog::resourcePayload($this->resource),
        ];
    }
}
