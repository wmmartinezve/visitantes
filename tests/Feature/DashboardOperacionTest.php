<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\OperacionMetricsService;
use App\Services\ReporteExportService;
use App\Support\OperacionFiltros;
use Database\Seeders\AnzoateguiGeografiaSeeder;
use Database\Seeders\DemoOperacionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardOperacionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(AnzoateguiGeografiaSeeder::class);
        $this->seed(DemoOperacionSeeder::class);
    }

    public function test_admin_puede_ver_dashboard_con_filtros(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk();
    }

    public function test_metricas_responden_a_filtros_de_fecha(): void
    {
        $filtros = OperacionFiltros::fromArray([
            'desde' => now()->subYear()->toDateString(),
            'hasta' => now()->toDateString(),
        ]);

        $kpis = app(OperacionMetricsService::class)->kpis($filtros);

        $this->assertGreaterThan(0, $kpis['invitados_activos']);
        $this->assertGreaterThan(0, $kpis['hogares_solidarios']);
        $this->assertArrayHasKey('anfitriones_desplegados', $kpis);
        $this->assertArrayHasKey('anfitriones_registrados', $kpis);
        $this->assertArrayHasKey('tasa_despliegue_anfitriones', $kpis);
        $this->assertArrayHasKey('tasa_cumplimiento', $kpis);
    }

    public function test_admin_puede_descargar_pdf_desde_ruta(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/admin/dashboard/exportar-pdf')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_export_pdf_dashboard_genera_archivo(): void
    {
        $filtros = OperacionFiltros::fromArray(null);
        $response = app(ReporteExportService::class)->dashboardPdf($filtros);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('reporte-operacion', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_reporte_completo_incluye_secciones_clave(): void
    {
        $reporte = app(OperacionMetricsService::class)->reporteCompleto(
            OperacionFiltros::fromArray(null),
        );

        $this->assertArrayHasKey('kpis', $reporte);
        $this->assertArrayHasKey('kpi_filas', $reporte);
        $this->assertArrayHasKey('logistica_habilitada', $reporte);
        $this->assertArrayHasKey('top_refugios', $reporte);
        $this->assertArrayHasKey('requerimientos_recientes', $reporte);
        $this->assertNotEmpty($reporte['etiquetas_filtros']);
        $this->assertNotEmpty($reporte['kpi_filas']);

        $labels = array_column($reporte['kpi_filas'], 0);
        $this->assertContains('Anfitriones desplegados', $labels);
        $this->assertContains('Hogares solidarios', $labels);
    }
}
