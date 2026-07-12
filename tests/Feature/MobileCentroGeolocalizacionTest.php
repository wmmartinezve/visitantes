<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\AnzoateguiGeografiaSeeder;
use Database\Seeders\DemoOperacionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Support\VisitantesFeatureTest;
use Tests\TestCase;

class MobileCentroGeolocalizacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        VisitantesFeatureTest::skipUnlessLogistica($this);
    }

    private function operadorAcopio(): User
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);
        $this->seed(DemoOperacionSeeder::class);

        return User::query()->where('email', 'acopio@visitantes.test')->firstOrFail();
    }

    public function test_operador_puede_actualizar_geolocalizacion_de_su_centro(): void
    {
        $operador = $this->operadorAcopio();
        Sanctum::actingAs($operador);

        $this->putJson('/api/mobile/centro/geolocalizacion', [
            'latitud' => 10.24567891,
            'longitud' => -64.65543210,
            'direccion_exacta' => 'Av. Principal, Pozuelos',
        ])
            ->assertOk()
            ->assertJsonPath('data.tiene_geolocalizacion', true)
            ->assertJsonPath('data.latitud', 10.24567891);

        $this->assertDatabaseHas('centros_acopio', [
            'id' => $operador->centro_acopio_id,
            'latitud' => 10.24567891,
            'longitud' => -64.65543210,
            'direccion_exacta' => 'Av. Principal, Pozuelos',
        ]);

        $this->assertNotNull(
            \App\Models\CentroAcopio::query()->find($operador->centro_acopio_id)?->geolocalizacion_fijada_en,
        );
    }

    public function test_operador_no_puede_modificar_geolocalizacion_dos_veces(): void
    {
        $operador = $this->operadorAcopio();
        Sanctum::actingAs($operador);

        $payload = [
            'latitud' => 10.24567891,
            'longitud' => -64.65543210,
            'direccion_exacta' => 'Av. Principal, Pozuelos',
        ];

        $this->putJson('/api/mobile/centro/geolocalizacion', $payload)->assertOk();

        $this->putJson('/api/mobile/centro/geolocalizacion', [
            'latitud' => 10.25,
            'longitud' => -64.66,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['geolocalizacion']);
    }

    public function test_anfitrion_no_puede_actualizar_centro(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);
        $this->seed(DemoOperacionSeeder::class);

        Sanctum::actingAs(User::query()->where('email', 'anfitrion@visitantes.test')->firstOrFail());

        $this->putJson('/api/mobile/centro/geolocalizacion', [
            'latitud' => 10.24,
            'longitud' => -64.65,
        ])->assertForbidden();
    }

    public function test_validacion_rechaza_coordenadas_fuera_de_anzoategui(): void
    {
        Sanctum::actingAs($this->operadorAcopio());

        $this->putJson('/api/mobile/centro/geolocalizacion', [
            'latitud' => 4.5,
            'longitud' => -74.0,
        ])->assertUnprocessable();
    }

    public function test_me_incluye_geolocalizacion_del_centro(): void
    {
        Sanctum::actingAs($this->operadorAcopio());

        $this->getJson('/api/mobile/me')
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'centro_acopio' => [
                        'latitud',
                        'longitud',
                        'tiene_geolocalizacion',
                        'geolocalizacion_editable',
                    ],
                ],
            ]);
    }
}
