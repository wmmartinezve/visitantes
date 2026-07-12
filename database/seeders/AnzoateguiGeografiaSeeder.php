<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Estado;
use App\Models\Municipio;
use App\Support\GeografiaUpsert;
use Illuminate\Database\Seeder;

class AnzoateguiGeografiaSeeder extends Seeder
{
    public function run(): void
    {
        $estadoAnzoategui = Estado::query()->where('nombre', 'Anzoátegui')->first();

        /** @var array<string, list<string>> $geografia */
        $geografia = require database_path('seeders/data/anzoategui_geografia.php');

        foreach ($geografia as $municipioNombre => $parroquias) {
            $municipio = Municipio::query()->firstOrCreate(
                ['nombre' => $municipioNombre],
                ['estado_id' => $estadoAnzoategui?->id],
            );

            if ($estadoAnzoategui !== null && $municipio->estado_id === null) {
                $municipio->update(['estado_id' => $estadoAnzoategui->id]);
            }

            foreach ($parroquias as $parroquiaNombre) {
                GeografiaUpsert::upsertParroquia($municipio, $parroquiaNombre);
            }
        }
    }
}
