<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentAdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_acceder_al_panel(): void
    {
        $admin = User::factory()->create([
            'rol' => UserRole::Admin,
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk();
    }

    public function test_anfitrion_no_puede_acceder_al_panel(): void
    {
        $anfitrion = User::factory()->create([
            'rol' => UserRole::Anfitrion,
        ]);

        $this->actingAs($anfitrion)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_login_no_es_indexado(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet');
    }

    public function test_admin_usa_locale_espanol(): void
    {
        $this->assertSame('es', app()->getLocale());

        $response = $this->get('/admin/login');

        $response->assertOk();
        $response->assertSee('Correo electrónico', false);
        $response->assertSee('Contraseña', false);
    }

    public function test_robots_txt_bloquea_admin(): void
    {
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Disallow: /admin');
    }
}
