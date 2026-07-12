<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\HogarSolidarioResource\Pages\EditHogarSolidario;
use App\Filament\Resources\HogarSolidarioResource\Pages\ListHogaresSolidarios;
use App\Models\HogarSolidario;
use App\Models\Parroquia;
use App\Models\User;
use Database\Seeders\VenezuelaEstadosSeeder;
use Database\Seeders\VenezuelaGeografiaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class HogarSolidarioGeografiaFilamentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(VenezuelaEstadosSeeder::class);
        $this->seed(VenezuelaGeografiaSeeder::class);
    }

    public function test_listado_muestra_municipio_y_parroquia_desde_parroquia_id(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::Admin]);

        $parroquia = Parroquia::query()
            ->whereHas('municipio', fn ($q) => $q->where('nombre', 'Anaco'))
            ->firstOrFail();

        $hogar = HogarSolidario::query()->create([
            'parroquia_id' => $parroquia->id,
            'tipo_vivienda' => 'casa',
            'tipo_anfitrion' => 'familiar',
            'responsable_nombre' => 'Responsable Test',
            'latitud' => 10.12345678,
            'longitud' => -64.87654321,
            'direccion_exacta' => 'Calle test',
        ]);

        Livewire::actingAs($admin)
            ->test(ListHogaresSolidarios::class)
            ->assertCanSeeTableRecords([$hogar])
            ->assertTableColumnStateSet('parroquia.municipio.nombre', 'Anaco', $hogar)
            ->assertTableColumnStateSet('parroquia.nombre', $parroquia->nombre, $hogar);
    }

    public function test_edicion_precarga_estado_municipio_y_parroquia(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::Admin]);

        $parroquia = Parroquia::query()
            ->whereHas('municipio', fn ($q) => $q->where('nombre', 'Anaco'))
            ->firstOrFail();

        $hogar = HogarSolidario::query()->create([
            'parroquia_id' => $parroquia->id,
            'tipo_vivienda' => 'casa',
            'tipo_anfitrion' => 'familiar',
            'responsable_nombre' => 'Responsable Test',
            'latitud' => 10.12345678,
            'longitud' => -64.87654321,
            'direccion_exacta' => 'Calle test',
        ]);

        Livewire::actingAs($admin)
            ->test(EditHogarSolidario::class, ['record' => $hogar->getRouteKey()])
            ->assertSet('data.parroquia_id', $parroquia->id)
            ->assertSet('data.municipio_id', $parroquia->municipio_id)
            ->assertSet('data.estado_id', $parroquia->municipio->estado_id);
    }
}
