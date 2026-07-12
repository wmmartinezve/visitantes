<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Comuna;
use App\Models\HogarSolidario;
use App\Models\Invitado;
use App\Models\User;
use App\Support\InvitadoFotoStorage;
use Database\Seeders\AnzoateguiGeografiaSeeder;
use Database\Seeders\VenezuelaEstadosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreaInvitadosDePrueba;
use Tests\Concerns\HasProcedenciaDemo;
use Tests\TestCase;

class NucleoFamiliarOnboardingTest extends TestCase
{
    use CreaInvitadosDePrueba;
    use HasProcedenciaDemo;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(VenezuelaEstadosSeeder::class);
        $this->seed(AnzoateguiGeografiaSeeder::class);
    }

    public function test_anfitrion_sin_hogar_registra_hogar_y_nucleo_en_un_paso(): void
    {
        Storage::fake(InvitadoFotoStorage::privateDisk());

        $anfitrion = User::factory()->create([
            'rol' => UserRole::Anfitrion,
            'hogar_solidario_id' => null,
        ]);

        $comuna = Comuna::query()->first();
        if ($comuna === null) {
            $parroquia = \App\Models\Parroquia::query()->firstOrFail();
            $comuna = Comuna::query()->create([
                'parroquia_id' => $parroquia->id,
                'nombre' => 'Comuna demo test',
            ]);
        }
        $procedencia = $this->procedenciaDemo();

        Sanctum::actingAs($anfitrion);

        $response = $this->postJson('/api/mobile/invitados', [
            'hogar' => [
                'tipo_vivienda' => 'casa',
                'tipo_anfitrion' => 'familiar',
                'parentesco_anfitrion' => 'Padre/Madre',
                'parroquia_id' => $comuna->parroquia_id,
                'comuna_id' => $comuna->id,
                'responsable_nombre' => 'Ana Anfitriona',
                'responsable_cedula' => 'V-12345678',
                'responsable_telefono' => '04141234567',
                'direccion_exacta' => 'Calle 10, Lechería',
                'latitud' => 10.12345678,
                'longitud' => -64.87654321,
            ],
            'nombre' => 'Carlos',
            'apellido' => 'Pérez',
            'fecha_nacimiento' => '1985-05-15',
            'procedencia_estado_id' => $procedencia['procedencia_estado_id'],
            'procedencia_municipio_id' => $procedencia['procedencia_municipio_id'],
            'procedencia_parroquia_id' => $procedencia['procedencia_parroquia_id'],
            'situacion_jefe' => $procedencia['situacion_jefe'],
            'condicion' => $procedencia['condicion'],
            'familiares' => [
                [
                    'nombre' => 'Lucía',
                    'apellido' => 'Pérez',
                    'parentesco' => 'Esposa(o)',
                    'fecha_nacimiento' => '1988-03-20',
                    'condicion' => 'ninguna',
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('hogar_creado', true)
            ->assertJsonPath('data.nombre', 'Carlos');

        $this->assertNotNull($response->json('user.hogar_solidario_id'));

        $anfitrion->refresh();
        $this->assertNotNull($anfitrion->hogar_solidario_id);

        $hogar = HogarSolidario::query()->findOrFail($anfitrion->hogar_solidario_id);
        $this->assertNotNull($hogar->codigo);
        $this->assertMatchesRegularExpression('/^[A-Z]{2,3}-[A-Z]{2,3}-\d{4}$/', $hogar->codigo);
        $this->assertSame('familiar', $hogar->tipo_anfitrion->value);
        $this->assertSame('Padre/Madre', $hogar->parentesco_anfitrion);

        $this->assertSame(2, Invitado::query()->where('hogar_solidario_id', $hogar->id)->count());
    }

    public function test_hogar_amigo_no_requiere_parentesco(): void
    {
        $anfitrion = User::factory()->create([
            'rol' => UserRole::Anfitrion,
            'hogar_solidario_id' => null,
        ]);

        $comuna = Comuna::query()->first();
        if ($comuna === null) {
            $parroquia = \App\Models\Parroquia::query()->firstOrFail();
            $comuna = Comuna::query()->create([
                'parroquia_id' => $parroquia->id,
                'nombre' => 'Comuna demo test',
            ]);
        }
        $procedencia = $this->procedenciaDemo();

        Sanctum::actingAs($anfitrion);

        $this->postJson('/api/mobile/invitados', [
            'hogar' => [
                'tipo_vivienda' => 'casa',
                'tipo_anfitrion' => 'amigo',
                'parroquia_id' => $comuna->parroquia_id,
                'comuna_id' => $comuna->id,
                'responsable_nombre' => 'Pedro Amigo',
                'direccion_exacta' => 'Calle 1',
                'latitud' => 10.12,
                'longitud' => -64.87,
            ],
            'nombre' => 'Carlos',
            'apellido' => 'Pérez',
            'fecha_nacimiento' => '1985-05-15',
            'procedencia_estado_id' => $procedencia['procedencia_estado_id'],
            'procedencia_municipio_id' => $procedencia['procedencia_municipio_id'],
            'procedencia_parroquia_id' => $procedencia['procedencia_parroquia_id'],
            'situacion_jefe' => $procedencia['situacion_jefe'],
            'condicion' => $procedencia['condicion'],
        ])->assertCreated();

        $hogar = HogarSolidario::query()->findOrFail($anfitrion->fresh()->hogar_solidario_id);
        $this->assertSame('amigo', $hogar->tipo_anfitrion->value);
        $this->assertNull($hogar->parentesco_anfitrion);
    }

    public function test_hogar_sin_comuna_es_valido(): void
    {
        $anfitrion = User::factory()->create([
            'rol' => UserRole::Anfitrion,
            'hogar_solidario_id' => null,
        ]);

        $parroquia = \App\Models\Parroquia::query()->firstOrFail();
        $procedencia = $this->procedenciaDemo();

        Sanctum::actingAs($anfitrion);

        $this->postJson('/api/mobile/invitados', [
            'hogar' => [
                'tipo_vivienda' => 'casa',
                'tipo_anfitrion' => 'amigo',
                'parroquia_id' => $parroquia->id,
                'responsable_nombre' => 'Pedro Amigo',
                'direccion_exacta' => 'Calle 1',
                'latitud' => 10.12,
                'longitud' => -64.87,
            ],
            'nombre' => 'Carlos',
            'apellido' => 'Pérez',
            'fecha_nacimiento' => '1985-05-15',
            'procedencia_estado_id' => $procedencia['procedencia_estado_id'],
            'procedencia_municipio_id' => $procedencia['procedencia_municipio_id'],
            'procedencia_parroquia_id' => $procedencia['procedencia_parroquia_id'],
            'situacion_jefe' => $procedencia['situacion_jefe'],
            'condicion' => $procedencia['condicion'],
        ])->assertCreated();

        $hogar = HogarSolidario::query()->findOrFail($anfitrion->fresh()->hogar_solidario_id);
        $this->assertNull($hogar->comuna_id);
        $this->assertSame($parroquia->id, $hogar->parroquia_id);
    }

    public function test_anfitrion_con_hogar_no_puede_enviar_datos_de_hogar(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);

        $parroquia = \App\Models\Parroquia::query()->firstOrFail();
        $hogar = HogarSolidario::query()->create([
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.21,
            'longitud' => -64.63,
            'direccion_exacta' => 'Test',
            'responsable_nombre' => 'Host',
        ]);

        $anfitrion = User::factory()->create([
            'rol' => UserRole::Anfitrion,
            'hogar_solidario_id' => $hogar->id,
        ]);

        $this->limpiarNucleoDeHogar($hogar->id);

        $procedencia = $this->procedenciaDemo();

        Sanctum::actingAs($anfitrion);

        $this->postJson('/api/mobile/invitados', [
            'hogar' => ['tipo_anfitrion' => 'amigo'],
            'nombre' => 'Carlos',
            'apellido' => 'Pérez',
            'fecha_nacimiento' => '1985-05-15',
            'procedencia_estado_id' => $procedencia['procedencia_estado_id'],
            'procedencia_municipio_id' => $procedencia['procedencia_municipio_id'],
            'procedencia_parroquia_id' => $procedencia['procedencia_parroquia_id'],
            'situacion_jefe' => $procedencia['situacion_jefe'],
            'condicion' => $procedencia['condicion'],
        ])->assertUnprocessable();
    }
}
