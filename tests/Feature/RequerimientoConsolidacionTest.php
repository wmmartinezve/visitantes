<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\InvitadoEstatus;
use App\Enums\RequerimientoEstatus;
use App\Enums\UserRole;
use App\Models\CentroAcopio;
use App\Models\Inventario;
use App\Models\Invitado;
use App\Models\Parroquia;
use App\Models\HogarSolidario;
use App\Models\Requerimiento;
use App\Models\User;
use App\Services\RequerimientoAsignacionService;
use App\Services\RequerimientoConsolidacionService;
use App\Support\InsumoCatalog;
use Database\Seeders\AnzoateguiGeografiaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequerimientoConsolidacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_demanda_por_refugio_agrupa_cantidades(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);

        [$refugio, $anfitrion] = $this->refugioConAnfitrion();
        $catalog = InsumoCatalog::catalog();
        $categoria = (string) array_key_first($catalog);
        $subcategoria = $catalog[$categoria][0];
        $item = InsumoCatalog::etiqueta($categoria, $subcategoria);

        $jefe = Invitado::query()->create([
            'nombre' => 'A', 'apellido' => 'Uno', 'fecha_nacimiento' => '1990-01-01',
            'hogar_solidario_id' => $refugio->id, 'estatus' => InvitadoEstatus::Activo,
            'jefe_familia_id' => null,
        ]);
        $invitados = collect([
            $jefe,
            Invitado::query()->create([
                'nombre' => 'B', 'apellido' => 'Dos', 'fecha_nacimiento' => '1991-01-01',
                'hogar_solidario_id' => $refugio->id, 'estatus' => InvitadoEstatus::Activo,
                'jefe_familia_id' => $jefe->id,
                'parentesco' => 'Hijo(a)',
            ]),
        ]);

        foreach ([2, 3] as $index => $cantidad) {
            Requerimiento::query()->create([
                'invitado_id' => $invitados[$index]->id,
                'anfitrion_id' => $anfitrion->id,
                'categoria' => $categoria,
                'subcategoria' => $subcategoria,
                'item_solicitado' => $item,
                'cantidad' => $cantidad,
                'estatus' => RequerimientoEstatus::Pendiente,
            ]);
        }

        $demanda = app(RequerimientoConsolidacionService::class)->demandaPorRefugio();

        $this->assertCount(1, $demanda);
        $this->assertSame(5, $demanda->first()['cantidad_total']);
        $this->assertSame(2, $demanda->first()['requerimientos_count']);
        $this->assertSame($refugio->id, $demanda->first()['hogar_solidario_id']);
    }

    public function test_asignar_lote_valida_stock_total_y_asigna_todos(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);

        [$refugio, $anfitrion] = $this->refugioConAnfitrion();
        $centro = $this->centroDemo();
        $catalog = InsumoCatalog::catalog();
        $categoria = (string) array_key_first($catalog);
        $subcategoria = $catalog[$categoria][0];
        $item = InsumoCatalog::etiqueta($categoria, $subcategoria);

        $invitado = Invitado::query()->create([
            'nombre' => 'Jefe', 'apellido' => 'Familia', 'fecha_nacimiento' => '1985-01-01',
            'hogar_solidario_id' => $refugio->id, 'estatus' => InvitadoEstatus::Activo,
        ]);

        $reqs = collect([2, 3])->map(fn (int $cantidad) => Requerimiento::query()->create([
            'invitado_id' => $invitado->id,
            'anfitrion_id' => $anfitrion->id,
            'categoria' => $categoria,
            'subcategoria' => $subcategoria,
            'item_solicitado' => $item,
            'cantidad' => $cantidad,
            'estatus' => RequerimientoEstatus::Pendiente,
        ]));

        Inventario::query()->create([
            'centro_acopio_id' => $centro->id,
            'categoria' => $categoria,
            'subcategoria' => $subcategoria,
            'item_nombre' => $item,
            'cantidad' => 5,
            'unidad_medida' => 'unidad',
        ]);

        $asignados = app(RequerimientoAsignacionService::class)->asignarLote($reqs->all(), $centro->id);

        $this->assertSame(2, $asignados);
        $this->assertSame(2, Requerimiento::query()->where('estatus', RequerimientoEstatus::Asignado)->count());
        $this->assertSame($centro->id, $reqs->first()->fresh()->centro_acopio_id);
    }

    public function test_asignar_lote_falla_si_stock_insuficiente_para_total(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);

        [$refugio, $anfitrion] = $this->refugioConAnfitrion();
        $centro = $this->centroDemo();
        $catalog = InsumoCatalog::catalog();
        $categoria = (string) array_key_first($catalog);
        $subcategoria = $catalog[$categoria][0];
        $item = InsumoCatalog::etiqueta($categoria, $subcategoria);

        $invitado = Invitado::query()->create([
            'nombre' => 'Uno', 'apellido' => 'Solo', 'fecha_nacimiento' => '1985-01-01',
            'hogar_solidario_id' => $refugio->id, 'estatus' => InvitadoEstatus::Activo,
        ]);

        $reqs = collect([2, 3])->map(fn (int $cantidad) => Requerimiento::query()->create([
            'invitado_id' => $invitado->id,
            'anfitrion_id' => $anfitrion->id,
            'categoria' => $categoria,
            'subcategoria' => $subcategoria,
            'item_solicitado' => $item,
            'cantidad' => $cantidad,
            'estatus' => RequerimientoEstatus::Pendiente,
        ]));

        Inventario::query()->create([
            'centro_acopio_id' => $centro->id,
            'categoria' => $categoria,
            'subcategoria' => $subcategoria,
            'item_nombre' => $item,
            'cantidad' => 4,
            'unidad_medida' => 'unidad',
        ]);

        $this->expectException(\RuntimeException::class);

        app(RequerimientoAsignacionService::class)->asignarLote($reqs->all(), $centro->id);
    }

    /** @return array{0: HogarSolidario, 1: User} */
    private function refugioConAnfitrion(): array
    {
        $parroquia = Parroquia::query()->where('nombre', 'Puerto La Cruz')->firstOrFail();
        $refugio = HogarSolidario::query()->create([
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.214,
            'longitud' => -64.633,
            'direccion_exacta' => 'PLC',
        ]);

        $anfitrion = User::factory()->create([
            'rol' => UserRole::Anfitrion,
            'hogar_solidario_id' => $refugio->id,
        ]);

        return [$refugio, $anfitrion];
    }

    private function centroDemo(): CentroAcopio
    {
        $parroquia = Parroquia::query()->where('nombre', 'Puerto La Cruz')->firstOrFail();

        return CentroAcopio::query()->create([
            'nombre' => 'Centro Consolidado',
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.220,
            'longitud' => -64.640,
            'direccion_exacta' => 'PLC',
            'activo' => true,
        ]);
    }
}
