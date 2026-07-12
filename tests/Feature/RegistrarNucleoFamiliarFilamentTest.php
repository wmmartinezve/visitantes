<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Pages\RegistrarNucleoFamiliar;
use App\Models\Invitado;
use App\Models\Parroquia;
use App\Models\User;
use Database\Seeders\VenezuelaEstadosSeeder;
use Database\Seeders\VenezuelaGeografiaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\HasProcedenciaDemo;
use Tests\TestCase;

class RegistrarNucleoFamiliarFilamentTest extends TestCase
{
    use HasProcedenciaDemo;
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

    public function test_admin_registra_nucleo_completo_desde_wizard(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::Admin]);
        $parroquia = Parroquia::query()
            ->whereHas('municipio', fn ($q) => $q->where('nombre', 'Anaco'))
            ->firstOrFail();
        $proc = $this->procedenciaDemo('Anaco');

        Livewire::actingAs($admin)
            ->test(RegistrarNucleoFamiliar::class)
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
                'familiares' => [
                    [
                        'parentesco' => 'Hijo(a)',
                        'nombre' => 'Pedro',
                        'apellido' => 'Rivero',
                        'fecha_nacimiento' => '2022-04-16',
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('invitados', [
            'nombre' => 'José',
            'apellido' => 'Rivero',
            'jefe_familia_id' => null,
        ]);

        $jefe = Invitado::query()->where('nombre', 'José')->firstOrFail();
        $this->assertDatabaseHas('invitados', [
            'nombre' => 'Pedro',
            'jefe_familia_id' => $jefe->id,
        ]);
    }
}
