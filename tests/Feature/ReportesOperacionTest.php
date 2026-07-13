<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\ReporteExportService;
use Database\Seeders\AnzoateguiGeografiaSeeder;
use Database\Seeders\DemoOperacionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportesOperacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_acceder_a_reportes_sin_logistica(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/admin/reportes-operacion')
            ->assertOk()
            ->assertSee('Exportar datos (CSV)')
            ->assertSee('Invitados y núcleos familiares');
    }

    public function test_export_invitados_genera_csv_con_nucleo_familiar(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);
        $this->seed(DemoOperacionSeeder::class);

        $response = app(ReporteExportService::class)->invitados();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('invitados-nucleo', (string) $response->headers->get('Content-Disposition'));

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('Rol en núcleo', $content);
        $this->assertStringContainsString('Jefe de familia', $content);
        $this->assertStringContainsString('Miembro del núcleo', $content);
        $this->assertStringContainsString('Código hogar', $content);
        $this->assertStringContainsString('Ayudas mencionadas', $content);
        $this->assertStringContainsString('Carlos', $content);
    }
}
