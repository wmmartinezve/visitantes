<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\RegistrarNucleoFamiliar;
use App\Models\Estado;
use App\Models\Municipio;
use App\Models\Parroquia;
use App\Models\User;
use Database\Seeders\VenezuelaEstadosSeeder;
use Database\Seeders\VenezuelaGeografiaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\HasProcedenciaDemo;
use Tests\TestCase;

class RegistrarNucleoFamiliarValidationTest extends TestCase
{
    use HasProcedenciaDemo;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(VenezuelaEstadosSeeder::class);
        $this->seed(VenezuelaGeografiaSeeder::class);
    }

    public function test_finalizar_con_parroquia_seleccionada_sin_deshidratar_municipio_no_falla(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::Admin]);

        $estado = Estado::query()->where('nombre', 'Anzoátegui')->firstOrFail();
        $municipio = Municipio::query()->where('estado_id', $estado->id)->where('nombre', 'Anaco')->firstOrFail();
        $parroquia = Parroquia::query()->where('municipio_id', $municipio->id)->firstOrFail();
        $proc = $this->procedenciaDemo('Anaco');

        Livewire::actingAs($admin)
            ->test(RegistrarNucleoFamiliar::class)
            ->set('data.municipio_id', $municipio->id)
            ->fillForm([
                'tipo_vivienda' => 'casa',
                'tipo_anfitrion' => 'familiar',
                'parentesco_anfitrion' => 'Padre',
                'parroquia_id' => $parroquia->id,
                'direccion_exacta' => 'Calle principal, Anaco',
                'latitud' => 10.12345678,
                'longitud' => -64.87654321,
                'responsable_nombre' => 'María Responsable',
                'jefe_nombre' => 'José',
                'jefe_apellido' => 'Rivero',
                'jefe_fecha_nacimiento' => '1985-05-15',
                'jefe_procedencia_estado_id' => $proc['procedencia_estado_id'],
                'jefe_procedencia_municipio_id' => $proc['procedencia_municipio_id'],
                'jefe_procedencia_parroquia_id' => $proc['procedencia_parroquia_id'],
                'jefe_situacion' => 'trabajando',
                'jefe_condicion' => 'ninguna',
            ])
            ->call('create')
            ->assertHasNoErrors()
            ->assertRedirect();
    }
}
