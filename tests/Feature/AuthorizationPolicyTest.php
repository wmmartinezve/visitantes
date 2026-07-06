<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RequerimientoEstatus;
use App\Enums\UserRole;
use App\Models\CentroAcopio;
use App\Models\Inventario;
use App\Models\Invitado;
use App\Models\Parroquia;
use App\Models\Refugio;
use App\Models\Requerimiento;
use App\Models\User;
use Database\Seeders\AnzoateguiGeografiaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AnzoateguiGeografiaSeeder::class);
    }

    public function test_anfitrion_no_puede_ver_invitado_de_otro_refugio(): void
    {
        $parroquia = Parroquia::query()->where('nombre', 'Puerto La Cruz')->firstOrFail();

        $refugioPropio = Refugio::query()->create([
            'nombre' => 'Refugio Propio',
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.026,
            'longitud' => -69.256,
            'direccion_exacta' => 'Dir',
        ]);

        $refugioAjeno = Refugio::query()->create([
            'nombre' => 'Refugio Ajeno',
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.030,
            'longitud' => -69.260,
            'direccion_exacta' => 'Dir 2',
        ]);

        $anfitrion = User::factory()->create([
            'rol' => UserRole::Anfitrion,
            'refugio_id' => $refugioPropio->id,
        ]);

        $invitadoAjeno = Invitado::query()->create([
            'nombre' => 'Ajeno',
            'apellido' => 'Test',
            'fecha_nacimiento' => '1980-01-01',
            'refugio_id' => $refugioAjeno->id,
            'estatus' => 'activo',
        ]);

        $this->actingAs($anfitrion);

        $this->assertFalse($anfitrion->can('view', $invitadoAjeno));
        $this->assertFalse($anfitrion->can('createForInvitado', $invitadoAjeno));
    }

    public function test_operador_no_puede_editar_inventario_de_otro_centro(): void
    {
        $parroquia = Parroquia::query()->where('nombre', 'Puerto La Cruz')->firstOrFail();

        $centroPropio = CentroAcopio::query()->create([
            'nombre' => 'Centro Propio',
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.034,
            'longitud' => -69.248,
            'direccion_exacta' => 'Dir',
            'activo' => true,
        ]);

        $centroAjeno = CentroAcopio::query()->create([
            'nombre' => 'Centro Ajeno',
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.040,
            'longitud' => -69.240,
            'direccion_exacta' => 'Dir 2',
            'activo' => true,
        ]);

        $operador = User::factory()->create([
            'rol' => UserRole::CentroAcopio,
            'centro_acopio_id' => $centroPropio->id,
        ]);

        $inventarioAjeno = Inventario::query()->create([
            'centro_acopio_id' => $centroAjeno->id,
            'item_nombre' => 'Agua',
            'cantidad' => 10,
            'unidad_medida' => 'caja',
        ]);

        $this->actingAs($operador);

        $this->assertFalse($operador->can('update', $inventarioAjeno));
        $this->assertFalse($operador->can('delete', $inventarioAjeno));
    }

    public function test_operador_no_puede_entregar_requerimiento_de_otro_centro(): void
    {
        $parroquia = Parroquia::query()->where('nombre', 'Puerto La Cruz')->firstOrFail();

        $refugio = Refugio::query()->create([
            'nombre' => 'Refugio',
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.026,
            'longitud' => -69.256,
            'direccion_exacta' => 'Dir',
        ]);

        $centroPropio = CentroAcopio::query()->create([
            'nombre' => 'Centro Propio',
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.034,
            'longitud' => -69.248,
            'direccion_exacta' => 'Dir',
            'activo' => true,
        ]);

        $centroAjeno = CentroAcopio::query()->create([
            'nombre' => 'Centro Ajeno',
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.040,
            'longitud' => -69.240,
            'direccion_exacta' => 'Dir 2',
            'activo' => true,
        ]);

        $operador = User::factory()->create([
            'rol' => UserRole::CentroAcopio,
            'centro_acopio_id' => $centroPropio->id,
        ]);

        $invitado = Invitado::query()->create([
            'nombre' => 'Ana',
            'apellido' => 'Test',
            'fecha_nacimiento' => '1990-01-01',
            'refugio_id' => $refugio->id,
            'estatus' => 'activo',
        ]);

        $requerimientoAjeno = Requerimiento::query()->create([
            'invitado_id' => $invitado->id,
            'anfitrion_id' => User::factory()->create(['rol' => UserRole::Anfitrion, 'refugio_id' => $refugio->id])->id,
            'item_solicitado' => 'Colchonetas',
            'cantidad' => 2,
            'estatus' => RequerimientoEstatus::Asignado,
            'centro_acopio_id' => $centroAjeno->id,
        ]);

        $this->actingAs($operador);

        $this->assertFalse($operador->can('entregar', $requerimientoAjeno));
    }

    public function test_admin_puede_asignar_requerimiento_pendiente(): void
    {
        $parroquia = Parroquia::query()->where('nombre', 'Puerto La Cruz')->firstOrFail();

        $refugio = Refugio::query()->create([
            'nombre' => 'Refugio',
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.026,
            'longitud' => -69.256,
            'direccion_exacta' => 'Dir',
        ]);

        $admin = User::factory()->create(['rol' => UserRole::Admin]);

        $invitado = Invitado::query()->create([
            'nombre' => 'Ana',
            'apellido' => 'Test',
            'fecha_nacimiento' => '1990-01-01',
            'refugio_id' => $refugio->id,
            'estatus' => 'activo',
        ]);

        $requerimiento = Requerimiento::query()->create([
            'invitado_id' => $invitado->id,
            'anfitrion_id' => User::factory()->create(['rol' => UserRole::Anfitrion, 'refugio_id' => $refugio->id])->id,
            'item_solicitado' => 'Agua',
            'cantidad' => 1,
            'estatus' => RequerimientoEstatus::Pendiente,
        ]);

        $this->actingAs($admin);

        $this->assertTrue($admin->can('assign', $requerimiento));
    }
}
