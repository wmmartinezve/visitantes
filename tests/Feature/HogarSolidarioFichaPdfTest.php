<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\HogarSolidario;
use App\Models\User;
use App\Services\ReporteExportService;
use App\Support\InvitadoMencionesCatalog;
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
        $hogar = HogarSolidario::query()->whereHas('jefeFamilia')->firstOrFail();

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

    public function test_ficha_pdf_incluye_menciones_del_jefe(): void
    {
        $hogar = HogarSolidario::query()->whereHas('jefeFamilia')->firstOrFail();
        $jefe = $hogar->jefeFamilia;
        $jefe->update(InvitadoMencionesCatalog::normalizePayload([
            'menciones_ayudas' => ['alimentos'],
            'menciones_salud' => ['consultas'],
            'menciones_tramites' => ['cedula'],
            'menciones_nota' => 'Referencia en ficha',
        ]));

        $html = view('reports.hogar-solidario-ficha-pdf', [
            'hogar' => $hogar->fresh(['jefeFamilia', 'anfitriones', 'parroquia.municipio.estado', 'comuna']),
            'jefe' => $hogar->fresh()->jefeFamilia,
            'miembros' => collect(),
            'fotoBase64' => null,
            'mencionesJefe' => InvitadoMencionesCatalog::resourcePayload($hogar->fresh()->jefeFamilia),
            'generadoEn' => now()->format('d/m/Y H:i'),
        ])->render();

        $this->assertStringContainsString('Menciones opcionales', $html);
        $this->assertStringContainsString('Alimentos', $html);
        $this->assertStringContainsString('Consultas médicas', $html);
        $this->assertStringContainsString('Referencia en ficha', $html);
    }
}
