<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Municipio;
use App\Models\Parroquia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnzoateguiGeografiaSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_anzoategui_geografia_seeder_carga_municipios_y_parroquias(): void
    {
        $this->seed(\Database\Seeders\AnzoateguiGeografiaSeeder::class);

        $this->assertSame(21, Municipio::query()->count());
        $this->assertGreaterThanOrEqual(53, Parroquia::query()->count());

        $this->assertDatabaseHas('municipios', ['nombre' => 'Juan Antonio Sotillo']);
        $this->assertDatabaseHas('parroquias', ['nombre' => 'Puerto La Cruz']);
        $this->assertDatabaseHas('parroquias', ['nombre' => 'El Carmen']);
    }
}
