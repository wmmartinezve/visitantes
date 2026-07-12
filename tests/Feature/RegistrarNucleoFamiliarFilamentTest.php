<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\RegistrarNucleoFamiliar;
use App\Models\User;
use Database\Seeders\AnzoateguiGeografiaSeeder;
use Database\Seeders\VenezuelaEstadosSeeder;
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
        $this->seed(AnzoateguiGeografiaSeeder::class);
    }

    public function test_procedencia_jefe_acepta_estado_municipio_y_parroquia(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::Admin]);

        $estado = \App\Models\Estado::query()->where('nombre', 'Anzoátegui')->firstOrFail();
        $municipio = \App\Models\Municipio::query()->where('nombre', 'Anaco')->firstOrFail();
        $parroquia = \App\Models\Parroquia::query()->where('municipio_id', $municipio->id)->firstOrFail();

        Livewire::actingAs($admin)
            ->test(RegistrarNucleoFamiliar::class)
            ->set('data.jefe_procedencia.estado_id', (string) $estado->id)
            ->set('data.jefe_procedencia.municipio_id', (string) $municipio->id)
            ->set('data.jefe_procedencia.parroquia_id', (string) $parroquia->id)
            ->assertSet('data.jefe_procedencia.estado_id', (string) $estado->id)
            ->assertSet('data.jefe_procedencia.parroquia_id', (string) $parroquia->id);
    }
}
