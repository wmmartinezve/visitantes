<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Invitado;
use App\Models\User;
use Database\Seeders\AnzoateguiGeografiaSeeder;
use Database\Seeders\DemoOperacionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\CreatesAnfitrionWithHogar;
use Tests\TestCase;

class InvitadoMencionesApiTest extends TestCase
{
    use CreatesAnfitrionWithHogar;
    use RefreshDatabase;

    public function test_catalogo_mobile_incluye_menciones_catalogo(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);
        $this->seed(DemoOperacionSeeder::class);

        $anfitrion = User::query()->where('email', 'anfitrion@visitantes.test')->firstOrFail();
        Sanctum::actingAs($anfitrion);

        $this->getJson(route('api.mobile.catalog'))
            ->assertOk()
            ->assertJsonStructure([
                'menciones_catalogo' => [
                    'ayudas',
                    'salud',
                    'tramites',
                ],
            ])
            ->assertJsonPath('menciones_catalogo.ayudas.0.value', 'alimentos');
    }

    public function test_anfitrion_puede_actualizar_menciones_de_su_invitado(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);

        [$anfitrion, $hogar] = $this->createAnfitrionWithHogar();

        $invitado = Invitado::query()->create([
            'nombre' => 'Pedro',
            'apellido' => 'Api',
            'fecha_nacimiento' => '1990-01-01',
            'hogar_solidario_id' => $hogar->id,
            'estatus' => 'activo',
        ]);

        Sanctum::actingAs($anfitrion);

        $this->putJson(route('api.mobile.invitados.menciones', $invitado), [
            'menciones_ayudas' => ['alimentos', 'bebidas'],
            'menciones_salud' => ['consultas'],
            'menciones_tramites' => ['cedula'],
            'menciones_nota' => 'Desde app móvil',
        ])
            ->assertOk()
            ->assertJsonPath('data.menciones_ayudas', ['alimentos', 'bebidas'])
            ->assertJsonPath('data.menciones.ayudas.0.label', 'Alimentos')
            ->assertJsonPath('data.menciones.nota', 'Desde app móvil');

        $this->assertDatabaseHas('invitados', [
            'id' => $invitado->id,
            'menciones_nota' => 'Desde app móvil',
        ]);
    }

    public function test_anfitrion_no_puede_actualizar_menciones_de_invitado_ajeno(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);

        [$anfitrionA] = $this->createAnfitrionWithHogar(['direccion_exacta' => 'A']);
        [, $hogarB] = $this->createAnfitrionWithHogar(['direccion_exacta' => 'B']);

        $invitadoAjeno = Invitado::query()->create([
            'nombre' => 'Ajeno',
            'apellido' => 'Api',
            'fecha_nacimiento' => '1990-01-01',
            'hogar_solidario_id' => $hogarB->id,
            'estatus' => 'activo',
        ]);

        Sanctum::actingAs($anfitrionA);

        $this->putJson(route('api.mobile.invitados.menciones', $invitadoAjeno), [
            'menciones_ayudas' => ['alimentos'],
        ])->assertForbidden();
    }

    public function test_show_invitado_incluye_menciones(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);

        [$anfitrion, $hogar] = $this->createAnfitrionWithHogar();

        $invitado = Invitado::query()->create([
            'nombre' => 'Show',
            'apellido' => 'Menciones',
            'fecha_nacimiento' => '1990-01-01',
            'hogar_solidario_id' => $hogar->id,
            'estatus' => 'activo',
            'menciones_ayudas' => ['otros'],
            'menciones_salud' => ['medicamentos'],
            'menciones_tramites' => null,
            'menciones_nota' => 'Nota test',
        ]);

        Sanctum::actingAs($anfitrion);

        $this->getJson(route('api.mobile.invitados.show', $invitado))
            ->assertOk()
            ->assertJsonPath('data.menciones_ayudas', ['otros'])
            ->assertJsonPath('data.menciones.salud.0.label', 'Medicamentos')
            ->assertJsonPath('data.menciones.nota', 'Nota test');
    }

    public function test_sync_offline_actualiza_menciones(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);

        [$anfitrion, $hogar] = $this->createAnfitrionWithHogar();

        $invitado = Invitado::query()->create([
            'nombre' => 'Sync',
            'apellido' => 'Menciones',
            'fecha_nacimiento' => '1990-01-01',
            'hogar_solidario_id' => $hogar->id,
            'estatus' => 'activo',
        ]);

        $this->actingAs($anfitrion)
            ->postJson(route('api.offline.sync'), [
                'items' => [[
                    'client_id' => 'menciones-offline-1',
                    'type' => 'invitado.menciones',
                    'payload' => [
                        'invitado_id' => $invitado->id,
                        'menciones_tramites' => ['partida_nacimiento'],
                        'menciones_nota' => 'Offline',
                    ],
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('results.0.status', 'ok');

        $fresh = $invitado->fresh();
        $this->assertSame(['partida_nacimiento'], $fresh->menciones_tramites);
        $this->assertSame('Offline', $fresh->menciones_nota);
    }
}
