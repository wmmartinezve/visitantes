<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\HogarSolidario;
use App\Models\Invitado;
use App\Models\Parroquia;
use App\Models\User;
use App\Services\AnfitrionMobileProfileService;
use Database\Seeders\AnzoateguiGeografiaSeeder;
use Database\Seeders\DemoOperacionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnfitrionMobileProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_me_limpia_hogar_preasignado_demo_y_exige_registro(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);
        $this->seed(DemoOperacionSeeder::class);

        $anfitrion = User::query()->where('email', 'anfitrion@visitantes.test')->firstOrFail();
        $hogarDemo = HogarSolidario::query()->firstOrFail();

        $anfitrion->forceFill(['hogar_solidario_id' => $hogarDemo->id])->save();

        $response = $this->actingAs($anfitrion)->getJson('/api/mobile/me');

        $response->assertOk()
            ->assertJsonPath('data.hogar_solidario_id', null)
            ->assertJsonPath('data.requiere_registro_hogar', true)
            ->assertJsonPath('data.tiene_nucleo_familiar', false);

        $this->assertNull($anfitrion->fresh()->hogar_solidario_id);
    }

    public function test_login_limpia_hogar_preasignado(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);
        $this->seed(DemoOperacionSeeder::class);

        $anfitrion = User::query()->where('email', 'anfitrion@visitantes.test')->firstOrFail();
        $hogarDemo = HogarSolidario::query()->firstOrFail();
        $anfitrion->forceFill(['hogar_solidario_id' => $hogarDemo->id])->save();

        $this->postJson('/api/mobile/login', [
            'email' => 'anfitrion@visitantes.test',
            'password' => 'password',
            'device_name' => 'test',
        ])->assertOk()
            ->assertJsonPath('user.hogar_solidario_id', null)
            ->assertJsonPath('user.requiere_registro_hogar', true);

        $this->assertNull($anfitrion->fresh()->hogar_solidario_id);
    }

    public function test_mantiene_hogar_creado_por_anfitrion_en_onboarding(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);

        $parroquia = Parroquia::query()->firstOrFail();
        $anfitrion = User::factory()->create([
            'rol' => UserRole::Anfitrion,
            'hogar_solidario_id' => null,
            'created_at' => now()->subDay(),
        ]);

        $hogar = HogarSolidario::query()->create([
            'codigo' => 'HS-TEST-001',
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.0,
            'longitud' => -64.0,
            'direccion_exacta' => 'Calle test',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $anfitrion->forceFill(['hogar_solidario_id' => $hogar->id, 'hogar_vinculado_en' => now()])->save();

        Invitado::query()->create([
            'nombre' => 'Jefe',
            'apellido' => 'Test',
            'fecha_nacimiento' => '1990-01-01',
            'hogar_solidario_id' => $hogar->id,
            'estatus' => 'activo',
        ]);

        $normalized = app(AnfitrionMobileProfileService::class)->normalize($anfitrion->fresh(['hogarSolidario']));

        $this->assertSame($hogar->id, $normalized->hogar_solidario_id);
        $this->assertFalse(app(AnfitrionMobileProfileService::class)->requiereRegistroHogar($normalized));
        $this->assertTrue(app(AnfitrionMobileProfileService::class)->tieneNucleoFamiliar($normalized));
    }
}
