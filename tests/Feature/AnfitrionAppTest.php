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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AnfitrionAppTest extends TestCase
{
    use RefreshDatabase;

    private function createAnfitrion(): User
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);

        $parroquia = Parroquia::query()->where('nombre', 'Puerto La Cruz')->firstOrFail();

        $refugio = HogarSolidario::query()->create([
            'nombre' => 'HogarSolidario Test',
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.214,
            'longitud' => -64.633,
            'direccion_exacta' => 'Dirección test',
        ]);

        return User::factory()->create([
            'rol' => UserRole::Anfitrion,
            'hogar_solidario_id' => $refugio->id,
        ]);
    }

    public function test_anfitrion_puede_ver_dashboard(): void
    {
        $anfitrion = $this->createAnfitrion();

        $this->actingAs($anfitrion)
            ->get('/anfitrion')
            ->assertOk();
    }

    public function test_anfitrion_puede_registrar_invitado_con_familiar(): void
    {
        $anfitrion = $this->createAnfitrion();

        Livewire::actingAs($anfitrion)
            ->test(\App\Livewire\Anfitrion\RegistrarInvitado::class)
            ->set('nombre', 'María')
            ->set('apellido', 'Pérez')
            ->set('fecha_nacimiento', '1990-05-10')
            ->set('familiares', [[
                'nombre' => 'José',
                'apellido' => 'Pérez',
                'parentesco' => 'Hijo(a)',
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
            'nombre' => 'Otro refugio',
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
