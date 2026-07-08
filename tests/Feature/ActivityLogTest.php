<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ActivityAction;
use App\Enums\ActivityChannel;
use App\Enums\InvitadoEstatus;
use App\Enums\RequerimientoEstatus;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\CentroAcopio;
use App\Models\Inventario;
use App\Models\Invitado;
use App\Models\Parroquia;
use App\Models\Refugio;
use App\Models\Requerimiento;
use App\Models\User;
use App\Services\InvitadoRegistrationService;
use App\Services\RequerimientoAsignacionService;
use App\Support\InsumoCatalog;
use Database\Seeders\AnzoateguiGeografiaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_registro_invitado_genera_entradas_en_bitacora(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);
        Storage::fake('local');

        [$anfitrion, $refugio] = $this->anfitrionConRefugio();

        $jefe = app(InvitadoRegistrationService::class)->register(
            $anfitrion,
            [
                'nombre' => 'Jose',
                'apellido' => 'Perez',
                'fecha_nacimiento' => '1990-01-01',
            ],
            UploadedFile::fake()->image('foto.jpg'),
            [[
                'nombre' => 'Ana',
                'apellido' => 'Perez',
                'parentesco' => 'Hijo(a)',
                'fecha_nacimiento' => '2015-01-01',
            ]],
        );

        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => Invitado::class,
            'subject_id' => $jefe->id,
            'action' => ActivityAction::Created->value,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => Invitado::class,
            'action' => ActivityAction::FotoAttached->value,
        ]);

        $this->assertTrue(
            ActivityLog::query()
                ->where('action', ActivityAction::Created)
                ->where('subject_type', Invitado::class)
                ->where('subject_id', '!=', $jefe->id)
                ->exists(),
        );
    }

    public function test_asignar_y_entregar_requerimiento_genera_bitacora(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);

        [$anfitrion, $refugio] = $this->anfitrionConRefugio();
        $centro = $this->centroDemo();

        $invitado = Invitado::query()->create([
            'nombre' => 'Invitado',
            'apellido' => 'Test',
            'fecha_nacimiento' => '1990-01-01',
            'refugio_id' => $refugio->id,
            'estatus' => InvitadoEstatus::Activo,
        ]);

        $catalog = InsumoCatalog::catalog();
        $categoria = (string) array_key_first($catalog);
        $subcategoria = $catalog[$categoria][0];

        $requerimiento = Requerimiento::query()->create([
            'invitado_id' => $invitado->id,
            'anfitrion_id' => $anfitrion->id,
            'categoria' => $categoria,
            'subcategoria' => $subcategoria,
            'item_solicitado' => InsumoCatalog::etiqueta($categoria, $subcategoria),
            'cantidad' => 2,
            'estatus' => RequerimientoEstatus::Pendiente,
        ]);

        Inventario::query()->create([
            'centro_acopio_id' => $centro->id,
            'categoria' => $categoria,
            'subcategoria' => $subcategoria,
            'item_nombre' => InsumoCatalog::etiqueta($categoria, $subcategoria),
            'cantidad' => 10,
            'unidad_medida' => 'unidad',
        ]);

        $service = app(RequerimientoAsignacionService::class);
        $service->asignar($requerimiento, $centro->id);
        $service->marcarEntregado($requerimiento->fresh());

        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => Requerimiento::class,
            'subject_id' => $requerimiento->id,
            'action' => ActivityAction::Asignado->value,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => Requerimiento::class,
            'subject_id' => $requerimiento->id,
            'action' => ActivityAction::Entregado->value,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'subject_type' => Inventario::class,
            'action' => ActivityAction::StockDecremented->value,
        ]);
    }

    public function test_sync_offline_registra_canal_offline_sync(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);

        [$anfitrion, $refugio] = $this->anfitrionConRefugio();

        $this->actingAs($anfitrion);

        $response = $this->postJson('/api/offline/sync', [
            'items' => [[
                'client_id' => 'offline-test-1',
                'type' => 'invitado.registro',
                'payload' => [
                    'nombre' => 'Offline',
                    'apellido' => 'Sync',
                    'fecha_nacimiento' => '1988-05-05',
                    'familiares' => [],
                ],
            ]],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'channel' => ActivityChannel::OfflineSync->value,
            'client_id' => 'offline-test-1',
            'action' => ActivityAction::Created->value,
        ]);
    }

    /** @return array{0: User, 1: Refugio} */
    private function anfitrionConRefugio(): array
    {
        $parroquia = Parroquia::query()->where('nombre', 'Puerto La Cruz')->firstOrFail();
        $refugio = Refugio::query()->create([
            'nombre' => 'Refugio Bitácora',
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.214,
            'longitud' => -64.633,
            'direccion_exacta' => 'PLC',
        ]);

        $anfitrion = User::factory()->create([
            'rol' => UserRole::Anfitrion,
            'refugio_id' => $refugio->id,
        ]);

        return [$anfitrion, $refugio];
    }

    private function centroDemo(): CentroAcopio
    {
        $parroquia = Parroquia::query()->where('nombre', 'Puerto La Cruz')->firstOrFail();

        return CentroAcopio::query()->create([
            'nombre' => 'Centro Bitácora',
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.220,
            'longitud' => -64.640,
            'direccion_exacta' => 'PLC',
            'activo' => true,
        ]);
    }
}
