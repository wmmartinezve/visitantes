<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Invitado;
use App\Models\Parroquia;
use App\Models\HogarSolidario;
use App\Models\User;
use App\Enums\UserRole;
use App\Services\InvitadoRegistrationService;
use Database\Seeders\AnzoateguiGeografiaSeeder;
use Database\Seeders\VenezuelaEstadosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class NucleoFamiliarPorHogarTest extends TestCase
{
    use RefreshDatabase;

    public function test_hogar_solidario_solo_admite_un_jefe_de_familia(): void
    {
        $this->seed(VenezuelaEstadosSeeder::class);
        $this->seed(AnzoateguiGeografiaSeeder::class);

        $parroquia = Parroquia::query()->where('nombre', 'Puerto La Cruz')->firstOrFail();
        $hogar = HogarSolidario::query()->create([
            'nombre' => 'Hogar único núcleo',
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.21,
            'longitud' => -64.63,
            'direccion_exacta' => 'PLC',
            'responsable_nombre' => 'Ana Host',
        ]);

        $anfitrion = User::factory()->create([
            'rol' => UserRole::Anfitrion,
            'hogar_solidario_id' => $hogar->id,
        ]);

        $service = app(InvitadoRegistrationService::class);

        $service->register($anfitrion, [
            'nombre' => 'Jefe',
            'apellido' => 'Uno',
            'fecha_nacimiento' => '1990-01-01',
            ...$this->procedenciaDemo(),
        ], null, []);

        $this->expectException(ValidationException::class);

        $service->register($anfitrion, [
            'nombre' => 'Jefe',
            'apellido' => 'Dos',
            'fecha_nacimiento' => '1991-01-01',
            ...$this->procedenciaDemo(),
        ], null, []);
    }

    public function test_modelo_hogar_expone_jefe_del_nucleo(): void
    {
        $this->seed(VenezuelaEstadosSeeder::class);
        $this->seed(AnzoateguiGeografiaSeeder::class);

        $parroquia = Parroquia::query()->firstOrFail();
        $hogar = HogarSolidario::query()->create([
            'nombre' => 'Hogar relación 1a1',
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.21,
            'longitud' => -64.63,
            'direccion_exacta' => 'Test',
            'responsable_nombre' => 'Host',
        ]);

        Invitado::query()->create([
            'nombre' => 'Maria',
            'apellido' => 'Jefe',
            'fecha_nacimiento' => '1985-05-05',
            'hogar_solidario_id' => $hogar->id,
            'estatus' => 'activo',
            'jefe_familia_id' => null,
        ]);

        $hogar->load('jefeFamilia');

        $this->assertTrue($hogar->tieneNucleoFamiliar());
        $this->assertSame('Maria', $hogar->jefeFamilia?->nombre);
    }
}
