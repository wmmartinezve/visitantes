<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Invitado;
use App\Support\InvitadoCedula;
use Database\Seeders\AnzoateguiGeografiaSeeder;
use Database\Seeders\VenezuelaEstadosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesAnfitrionWithHogar;
use Tests\Concerns\HasProcedenciaDemo;
use Tests\TestCase;

class InvitadoCedulaUniqueTest extends TestCase
{
    use CreatesAnfitrionWithHogar;
    use HasProcedenciaDemo;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(VenezuelaEstadosSeeder::class);
        $this->seed(AnzoateguiGeografiaSeeder::class);
    }

    public function test_normaliza_cadena_vacia_a_null(): void
    {
        $this->assertNull(InvitadoCedula::normalize(''));
        $this->assertNull(InvitadoCedula::normalize('   '));
        $this->assertSame('V-12345678', InvitadoCedula::normalize(' V-12345678 '));
    }

    public function test_permite_multiples_invitados_sin_cedula(): void
    {
        [, $hogar] = $this->createAnfitrionWithHogar();

        $jefe = Invitado::query()->create([
            'nombre' => 'Niño',
            'apellido' => 'Uno',
            'cedula' => null,
            'fecha_nacimiento' => '2015-01-01',
            'hogar_solidario_id' => $hogar->id,
            'estatus' => 'activo',
        ]);

        Invitado::query()->create([
            'nombre' => 'Niño',
            'apellido' => 'Dos',
            'cedula' => '',
            'fecha_nacimiento' => '2016-01-01',
            'hogar_solidario_id' => $hogar->id,
            'jefe_familia_id' => $jefe->id,
            'parentesco' => 'Hijo(a)',
            'estatus' => 'activo',
        ]);

        $this->assertSame(2, Invitado::query()->whereNull('cedula')->count());
        $this->assertSame(0, Invitado::query()->where('cedula', '')->count());
    }

    public function test_api_rechaza_cedula_duplicada(): void
    {
        [$anfitrion, $hogar] = $this->createAnfitrionWithHogar();
        [, $otroHogar] = $this->createAnfitrionWithHogar([
            'direccion_exacta' => 'Otro hogar',
        ]);

        Invitado::query()->create([
            'nombre' => 'Existente',
            'apellido' => 'Test',
            'cedula' => 'V-77777777',
            'fecha_nacimiento' => '1990-01-01',
            'hogar_solidario_id' => $otroHogar->id,
            'estatus' => 'activo',
        ]);

        Sanctum::actingAs($anfitrion);

        $this->postJson('/api/mobile/invitados', [
            'nombre' => 'Nuevo',
            'apellido' => 'Test',
            'cedula' => 'V-77777777',
            'fecha_nacimiento' => '1991-01-01',
            ...$this->procedenciaDemo(),
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['cedula'])
            ->assertJsonPath('errors.cedula.0', 'Esta cédula ya está registrada.');
    }

    public function test_api_permite_cedula_vacia_como_null(): void
    {
        [$anfitrion] = $this->createAnfitrionWithHogar();
        Sanctum::actingAs($anfitrion);

        $this->postJson('/api/mobile/invitados', [
            'nombre' => 'Sin',
            'apellido' => 'Cédula',
            'cedula' => '',
            'fecha_nacimiento' => '2010-01-01',
            ...$this->procedenciaDemo(),
        ])->assertCreated();

        $this->assertDatabaseHas('invitados', [
            'nombre' => 'Sin',
            'apellido' => 'Cédula',
            'cedula' => null,
        ]);
    }

    public function test_rechaza_misma_cedula_entre_jefe_y_familiar(): void
    {
        $validator = Validator::make([
            'cedula' => 'V-111',
            'familiares' => [
                ['cedula' => 'V-111'],
            ],
        ], []);

        InvitadoCedula::validateDistinctInPayload($validator, [
            'cedula' => 'V-111',
            'familiares' => [
                ['cedula' => 'V-111'],
            ],
        ]);

        $this->assertTrue($validator->errors()->has('familiares.0.cedula'));
    }
}
