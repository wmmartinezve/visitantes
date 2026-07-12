<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Municipio;
use App\Models\Parroquia;
use App\Support\GeografiaUpsert;
use App\Support\ParroquiaDeduplicator;
use Database\Seeders\VenezuelaEstadosSeeder;
use Database\Seeders\VenezuelaGeografiaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParroquiaDeduplicatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(VenezuelaEstadosSeeder::class);
    }

    public function test_fusiona_parroquias_duplicadas_por_variantes_de_nombre(): void
    {
        $municipio = Municipio::query()->create([
            'nombre' => 'Anaco',
            'estado_id' => \App\Models\Estado::query()->where('nombre', 'Anzoátegui')->value('id'),
        ]);

        Parroquia::query()->create(['municipio_id' => $municipio->id, 'nombre' => 'Buena Vista']);
        Parroquia::query()->create(['municipio_id' => $municipio->id, 'nombre' => 'Buena vista']);
        Parroquia::query()->create(['municipio_id' => $municipio->id, 'nombre' => 'San Joaquín']);
        Parroquia::query()->create(['municipio_id' => $municipio->id, 'nombre' => 'San Joaquin']);

        $merged = app(ParroquiaDeduplicator::class)->run();

        $this->assertSame(2, $merged);
        $this->assertSame(2, Parroquia::query()->where('municipio_id', $municipio->id)->count());
        $this->assertDatabaseHas('parroquias', ['municipio_id' => $municipio->id, 'nombre' => 'Buena Vista']);
        $this->assertDatabaseHas('parroquias', ['municipio_id' => $municipio->id, 'nombre' => 'San Joaquín']);
    }

    public function test_seeder_nacional_no_duplica_parroquias_de_anzoategui(): void
    {
        $this->seed(\Database\Seeders\AnzoateguiGeografiaSeeder::class);
        $this->seed(VenezuelaGeografiaSeeder::class);

        $municipio = Municipio::query()->where('nombre', 'Anaco')->firstOrFail();

        $this->assertSame(3, Parroquia::query()->where('municipio_id', $municipio->id)->count());
        $this->assertDatabaseMissing('parroquias', ['municipio_id' => $municipio->id, 'nombre' => 'Buena vista']);
        $this->assertDatabaseMissing('parroquias', ['municipio_id' => $municipio->id, 'nombre' => 'San Joaquin']);
    }

    public function test_upsert_parroquia_reutiliza_registro_normalizado(): void
    {
        $municipio = Municipio::query()->create([
            'nombre' => 'Anaco',
            'estado_id' => \App\Models\Estado::query()->where('nombre', 'Anzoátegui')->value('id'),
        ]);

        GeografiaUpsert::upsertParroquia($municipio, 'Buena Vista');
        GeografiaUpsert::upsertParroquia($municipio, 'Buena vista');

        $this->assertSame(1, Parroquia::query()->where('municipio_id', $municipio->id)->count());
    }
}
