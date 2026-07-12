<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\RegistrarNucleoFamiliar;
use App\Models\User;
use Database\Seeders\VenezuelaEstadosSeeder;
use Database\Seeders\VenezuelaGeografiaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrarNucleoFamiliarFilamentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(VenezuelaEstadosSeeder::class);
        $this->seed(VenezuelaGeografiaSeeder::class);
    }

    public function test_procedencia_jefe_acepta_estado_municipio_y_parroquia_en_anzoategui(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::Admin]);

        $estado = \App\Models\Estado::query()->where('nombre', 'Anzoátegui')->firstOrFail();
        $municipio = \App\Models\Municipio::query()->where('estado_id', $estado->id)->where('nombre', 'Anaco')->firstOrFail();
        $parroquia = \App\Models\Parroquia::query()->where('municipio_id', $municipio->id)->firstOrFail();

        Livewire::actingAs($admin)
            ->test(RegistrarNucleoFamiliar::class)
            ->set('data.jefe_procedencia_estado_id', $estado->id)
            ->set('data.jefe_procedencia_municipio_id', $municipio->id)
            ->set('data.jefe_procedencia_parroquia_id', $parroquia->id)
            ->assertSet('data.jefe_procedencia_estado_id', $estado->id)
            ->assertSet('data.jefe_procedencia_parroquia_id', $parroquia->id);
    }

    public function test_procedencia_jefe_carga_municipios_de_la_guaira(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::Admin]);

        $estado = \App\Models\Estado::query()->where('nombre', 'La Guaira')->firstOrFail();

        $municipios = \App\Models\Municipio::query()->where('estado_id', $estado->id)->orderBy('nombre')->pluck('nombre')->all();

        $this->assertNotEmpty($municipios, 'La Guaira debe tener municipios sembrados.');

        Livewire::actingAs($admin)
            ->test(RegistrarNucleoFamiliar::class)
            ->set('data.jefe_procedencia_estado_id', $estado->id)
            ->assertSet('data.jefe_procedencia_estado_id', $estado->id);
    }
}
