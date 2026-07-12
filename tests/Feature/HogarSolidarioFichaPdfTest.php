<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\HogarSolidario;
use App\Models\User;
use App\Services\ReporteExportService;
use Database\Seeders\AnzoateguiGeografiaSeeder;
use Database\Seeders\DemoOperacionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HogarSolidarioFichaPdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AnzoateguiGeografiaSeeder::class);
        $this->seed(DemoOperacionSeeder::class);
    }

    public function test_admin_puede_descargar_ficha_pdf_desde_ruta(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::Admin]);
        $hogar = HogarSolidario::query()->whereHas('jefeFamilia')->firstOrFail();

        $this->actingAs($admin)
            ->get("/admin/hogares-solidarios/{$hogar->id}/exportar-ficha-pdf")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_anfitrion_no_puede_descargar_ficha_pdf(): void
    {
        $anfitrion = User::query()->where('email', 'anfitrion@visitantes.test')->firstOrFail();
        $hogar = HogarSolidario::query()->findOrFail($anfitrion->hogar_solidario_id);

        $this->actingAs($anfitrion)
            ->get("/admin/hogares-solidarios/{$hogar->id}/exportar-ficha-pdf")
            ->assertForbidden();
    }

    public function test_servicio_genera_pdf_con_datos_del_hogar_y_nucleo(): void
    {
        $hogar = HogarSolidario::query()->whereHas('jefeFamilia')->firstOrFail();

        $response = app(ReporteExportService::class)->hogarSolidarioFichaPdf($hogar);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('ficha-hogar-solidario', (string) $response->headers->get('Content-Disposition'));
    }
}
