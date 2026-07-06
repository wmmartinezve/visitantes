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
}
