<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\AnzoateguiGeografiaSeeder;
use Database\Seeders\DemoOperacionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_anfitrion_puede_iniciar_sesion_mobile(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);
        $this->seed(DemoOperacionSeeder::class);

        $response = $this->postJson('/api/mobile/login', [
            'email' => 'anfitrion@visitantes.test',
            'password' => 'password',
            'device_name' => 'test-device',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user' => ['id', 'email', 'rol', 'hogar_solidario', 'refugio', 'requiere_registro_hogar']])
            ->assertJsonPath('user.rol', 'anfitrion')
            ->assertJsonPath('user.requiere_registro_hogar', true)
            ->assertJsonPath('user.hogar_solidario_id', null);
    }

    public function test_admin_no_puede_usar_app_mobile(): void
    {
        User::factory()->create([
            'email' => 'admin@visitantes.test',
            'rol' => UserRole::Admin,
        ]);

        $this->postJson('/api/mobile/login', [
            'email' => 'admin@visitantes.test',
            'password' => 'password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_usuario_autenticado_puede_obtener_catalogo_mobile(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);
        $this->seed(DemoOperacionSeeder::class);

        $anfitrion = User::query()->where('email', 'anfitrion@visitantes.test')->firstOrFail();
        Sanctum::actingAs($anfitrion);

        $this->getJson('/api/mobile/catalog')
            ->assertOk()
            ->assertJsonStructure(['municipios', 'parroquias', 'refugios', 'centros_acopio']);
    }
}
