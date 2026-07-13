<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\HogarSolidario;
use App\Models\Invitado;
use App\Models\Parroquia;
use App\Support\InvitadoMencionesCatalog;
use Database\Seeders\AnzoateguiGeografiaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitadoMencionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalogo_menciones_tiene_tres_categorias(): void
    {
        $catalog = InvitadoMencionesCatalog::catalog();

        $this->assertArrayHasKey('ayudas', $catalog);
        $this->assertArrayHasKey('salud', $catalog);
        $this->assertArrayHasKey('tramites', $catalog);
        $this->assertArrayHasKey('alimentos', $catalog['ayudas']);
        $this->assertArrayHasKey('cedula', $catalog['tramites']);
    }

    public function test_normalize_payload_filtra_claves_invalidas(): void
    {
        $normalized = InvitadoMencionesCatalog::normalizePayload([
            'menciones_ayudas' => ['alimentos', 'bebidas', 'invalido'],
            'menciones_salud' => ['consultas'],
            'menciones_tramites' => [],
            'menciones_nota' => '  Nota breve  ',
        ]);

        $this->assertSame(['alimentos', 'bebidas'], $normalized['menciones_ayudas']);
        $this->assertSame(['consultas'], $normalized['menciones_salud']);
        $this->assertNull($normalized['menciones_tramites']);
        $this->assertSame('Nota breve', $normalized['menciones_nota']);
    }

    public function test_invitado_persiste_menciones_opcionales(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);

        $parroquia = Parroquia::query()->where('nombre', 'Puerto La Cruz')->firstOrFail();
        $hogar = HogarSolidario::query()->create([
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.214,
            'longitud' => -64.633,
            'direccion_exacta' => 'PLC',
        ]);

        $invitado = Invitado::query()->create([
            'nombre' => 'Ana',
            'apellido' => 'Menciones',
            'fecha_nacimiento' => '1990-01-01',
            'hogar_solidario_id' => $hogar->id,
            'estatus' => 'activo',
            ...InvitadoMencionesCatalog::normalizePayload([
                'menciones_ayudas' => ['alimentos'],
                'menciones_salud' => ['medicamentos', 'examenes'],
                'menciones_tramites' => ['cedula'],
                'menciones_nota' => 'Solo referencia',
            ]),
        ]);

        $fresh = $invitado->fresh();

        $this->assertSame(['alimentos'], $fresh->menciones_ayudas);
        $this->assertSame(['medicamentos', 'examenes'], $fresh->menciones_salud);
        $this->assertSame(['cedula'], $fresh->menciones_tramites);
        $this->assertSame('Solo referencia', $fresh->menciones_nota);
    }

    public function test_resumen_texto_formatea_etiquetas(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);

        $parroquia = Parroquia::query()->where('nombre', 'Puerto La Cruz')->firstOrFail();
        $hogar = HogarSolidario::query()->create([
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.214,
            'longitud' => -64.633,
            'direccion_exacta' => 'PLC',
        ]);

        $invitado = Invitado::query()->create([
            'nombre' => 'Luis',
            'apellido' => 'Resumen',
            'fecha_nacimiento' => '1990-01-01',
            'hogar_solidario_id' => $hogar->id,
            'estatus' => 'activo',
            ...InvitadoMencionesCatalog::normalizePayload([
                'menciones_ayudas' => ['alimentos'],
                'menciones_salud' => ['consultas'],
                'menciones_tramites' => ['cedula'],
            ]),
        ]);

        $resumen = InvitadoMencionesCatalog::resumenTexto($invitado->fresh());

        $this->assertStringContainsString('Alimentos', $resumen);
        $this->assertStringContainsString('Consultas médicas', $resumen);
        $this->assertStringContainsString('Cédula', $resumen);
        $this->assertTrue(InvitadoMencionesCatalog::tieneMenciones($invitado));
    }

    public function test_scope_con_menciones_excluye_vacios(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);

        $parroquia = Parroquia::query()->where('nombre', 'Puerto La Cruz')->firstOrFail();
        $hogar = HogarSolidario::query()->create([
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.214,
            'longitud' => -64.633,
            'direccion_exacta' => 'PLC',
        ]);

        Invitado::query()->create([
            'nombre' => 'Sin',
            'apellido' => 'Menciones',
            'fecha_nacimiento' => '1990-01-01',
            'hogar_solidario_id' => $hogar->id,
            'estatus' => 'activo',
        ]);

        Invitado::query()->create([
            'nombre' => 'Con',
            'apellido' => 'Menciones',
            'fecha_nacimiento' => '1990-01-01',
            'hogar_solidario_id' => $hogar->id,
            'jefe_familia_id' => Invitado::query()->where('hogar_solidario_id', $hogar->id)->value('id'),
            'parentesco' => 'Hijo',
            'estatus' => 'activo',
            ...InvitadoMencionesCatalog::normalizePayload([
                'menciones_ayudas' => ['alimentos'],
            ]),
        ]);

        $total = InvitadoMencionesCatalog::scopeConAlgunaMencion(
            Invitado::query()->where('estatus', 'activo'),
        )->count();

        $this->assertSame(1, $total);
    }
}
