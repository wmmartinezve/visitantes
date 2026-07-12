<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\Estado;
use App\Models\Parroquia;
use Database\Seeders\AnzoateguiGeografiaSeeder;
use Database\Seeders\VenezuelaEstadosSeeder;

trait HasProcedenciaDemo
{
    /** @return array<string, mixed> */
    protected function procedenciaDemo(string $parroquiaNombre = 'Puerto La Cruz'): array
    {
        $this->seed(VenezuelaEstadosSeeder::class);
        $this->seed(AnzoateguiGeografiaSeeder::class);

        $estado = Estado::query()->where('nombre', 'Anzoátegui')->firstOrFail();
        $parroquia = Parroquia::query()->where('nombre', $parroquiaNombre)->firstOrFail();

        return [
            'procedencia_estado_id' => $estado->id,
            'procedencia_municipio_id' => $parroquia->municipio_id,
            'procedencia_parroquia_id' => $parroquia->id,
            'situacion_jefe' => 'trabajando',
            'condicion' => 'ninguna',
        ];
    }
}
