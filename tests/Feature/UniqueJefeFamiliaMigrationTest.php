<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Invitado;
use App\Models\Parroquia;
use App\Models\HogarSolidario;
use App\Support\NucleoFamiliarPorHogar;
use Database\Seeders\AnzoateguiGeografiaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UniqueJefeFamiliaMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        DB::statement('DROP INDEX IF EXISTS invitados_un_jefe_por_hogar_solidario');
    }

    public function test_deduplicar_jefes_conserva_el_mas_antiguo(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);

        $parroquia = Parroquia::query()->firstOrFail();
        $hogar = HogarSolidario::query()->create([
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.21,
            'longitud' => -64.63,
            'direccion_exacta' => 'Test',
            'responsable_nombre' => 'Host',
        ]);

        $jefeUno = Invitado::withoutEvents(fn () => Invitado::query()->create([
            'nombre' => 'Jefe',
            'apellido' => 'Uno',
            'fecha_nacimiento' => '1980-01-01',
            'hogar_solidario_id' => $hogar->id,
            'jefe_familia_id' => null,
            'estatus' => 'activo',
        ]));

        Invitado::withoutEvents(fn () => Invitado::query()->create([
            'nombre' => 'Jefe',
            'apellido' => 'Dos',
            'fecha_nacimiento' => '1981-01-01',
            'hogar_solidario_id' => $hogar->id,
            'jefe_familia_id' => null,
            'estatus' => 'activo',
        ]));

        $this->assertSame(2, Invitado::query()
            ->where('hogar_solidario_id', $hogar->id)
            ->whereNull('jefe_familia_id')
            ->count());

        $reasignados = NucleoFamiliarPorHogar::deduplicarJefesPorHogar();

        $this->assertSame(1, $reasignados);
        $this->assertSame(1, Invitado::query()
            ->where('hogar_solidario_id', $hogar->id)
            ->whereNull('jefe_familia_id')
            ->count());

        $this->assertSame($jefeUno->id, Invitado::query()
            ->where('hogar_solidario_id', $hogar->id)
            ->whereNull('jefe_familia_id')
            ->value('id'));

        $this->assertDatabaseHas('invitados', [
            'apellido' => 'Dos',
            'jefe_familia_id' => $jefeUno->id,
            'parentesco' => 'Otro',
        ]);
    }
}
