<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\AnzoateguiGeografiaSeeder;
use Database\Seeders\DemoOperacionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapaOperacionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_ver_mapa_operativo(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);
        $this->seed(DemoOperacionSeeder::class);

        $admin = User::factory()->create(['rol' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/admin/mapa-operacion')
            ->assertOk()
            ->assertSee('Refugios')
            ->assertSee('id="mapa-operacion"', false)
            ->assertSee('leaflet.js', false);
    }
}
