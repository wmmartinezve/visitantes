<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Estado;
use App\Models\Municipio;
use App\Support\GeografiaUpsert;
use Illuminate\Database\Seeder;

class VenezuelaGeografiaSeeder extends Seeder
{
    /** @var array<string, string> */
    private const ENTIDAD_ALIASES = [
        'Vargas' => 'La Guaira',
        'Bolivariano de Miranda' => 'Miranda',
    ];

    public function run(): void
    {
        $path = database_path('seeders/data/venezuela_ubigeo.json');

        if (! is_file($path)) {
            $this->command?->warn('venezuela_ubigeo.json no encontrado; omitiendo geografía nacional.');

            return;
        }

        /** @var list<array{entidad: string, municipio: string, parroquia: string}> $rows */
        $rows = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        $estadosPorNombre = Estado::query()->pluck('id', 'nombre');

        foreach ($rows as $row) {
            $entidad = self::ENTIDAD_ALIASES[$row['entidad']] ?? $row['entidad'];

            if ($entidad === 'Dependencias Federales') {
                continue;
            }

            $estadoId = $estadosPorNombre[$entidad] ?? null;

            if ($estadoId === null) {
                continue;
            }

            $municipio = Municipio::query()
                ->where('nombre', $row['municipio'])
                ->where(function ($query) use ($estadoId): void {
                    $query->where('estado_id', $estadoId)->orWhereNull('estado_id');
                })
                ->first();

            if ($municipio === null) {
                $municipio = Municipio::query()->create([
                    'estado_id' => $estadoId,
                    'nombre' => $row['municipio'],
                ]);
            } elseif ($municipio->estado_id === null) {
                $municipio->update(['estado_id' => $estadoId]);
            }

            GeografiaUpsert::upsertParroquia($municipio, $row['parroquia']);
        }
    }
}
