<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\MapaOperacion;
use App\Models\Municipio;
use App\Models\Parroquia;
use App\Models\User;
use Database\Seeders\AnzoateguiGeografiaSeeder;
use Database\Seeders\DemoOperacionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MapaOperacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AnzoateguiGeografiaSeeder::class);
        $this->seed(DemoOperacionSeeder::class);
    }

    public function test_admin_puede_ver_mapa_operativo(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/admin/mapa-operacion')
            ->assertOk()
            ->assertSee('Refugios')
            ->assertSee('Filtros territoriales')
            ->assertSee('Municipio')
            ->assertSee('Parroquia')
            ->assertSee('id="mapa-operacion"', false)
            ->assertSee('leaflet.js', false)
            ->assertSee('data-puntos', false);
    }

    public function test_filtro_por_municipio_reduce_puntos_en_mapa(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::Admin]);
        $this->actingAs($admin);

        $municipio = Municipio::query()->where('nombre', 'Simón Bolívar')->firstOrFail();

        Livewire::test(MapaOperacion::class)
            ->assertSet('data.municipio_id', null)
            ->set('data.municipio_id', $municipio->id)
            ->assertSet('data.municipio_id', $municipio->id)
            ->assertDispatched('refresh-mapa-operacion');

        $component = Livewire::test(MapaOperacion::class)
            ->set('data.municipio_id', $municipio->id);

        $puntos = $component->instance()->puntos;

        $this->assertNotEmpty($puntos['refugios']);
        $this->assertTrue(collect($puntos['refugios'])->every(
            fn (array $refugio): bool => $refugio['municipio'] === 'Simón Bolívar',
        ));
    }

    public function test_filtro_por_parroquia_reduce_puntos_en_mapa(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::Admin]);
        $this->actingAs($admin);

        $parroquia = Parroquia::query()->where('nombre', 'Puerto La Cruz')->firstOrFail();

        $component = Livewire::test(MapaOperacion::class)
            ->set('data.municipio_id', $parroquia->municipio_id)
            ->set('data.parroquia_id', $parroquia->id);

        $puntos = $component->instance()->puntos;

        $this->assertNotEmpty($puntos['refugios']);
        $this->assertTrue(collect($puntos['refugios'])->every(
            fn (array $refugio): bool => $refugio['parroquia'] === 'Puerto La Cruz',
        ));
    }
}
