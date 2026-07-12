<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Estado;
use Illuminate\Database\Seeder;

class VenezuelaEstadosSeeder extends Seeder
{
    public function run(): void
    {
        /** @var list<array{nombre: string, codigo_ine: string}> $estados */
        $estados = require database_path('seeders/data/venezuela_estados.php');

        foreach ($estados as $estado) {
            Estado::query()->updateOrCreate(
                ['codigo_ine' => $estado['codigo_ine']],
                ['nombre' => $estado['nombre']],
            );
        }
    }
}
