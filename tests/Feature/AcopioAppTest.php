<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RequerimientoEstatus;
use App\Enums\UserRole;
use App\Models\CentroAcopio;
use App\Models\Inventario;
use App\Models\Invitado;
use App\Models\Parroquia;
use App\Models\HogarSolidario;
use App\Models\Requerimiento;
use App\Models\User;
use App\Services\RequerimientoAsignacionService;
use Database\Seeders\AnzoateguiGeografiaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AcopioAppTest extends TestCase
{
    use RefreshDatabase;

    private function createOperador(): User
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);

        $parroquia = Parroquia::query()->where('nombre', 'Puerto La Cruz')->firstOrFail();

        $centro = CentroAcopio::query()->create([
            'nombre' => 'Centro Test',
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.034,
            'longitud' => -69.248,
            'direccion_exacta' => 'Dirección centro',
            'activo' => true,
        ]);

        return User::factory()->create([
            'rol' => UserRole::CentroAcopio,
            'centro_acopio_id' => $centro->id,
        ]);
    }

    public function test_operador_puede_ver_dashboard(): void
    {
        $operador = $this->createOperador();

        $this->actingAs($operador)
            ->get('/acopio')
            ->assertOk();
    }

    public function test_operador_puede_agregar_inventario(): void
    {
        $operador = $this->createOperador();

        Livewire::actingAs($operador)
            ->test(\App\Livewire\Acopio\GestionInventario::class)
            ->set('categoria', 'Abrigo y descanso')
            ->set('subcategoria', 'Frazada / cobija')
            ->set('cantidad', 20)
            ->set('unidad_medida', 'unidad')
            ->call('guardar')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('inventarios', [
            'centro_acopio_id' => $operador->centro_acopio_id,
            'categoria' => 'Abrigo y descanso',
            'subcategoria' => 'Frazada / cobija',
            'item_nombre' => 'Abrigo y descanso · Frazada / cobija',
            'cantidad' => 20,
        ]);
    }

    public function test_operador_puede_marcar_entrega_y_descontar_stock(): void
    {
        $operador = $this->createOperador();
        $parroquia = Parroquia::query()->where('nombre', 'Puerto La Cruz')->firstOrFail();

        $refugio = HogarSolidario::query()->create([
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.214,
            'longitud' => -64.633,
            'direccion_exacta' => 'Dirección refugio',
        ]);

        $anfitrion = User::factory()->create([
            'rol' => UserRole::Anfitrion,
            'hogar_solidario_id' => $refugio->id,
        ]);

        $invitado = Invitado::query()->create([
            'nombre' => 'Ana',
            'apellido' => 'Test',
            'fecha_nacimiento' => '1990-01-01',
            'hogar_solidario_id' => $refugio->id,
            'estatus' => 'activo',
        ]);

        Inventario::query()->create([
            'centro_acopio_id' => $operador->centro_acopio_id,
            'categoria' => 'Abrigo y descanso',
            'subcategoria' => 'Colchoneta',
            'item_nombre' => 'Abrigo y descanso · Colchoneta',
            'cantidad' => 30,
            'unidad_medida' => 'unidad',
        ]);

        $requerimiento = Requerimiento::query()->create([
            'invitado_id' => $invitado->id,
            'anfitrion_id' => $anfitrion->id,
            'categoria' => 'Abrigo y descanso',
            'subcategoria' => 'Colchoneta',
            'item_solicitado' => 'Abrigo y descanso · Colchoneta',
            'cantidad' => 5,
            'estatus' => RequerimientoEstatus::Asignado,
            'centro_acopio_id' => $operador->centro_acopio_id,
        ]);

        Livewire::actingAs($operador)
            ->test(\App\Livewire\Acopio\Requerimientos::class)
            ->call('marcarEntregado', $requerimiento->id)
            ->assertSet('mensaje', 'Entrega registrada correctamente.');

        $this->assertDatabaseHas('requerimientos', [
            'id' => $requerimiento->id,
            'estatus' => RequerimientoEstatus::Entregado->value,
        ]);

        $this->assertDatabaseHas('inventarios', [
            'centro_acopio_id' => $operador->centro_acopio_id,
            'subcategoria' => 'Colchoneta',
            'cantidad' => 25,
        ]);
    }

    public function test_servicio_busca_centros_por_proximidad(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);
        $parroquia = Parroquia::query()->where('nombre', 'Puerto La Cruz')->firstOrFail();

        $refugio = HogarSolidario::query()->create([
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.214,
            'longitud' => -64.633,
            'direccion_exacta' => 'Cerca',
        ]);

        $centroCerca = CentroAcopio::query()->create([
            'nombre' => 'Centro Cerca',
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.215,
            'longitud' => -64.631,
            'direccion_exacta' => 'Cerca',
            'activo' => true,
        ]);

        $centroLejos = CentroAcopio::query()->create([
            'nombre' => 'Centro Lejos',
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.500,
            'longitud' => -64.900,
            'direccion_exacta' => 'Lejos',
            'activo' => true,
        ]);

        Inventario::query()->create([
            'centro_acopio_id' => $centroCerca->id,
            'categoria' => 'Alimentos y bebidas',
            'subcategoria' => 'Agua embotellada',
            'item_nombre' => 'Alimentos y bebidas · Agua embotellada',
            'cantidad' => 10,
            'unidad_medida' => 'caja',
        ]);

        Inventario::query()->create([
            'centro_acopio_id' => $centroLejos->id,
            'categoria' => 'Alimentos y bebidas',
            'subcategoria' => 'Agua embotellada',
            'item_nombre' => 'Alimentos y bebidas · Agua embotellada',
            'cantidad' => 100,
            'unidad_medida' => 'caja',
        ]);

        $invitado = Invitado::query()->create([
            'nombre' => 'Pedro',
            'apellido' => 'Test',
            'fecha_nacimiento' => '1985-01-01',
            'hogar_solidario_id' => $refugio->id,
            'estatus' => 'activo',
        ]);

        $requerimiento = Requerimiento::query()->create([
            'invitado_id' => $invitado->id,
            'anfitrion_id' => User::factory()->create(['rol' => UserRole::Anfitrion, 'hogar_solidario_id' => $refugio->id])->id,
            'categoria' => 'Alimentos y bebidas',
            'subcategoria' => 'Agua embotellada',
            'item_solicitado' => 'Alimentos y bebidas · Agua embotellada',
            'cantidad' => 2,
            'estatus' => RequerimientoEstatus::Pendiente,
        ]);

        $service = app(RequerimientoAsignacionService::class);
        $resultados = $service->buscarCentrosConStock($requerimiento);

        $this->assertGreaterThanOrEqual(1, $resultados->count());
        $this->assertSame($centroCerca->id, $resultados->first()['centro']->id);
    }
}
