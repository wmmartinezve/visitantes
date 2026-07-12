<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\HogarSolidario;
use App\Models\Parroquia;
use App\Support\HogarSolidarioCodigoGenerator;
use Database\Seeders\AnzoateguiGeografiaSeeder;
use Database\Seeders\VenezuelaEstadosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HogarSolidarioCodigoGeneratorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(VenezuelaEstadosSeeder::class);
        $this->seed(AnzoateguiGeografiaSeeder::class);
    }

    public function test_genera_codigo_con_municipio_parroquia_y_correlativo(): void
    {
        $parroquia = Parroquia::query()->where('nombre', 'Puerto La Cruz')->firstOrFail();
        $generator = app(HogarSolidarioCodigoGenerator::class);

        $codigo1 = $generator->generar($parroquia->id);
        HogarSolidario::query()->create([
            'codigo' => $codigo1,
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.21,
            'longitud' => -64.63,
            'direccion_exacta' => 'Test 1',
            'responsable_nombre' => 'Host',
        ]);

        $codigo2 = $generator->generar($parroquia->id);

        $this->assertMatchesRegularExpression('/^[A-Z]{2,3}-[A-Z]{2,3}-\d{4}$/', $codigo1);
        $this->assertMatchesRegularExpression('/^[A-Z]{2,3}-[A-Z]{2,3}-\d{4}$/', $codigo2);
        $this->assertNotSame($codigo1, $codigo2);
    }

    public function test_modelo_asigna_codigo_al_crear_sin_codigo_explicito(): void
    {
        $parroquia = Parroquia::query()->firstOrFail();

        $hogar = HogarSolidario::query()->create([
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.21,
            'longitud' => -64.63,
            'direccion_exacta' => 'Auto',
            'responsable_nombre' => 'Host',
        ]);

        $this->assertNotNull($hogar->codigo);
        $this->assertSame($hogar->codigo, $hogar->nombre);
    }
}
