<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Invitado;
use App\Models\Parroquia;
use App\Models\HogarSolidario;
use App\Models\User;
use App\Services\InvitadoRegistrationService;
use Database\Seeders\AnzoateguiGeografiaSeeder;
use Database\Seeders\VenezuelaEstadosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesAnfitrionWithHogar;
use Tests\Concerns\HasProcedenciaDemo;
use Tests\TestCase;

class AnfitrionAppTest extends TestCase
{
    use CreatesAnfitrionWithHogar;
    use HasProcedenciaDemo;
    use RefreshDatabase;

    private function createAnfitrion(?int $hogarId = null): User
    {
        $this->seed(VenezuelaEstadosSeeder::class);
        $this->seed(AnzoateguiGeografiaSeeder::class);

        if ($hogarId === null) {
            [$anfitrion] = $this->createAnfitrionWithHogar([
                'direccion_exacta' => 'Dirección test',
                'responsable_nombre' => 'Responsable Test',
            ]);

            return $anfitrion;
        }

        $user = User::factory()->create([
            'rol' => UserRole::Anfitrion,
        ]);
        $hogar = HogarSolidario::query()->findOrFail($hogarId);
        $hogar->forceFill(['anfitrion_user_id' => $user->id])->save();
        $user->forceFill(['hogar_solidario_id' => $hogarId])->save();

        return $user->fresh();
    }

    public function test_anfitrion_puede_ver_dashboard(): void
    {
        $anfitrion = $this->createAnfitrion();

        $this->actingAs($anfitrion)
            ->get('/anfitrion')
            ->assertOk();
    }

    public function test_anfitrion_sin_hogar_puede_acceder_registrar(): void
    {
        $user = User::factory()->create(['rol' => UserRole::Anfitrion]);
        $user->forceFill(['hogar_solidario_id' => null])->save();

        $this->actingAs($user)
            ->get('/anfitrion/registrar')
            ->assertOk();
    }

    public function test_anfitrion_sin_hogar_es_redirigido_desde_dashboard(): void
    {
        $user = User::factory()->create(['rol' => UserRole::Anfitrion]);
        $user->forceFill(['hogar_solidario_id' => null])->save();

        $this->actingAs($user)
            ->get('/anfitrion')
            ->assertRedirect(route('anfitrion.registrar'));
    }

    public function test_anfitrion_puede_registrar_invitado_con_familiar(): void
    {
        $anfitrion = $this->createAnfitrion();
        $procedencia = $this->procedenciaDemo();

        Livewire::actingAs($anfitrion)
            ->test(\App\Livewire\Anfitrion\RegistrarInvitado::class)
            ->set('paso', 2)
            ->set('nombre', 'María')
            ->set('apellido', 'Pérez')
            ->set('fecha_nacimiento', '1990-05-10')
            ->set('procedencia_estado_id', $procedencia['procedencia_estado_id'])
            ->set('procedencia_municipio_id', $procedencia['procedencia_municipio_id'])
            ->set('procedencia_parroquia_id', $procedencia['procedencia_parroquia_id'])
            ->set('situacion_jefe', $procedencia['situacion_jefe'])
            ->set('condicion', $procedencia['condicion'])
            ->set('familiares', [[
                'nombre' => 'José',
                'apellido' => 'Pérez',
                'parentesco' => 'Hijo(a)',
                'condicion' => 'ninguna',
                'cedula' => null,
                'telefono' => null,
                'fecha_nacimiento' => '2015-03-01',
            ]])
            ->call('guardar')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('invitados', [
            'nombre' => 'María',
            'apellido' => 'Pérez',
            'hogar_solidario_id' => $anfitrion->hogar_solidario_id,
        ]);

        $jefe = Invitado::query()->where('nombre', 'María')->firstOrFail();

        $this->assertDatabaseHas('invitados', [
            'nombre' => 'José',
            'parentesco' => 'Hijo(a)',
            'jefe_familia_id' => $jefe->id,
        ]);
    }

    public function test_anfitrion_no_ve_invitados_de_otro_refugio(): void
    {
        $anfitrion = $this->createAnfitrion();

        $otraParroquia = Parroquia::query()->where('nombre', '!=', 'Puerto La Cruz')->firstOrFail();
        $otroRefugio = HogarSolidario::query()->create([
            'parroquia_id' => $otraParroquia->id,
            'latitud' => 10.0,
            'longitud' => -69.0,
            'direccion_exacta' => 'Otra dirección',
        ]);

        $invitadoAjeno = Invitado::query()->create([
            'nombre' => 'Ajeno',
            'apellido' => 'Test',
            'fecha_nacimiento' => '1980-01-01',
            'hogar_solidario_id' => $otroRefugio->id,
            'estatus' => 'activo',
        ]);

        $this->actingAs($anfitrion)
            ->get('/anfitrion/invitados/'.$invitadoAjeno->id)
            ->assertForbidden();
    }

    public function test_servicio_registro_asigna_refugio_del_anfitrion(): void
    {
        $anfitrion = $this->createAnfitrion();
        $service = app(InvitadoRegistrationService::class);

        $jefe = $service->register($anfitrion, [
            'nombre' => 'Ana',
            'apellido' => 'López',
            'cedula' => 'V123',
            'telefono' => '0414',
            'fecha_nacimiento' => '1988-02-02',
            ...$this->procedenciaDemo(),
        ], null, []);

        $this->assertSame($anfitrion->hogar_solidario_id, $jefe->hogar_solidario_id);
    }
}
