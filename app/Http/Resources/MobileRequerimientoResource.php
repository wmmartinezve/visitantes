<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Requerimiento;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Requerimiento */
class MobileRequerimientoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invitado_id' => $this->invitado_id,
            'invitado_nombre' => $this->invitado?->nombreCompleto(),
            'categoria' => $this->categoria,
            'subcategoria' => $this->subcategoria,
            'item_solicitado' => $this->item_solicitado,
            'cantidad' => $this->cantidad,
            'estatus' => $this->estatus?->value,
            'estatus_label' => $this->estatus?->label(),
            'centro_acopio_id' => $this->centro_acopio_id,
            'centro_acopio_nombre' => $this->centroAcopio?->nombre,
            'refugio_nombre' => $this->invitado?->refugio?->nombre,
            'refugio_direccion' => $this->invitado?->refugio?->direccion_exacta,
            'refugio_latitud' => $this->invitado?->refugio ? (float) $this->invitado->refugio->latitud : null,
            'refugio_longitud' => $this->invitado?->refugio ? (float) $this->invitado->refugio->longitud : null,
            'distancia_km' => $this->when($this->distancia_km !== null, $this->distancia_km),
            'ruta_url' => $this->when($this->ruta_url !== null, $this->ruta_url),
            'refugio_url' => $this->when($this->refugio_url !== null, $this->refugio_url),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
