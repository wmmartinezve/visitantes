<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Estado;
use App\Models\Municipio;
use App\Models\Parroquia;
use Database\Seeders\VenezuelaEstadosSeeder;
use Database\Seeders\VenezuelaGeografiaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenezuelaGeografiaSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_venezuela_geografia_seeder_carga_municipios_y_parroquias_nacionales(): void
    {
        $this->seed(VenezuelaEstadosSeeder::class);
        $this->seed(VenezuelaGeografiaSeeder::class);

        $this->assertGreaterThan(300, Municipio::query()->count());
        $this->assertGreaterThan(1000, Parroquia::query()->count());

        $anzoategui = Estado::query()->where('nombre', 'Anzoátegui')->firstOrFail();
        $laGuaira = Estado::query()->where('nombre', 'La Guaira')->firstOrFail();

        $this->assertDatabaseHas('municipios', [
            'nombre' => 'Juan Antonio Sotillo',
            'estado_id' => $anzoategui->id,
        ]);
        $this->assertDatabaseHas('parroquias', ['nombre' => 'Puerto La Cruz']);
        $this->assertDatabaseHas('parroquias', ['nombre' => 'El Carmen']);
        $this->assertGreaterThan(0, Municipio::query()->where('estado_id', $laGuaira->id)->count());
    }
}
