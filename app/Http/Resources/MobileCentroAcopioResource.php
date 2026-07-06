<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\CentroAcopio;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CentroAcopio */
class MobileCentroAcopioResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'direccion_exacta' => $this->direccion_exacta,
            'latitud' => $this->latitud !== null ? (float) $this->latitud : null,
            'longitud' => $this->longitud !== null ? (float) $this->longitud : null,
            'tiene_geolocalizacion' => $this->geolocalizacionFijadaDesdeApp(),
            'geolocalizacion_editable' => $this->geolocalizacionEditableDesdeApp(),
            'geolocalizacion_fijada_en' => $this->geolocalizacion_fijada_en?->toIso8601String(),
        ];
    }
}
