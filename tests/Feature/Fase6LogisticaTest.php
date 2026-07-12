<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RequerimientoEstatus;
use App\Enums\UserRole;
use App\Models\CentroAcopio;
use App\Models\Invitado;
use App\Models\Parroquia;
use App\Models\HogarSolidario;
use App\Models\Requerimiento;
use App\Models\User;
use App\Support\GeoNavigation;
use Database\Seeders\AnzoateguiGeografiaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class Fase6LogisticaTest extends TestCase
{
    use RefreshDatabase;

    public function test_acopio_muestra_distancia_y_ruta_en_entregas(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);
        $parroquia = Parroquia::query()->where('nombre', 'Puerto La Cruz')->firstOrFail();

        $centro = CentroAcopio::query()->create([
            'nombre' => 'Centro Origen',
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.24500000,
            'longitud' => -64.65500000,
            'direccion_exacta' => 'Pozuelos',
            'activo' => true,
        ]);

        $refugio = HogarSolidario::query()->create([
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.21380000,
            'longitud' => -64.63280000,
            'direccion_exacta' => 'Av. Municipal PLC',
        ]);

        $operador = User::factory()->create([
            'rol' => UserRole::CentroAcopio,
            'centro_acopio_id' => $centro->id,
        ]);

        $anfitrion = User::factory()->create([
            'rol' => UserRole::Anfitrion,
            'hogar_solidario_id' => $refugio->id,
        ]);

        $invitado = Invitado::query()->create([
            'nombre' => 'María',
            'apellido' => 'Test',
            'fecha_nacimiento' => '1990-01-01',
            'hogar_solidario_id' => $refugio->id,
            'estatus' => 'activo',
        ]);

        Requerimiento::query()->create([
            'invitado_id' => $invitado->id,
            'anfitrion_id' => $anfitrion->id,
            'categoria' => 'Alimentos y bebidas',
            'subcategoria' => 'Agua embotellada',
            'item_solicitado' => 'Alimentos y bebidas · Agua embotellada',
            'cantidad' => 3,
            'estatus' => RequerimientoEstatus::Asignado,
            'centro_acopio_id' => $centro->id,
        ]);

        Livewire::actingAs($operador)
            ->test(\App\Livewire\Acopio\Requerimientos::class)
            ->assertSee('km')
            ->assertSee('Cómo llegar')
            ->assertSee('Av. Municipal PLC');
    }

    public function test_anfitrion_puede_ver_seguimiento_requerimientos(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);
        $parroquia = Parroquia::query()->where('nombre', 'Puerto La Cruz')->firstOrFail();

        $refugio = HogarSolidario::query()->create([
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.214,
            'longitud' => -64.633,
            'direccion_exacta' => 'PLC',
        ]);

        $anfitrion = User::factory()->create([
            'rol' => UserRole::Anfitrion,
            'hogar_solidario_id' => $refugio->id,
        ]);

        $invitado = Invitado::query()->create([
            'nombre' => 'Luis',
            'apellido' => 'Test',
            'fecha_nacimiento' => '1988-01-01',
            'hogar_solidario_id' => $refugio->id,
            'estatus' => 'activo',
        ]);

        Requerimiento::query()->create([
            'invitado_id' => $invitado->id,
            'anfitrion_id' => $anfitrion->id,
            'categoria' => 'Abrigo y descanso',
            'subcategoria' => 'Colchoneta',
            'item_solicitado' => 'Abrigo y descanso · Colchoneta',
            'cantidad' => 2,
            'estatus' => RequerimientoEstatus::Pendiente,
        ]);

        $this->actingAs($anfitrion)
            ->get('/anfitrion/requerimientos')
            ->assertOk()
            ->assertSee('Colchoneta')
            ->assertSee('Pendiente');
    }

    public function test_geo_navigation_genera_url_de_ruta(): void
    {
        $url = GeoNavigation::directionsUrl(10.245, -64.655, 10.214, -64.633);

        $this->assertStringContainsString('google.com/maps/dir/', $url);
        $this->assertStringContainsString('origin=10.245%2C-64.655', $url);
        $this->assertStringContainsString('destination=10.214%2C-64.633', $url);
    }
}
