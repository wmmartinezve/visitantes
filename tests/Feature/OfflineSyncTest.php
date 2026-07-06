<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\CentroAcopio;
use App\Models\Inventario;
use App\Models\Invitado;
use App\Models\Parroquia;
use App\Models\Refugio;
use App\Models\User;
use App\Support\InvitadoFotoStorage;
use Database\Seeders\AnzoateguiGeografiaSeeder;
use Database\Seeders\DemoOperacionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OfflineSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_anfitrion_puede_descargar_catalogo_offline(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);
        $this->seed(DemoOperacionSeeder::class);

        $anfitrion = User::query()->where('email', 'anfitrion@visitantes.test')->firstOrFail();

        $response = $this->actingAs($anfitrion)
            ->getJson(route('api.offline.catalog'));

        $response->assertOk()
            ->assertJsonStructure([
                'version',
                'municipios',
                'parroquias',
                'refugios',
                'centros_acopio',
                'unidades_medida',
                'items_insumo_sugeridos',
                'operador',
            ])
            ->assertJsonPath('operador.rol', 'anfitrion');
    }

    public function test_acopio_puede_descargar_catalogo_con_inventario_local(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);
        $this->seed(DemoOperacionSeeder::class);

        $operador = User::query()->where('email', 'acopio@visitantes.test')->firstOrFail();

        $response = $this->actingAs($operador)
            ->getJson(route('api.offline.catalog'));

        $response->assertOk()
            ->assertJsonPath('operador.rol', 'centro_acopio')
            ->assertJsonStructure(['inventario_local']);
    }

    public function test_admin_no_puede_acceder_a_catalogo_offline(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::Admin]);

        $this->actingAs($admin)
            ->getJson(route('api.offline.catalog'))
            ->assertForbidden();
    }

    public function test_sincroniza_registro_de_invitado_offline(): void
    {
        Storage::fake(InvitadoFotoStorage::privateDisk());
        $this->seed(AnzoateguiGeografiaSeeder::class);

        $parroquia = Parroquia::query()->where('nombre', 'Puerto La Cruz')->firstOrFail();
        $refugio = Refugio::query()->create([
            'nombre' => 'Refugio Offline',
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.214,
            'longitud' => -64.633,
            'direccion_exacta' => 'PLC',
        ]);

        $anfitrion = User::factory()->create([
            'rol' => UserRole::Anfitrion,
            'refugio_id' => $refugio->id,
        ]);

        $clientId = 'offline-client-1';
        $fotoBase64 = base64_encode(
            base64_decode(
                '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAr/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFAEBAAAAAAAAAAAAAAAAAAAAAP/EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAMAwEAAhEDEQA/AL+AAf/Z',
                true,
            ) ?: '',
        );

        $response = $this->actingAs($anfitrion)
            ->postJson(route('api.offline.sync'), [
                'items' => [
                    [
                        'client_id' => $clientId,
                        'type' => 'invitado.registro',
                        'payload' => [
                            'nombre' => 'Juan',
                            'apellido' => 'Offline',
                            'cedula' => 'V-99999999',
                            'telefono' => '0414-0000000',
                            'fecha_nacimiento' => '1990-05-10',
                            'familiares' => [
                                [
                                    'nombre' => 'Ana',
                                    'apellido' => 'Offline',
                                    'parentesco' => 'Hijo(a)',
                                    'fecha_nacimiento' => '2015-03-01',
                                ],
                            ],
                            'foto_base64' => 'data:image/jpeg;base64,'.$fotoBase64,
                            'foto_mime' => 'image/jpeg',
                        ],
                    ],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('results.0.status', 'ok')
            ->assertJsonPath('summary.ok', 1);

        $this->assertDatabaseHas('invitados', [
            'nombre' => 'Juan',
            'apellido' => 'Offline',
            'refugio_id' => $refugio->id,
        ]);

        $this->assertDatabaseHas('invitados', [
            'nombre' => 'Ana',
            'apellido' => 'Offline',
        ]);
    }

    public function test_sincronizar_invitado_offline_es_idempotente(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);

        $parroquia = Parroquia::query()->where('nombre', 'Puerto La Cruz')->firstOrFail();
        $refugio = Refugio::query()->create([
            'nombre' => 'Refugio Idempotente',
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.214,
            'longitud' => -64.633,
            'direccion_exacta' => 'PLC',
        ]);

        $anfitrion = User::factory()->create([
            'rol' => UserRole::Anfitrion,
            'refugio_id' => $refugio->id,
        ]);

        $clientId = 'offline-client-dup';
        $payload = [
            'items' => [
                [
                    'client_id' => $clientId,
                    'type' => 'invitado.registro',
                    'payload' => [
                        'nombre' => 'Carlos',
                        'apellido' => 'Unico',
                        'fecha_nacimiento' => '1990-01-01',
                        'familiares' => [],
                    ],
                ],
            ],
        ];

        $this->actingAs($anfitrion)
            ->postJson(route('api.offline.sync'), $payload)
            ->assertOk()
            ->assertJsonPath('summary.ok', 1);

        $this->actingAs($anfitrion)
            ->postJson(route('api.offline.sync'), $payload)
            ->assertOk()
            ->assertJsonPath('summary.ok', 1);

        $this->assertSame(
            1,
            Invitado::query()->where('nombre', 'Carlos')->where('apellido', 'Unico')->count(),
        );
    }

    public function test_sincroniza_requerimiento_referenciando_invitado_offline(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);

        $parroquia = Parroquia::query()->where('nombre', 'Puerto La Cruz')->firstOrFail();
        $refugio = Refugio::query()->create([
            'nombre' => 'Refugio Sync',
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.214,
            'longitud' => -64.633,
            'direccion_exacta' => 'PLC',
        ]);

        $anfitrion = User::factory()->create([
            'rol' => UserRole::Anfitrion,
            'refugio_id' => $refugio->id,
        ]);

        $invitadoClientId = 'inv-offline-1';

        $response = $this->actingAs($anfitrion)
            ->postJson(route('api.offline.sync'), [
                'items' => [
                    [
                        'client_id' => $invitadoClientId,
                        'type' => 'invitado.registro',
                        'payload' => [
                            'nombre' => 'Pedro',
                            'apellido' => 'Sync',
                            'fecha_nacimiento' => '1988-01-01',
                            'familiares' => [],
                        ],
                    ],
                    [
                        'client_id' => 'req-offline-1',
                        'type' => 'requerimiento.create',
                        'payload' => [
                            'invitado_client_id' => $invitadoClientId,
                            'item_solicitado' => 'Agua embotellada',
                            'cantidad' => 2,
                        ],
                    ],
                ],
            ]);

        $response->assertOk()
            ->assertJsonPath('summary.ok', 2);

        $invitado = Invitado::query()->where('nombre', 'Pedro')->firstOrFail();

        $this->assertDatabaseHas('requerimientos', [
            'invitado_id' => $invitado->id,
            'categoria' => 'Alimentos y bebidas',
            'subcategoria' => 'Agua embotellada',
            'item_solicitado' => 'Alimentos y bebidas · Agua embotellada',
            'cantidad' => 2,
        ]);
    }

    public function test_sincroniza_inventario_desde_acopio(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);

        $parroquia = Parroquia::query()->where('nombre', 'Puerto La Cruz')->firstOrFail();
        $centro = CentroAcopio::query()->create([
            'nombre' => 'Centro Offline',
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

        $this->actingAs($operador)
            ->postJson(route('api.offline.sync'), [
                'items' => [
                    [
                        'client_id' => 'inv-item-1',
                        'type' => 'inventario.create',
                        'payload' => [
                            'categoria' => 'Abrigo y descanso',
                            'subcategoria' => 'Frazada / cobija',
                            'cantidad' => 15,
                            'unidad_medida' => 'unidad',
                        ],
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('summary.ok', 1);

        $this->assertDatabaseHas('inventarios', [
            'centro_acopio_id' => $centro->id,
            'categoria' => 'Abrigo y descanso',
            'subcategoria' => 'Frazada / cobija',
            'cantidad' => 15,
        ]);
    }

    public function test_sincroniza_actualizacion_cantidad_inventario(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);

        $parroquia = Parroquia::query()->where('nombre', 'Puerto La Cruz')->firstOrFail();
        $centro = CentroAcopio::query()->create([
            'nombre' => 'Centro Offline Update',
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

        $inventario = Inventario::query()->create([
            'centro_acopio_id' => $centro->id,
            'categoria' => 'Abrigo y descanso',
            'subcategoria' => 'Colchoneta',
            'item_nombre' => 'Abrigo y descanso · Colchoneta',
            'cantidad' => 40,
            'unidad_medida' => 'unidad',
        ]);

        $this->actingAs($operador)
            ->postJson(route('api.offline.sync'), [
                'items' => [
                    [
                        'client_id' => 'inv-update-1',
                        'type' => 'inventario.update_cantidad',
                        'payload' => [
                            'inventario_id' => $inventario->id,
                            'cantidad' => 28,
                        ],
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('summary.ok', 1);

        $this->assertDatabaseHas('inventarios', [
            'id' => $inventario->id,
            'cantidad' => 28,
        ]);
    }
}
