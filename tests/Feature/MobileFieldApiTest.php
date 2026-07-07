<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RequerimientoEstatus;
use App\Enums\UserRole;
use App\Http\Controllers\Api\MobileEntregaController;
use App\Models\CentroAcopio;
use App\Models\Invitado;
use App\Models\Parroquia;
use App\Models\Refugio;
use App\Models\Requerimiento;
use App\Models\User;
use App\Support\InvitadoFotoStorage;
use Database\Seeders\AnzoateguiGeografiaSeeder;
use Database\Seeders\DemoOperacionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileFieldApiTest extends TestCase
{
    use RefreshDatabase;

    private function anfitrion(): User
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);
        $this->seed(DemoOperacionSeeder::class);

        return User::query()->where('email', 'anfitrion@visitantes.test')->firstOrFail();
    }

    public function test_lista_invitados_mobile(): void
    {
        Sanctum::actingAs($this->anfitrion());

        $this->getJson('/api/mobile/invitados')
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    public function test_busqueda_invitados_mobile_es_insensitive(): void
    {
        $anfitrion = $this->anfitrion();
        Sanctum::actingAs($anfitrion);

        Invitado::query()->create([
            'nombre' => 'Manuel',
            'apellido' => 'Rivas',
            'fecha_nacimiento' => '1978-01-01',
            'refugio_id' => $anfitrion->refugio_id,
            'estatus' => 'activo',
        ]);

        $this->getJson('/api/mobile/invitados?q=manuel')
            ->assertOk()
            ->assertJsonPath('data.0.nombre', 'Manuel')
            ->assertJsonPath('data.0.apellido', 'Rivas');
    }

    public function test_anfitrion_puede_agregar_foto_a_invitado_sin_foto(): void
    {
        Storage::fake(InvitadoFotoStorage::privateDisk());

        $anfitrion = $this->anfitrion();
        Sanctum::actingAs($anfitrion);

        $invitado = Invitado::query()->create([
            'nombre' => 'Sin',
            'apellido' => 'Foto',
            'fecha_nacimiento' => '1990-01-01',
            'refugio_id' => $anfitrion->refugio_id,
            'estatus' => 'activo',
        ]);

        $fotoBase64 = base64_encode(
            base64_decode(
                '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAr/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFAEBAAAAAAAAAAAAAAAAAAAAAP/EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAMAwEAAhEDEQA/AL+AAf/Z',
                true,
            ) ?: '',
        );

        $this->postJson("/api/mobile/invitados/{$invitado->id}/foto", [
            'foto_base64' => 'data:image/jpeg;base64,'.$fotoBase64,
            'foto_mime' => 'image/jpeg',
        ])
            ->assertOk()
            ->assertJsonPath('data.foto_url', fn ($url) => is_string($url) && $url !== '');

        $invitado->refresh();
        $this->assertNotNull($invitado->foto_ingreso);
    }

    public function test_anfitrion_puede_registrar_invitado_online(): void
    {
        Storage::fake(InvitadoFotoStorage::privateDisk());

        $anfitrion = $this->anfitrion();
        Sanctum::actingAs($anfitrion);

        $fotoBase64 = base64_encode(
            base64_decode(
                '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAr/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFAEBAAAAAAAAAAAAAAAAAAAAAP/EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAMAwEAAhEDEQA/AL+AAf/Z',
                true,
            ) ?: '',
        );

        $response = $this->postJson('/api/mobile/invitados', [
            'nombre' => 'María',
            'apellido' => 'Online',
            'cedula' => 'V-55555555',
            'telefono' => '0414-5555555',
            'fecha_nacimiento' => '1992-04-10',
            'familiares' => [
                [
                    'nombre' => 'Pedro',
                    'apellido' => 'Online',
                    'parentesco' => 'Hijo(a)',
                    'fecha_nacimiento' => '2018-08-20',
                ],
            ],
            'foto_base64' => 'data:image/jpeg;base64,'.$fotoBase64,
            'foto_mime' => 'image/jpeg',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.nombre_completo', 'María Online');

        $this->assertDatabaseHas('invitados', [
            'nombre' => 'María',
            'apellido' => 'Online',
            'refugio_id' => $anfitrion->refugio_id,
        ]);

        $this->assertDatabaseHas('invitados', [
            'nombre' => 'Pedro',
            'apellido' => 'Online',
            'parentesco' => 'Hijo(a)',
        ]);
    }

    public function test_lista_invitados_incluye_jefe_y_familiares(): void
    {
        $anfitrion = $this->anfitrion();
        Sanctum::actingAs($anfitrion);

        $jefe = Invitado::query()->create([
            'nombre' => 'Padre',
            'apellido' => 'Lista',
            'cedula' => 'V-11111111',
            'telefono' => '0414-1111111',
            'fecha_nacimiento' => '1985-06-15',
            'refugio_id' => $anfitrion->refugio_id,
            'estatus' => 'activo',
        ]);

        $hijo = Invitado::query()->create([
            'nombre' => 'Hijo',
            'apellido' => 'Lista',
            'parentesco' => 'Hijo(a)',
            'fecha_nacimiento' => '2015-03-01',
            'refugio_id' => $anfitrion->refugio_id,
            'jefe_familia_id' => $jefe->id,
            'estatus' => 'activo',
        ]);

        $response = $this->getJson('/api/mobile/invitados');

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($jefe->id, $ids);
        $this->assertContains($hijo->id, $ids);

        $hijoData = collect($response->json('data'))->firstWhere('id', $hijo->id);

        $this->assertFalse($hijoData['es_jefe_familia']);
        $this->assertSame('Hijo(a)', $hijoData['parentesco']);
        $this->assertSame($jefe->id, $hijoData['detail_invitado_id']);
        $this->assertNotNull($hijoData['edad']);
    }

    public function test_crear_requerimiento_mobile(): void
    {
        $anfitrion = $this->anfitrion();
        Sanctum::actingAs($anfitrion);

        $invitado = Invitado::query()->where('refugio_id', $anfitrion->refugio_id)->firstOrFail();

        $this->postJson('/api/mobile/requerimientos', [
            'invitado_id' => $invitado->id,
            'categoria' => 'Alimentos y bebidas',
            'subcategoria' => 'Agua embotellada',
            'cantidad' => 3,
        ])->assertCreated();

        $this->assertDatabaseHas('requerimientos', [
            'invitado_id' => $invitado->id,
            'categoria' => 'Alimentos y bebidas',
            'subcategoria' => 'Agua embotellada',
            'item_solicitado' => 'Alimentos y bebidas · Agua embotellada',
        ]);
    }

    public function test_entregas_mobile_con_distancia(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);
        $parroquia = Parroquia::query()->where('nombre', 'Puerto La Cruz')->firstOrFail();

        $centro = CentroAcopio::query()->create([
            'nombre' => 'Centro API',
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.245,
            'longitud' => -64.655,
            'direccion_exacta' => 'Pozuelos',
            'activo' => true,
        ]);

        $operador = User::factory()->create([
            'rol' => UserRole::CentroAcopio,
            'centro_acopio_id' => $centro->id,
        ]);

        $refugio = Refugio::query()->create([
            'nombre' => 'Refugio API',
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.214,
            'longitud' => -64.633,
            'direccion_exacta' => 'PLC',
        ]);

        $anfitrion = User::factory()->create(['rol' => UserRole::Anfitrion, 'refugio_id' => $refugio->id]);
        $invitado = Invitado::query()->create([
            'nombre' => 'Test',
            'apellido' => 'API',
            'fecha_nacimiento' => '1990-01-01',
            'refugio_id' => $refugio->id,
            'estatus' => 'activo',
        ]);

        Requerimiento::query()->create([
            'invitado_id' => $invitado->id,
            'anfitrion_id' => $anfitrion->id,
            'categoria' => 'Abrigo y descanso',
            'subcategoria' => 'Frazada / cobija',
            'item_solicitado' => 'Abrigo y descanso · Frazada / cobija',
            'cantidad' => 2,
            'estatus' => RequerimientoEstatus::Asignado,
            'centro_acopio_id' => $centro->id,
        ]);

        Sanctum::actingAs($operador);

        $this->getJson('/api/mobile/entregas')
            ->assertOk()
            ->assertJsonPath('data.0.subcategoria', 'Frazada / cobija')
            ->assertJsonStructure(['data' => [['distancia_km', 'ruta_url', 'refugio_url', 'refugio_latitud']]]);
    }

    public function test_geo_links_incluye_refugio_url_aunque_centro_no_tenga_gps(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);
        $parroquia = Parroquia::query()->where('nombre', 'Puerto La Cruz')->firstOrFail();

        $refugio = Refugio::query()->create([
            'nombre' => 'Refugio API',
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.214,
            'longitud' => -64.633,
            'direccion_exacta' => 'PLC',
        ]);

        $invitado = Invitado::query()->create([
            'nombre' => 'Test',
            'apellido' => 'API',
            'fecha_nacimiento' => '1990-01-01',
            'refugio_id' => $refugio->id,
            'estatus' => 'activo',
        ]);

        $requerimiento = Requerimiento::query()->make([
            'invitado_id' => $invitado->id,
        ]);
        $requerimiento->setRelation('invitado', $invitado->load('refugio'));

        $links = MobileEntregaController::geoLinksFor($requerimiento, null, null);

        $this->assertArrayHasKey('refugio_url', $links);
        $this->assertStringContainsString('google.com/maps', $links['refugio_url']);
        $this->assertArrayNotHasKey('ruta_url', $links);
    }
}
