<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\FieldOperatorResetPasswordNotification;
use Database\Seeders\AnzoateguiGeografiaSeeder;
use Database\Seeders\DemoOperacionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserProfileAndPasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_anfitrion_puede_actualizar_su_perfil_mobile(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);
        $this->seed(DemoOperacionSeeder::class);

        $anfitrion = User::query()->where('email', 'anfitrion@visitantes.test')->firstOrFail();
        Sanctum::actingAs($anfitrion);

        $this->putJson('/api/mobile/profile', [
            'name' => 'Anfitrión Actualizado',
            'email' => 'anfitrion.nuevo@visitantes.test',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Anfitrión Actualizado')
            ->assertJsonPath('data.email', 'anfitrion.nuevo@visitantes.test');

        $this->assertDatabaseHas('users', [
            'id' => $anfitrion->id,
            'email' => 'anfitrion.nuevo@visitantes.test',
        ]);
    }

    public function test_operador_puede_cambiar_su_contrasena_mobile(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);
        $this->seed(DemoOperacionSeeder::class);

        $operador = User::query()->where('email', 'acopio@visitantes.test')->firstOrFail();
        Sanctum::actingAs($operador);

        $this->putJson('/api/mobile/profile/password', [
            'current_password' => 'password',
            'password' => 'nueva-clave-segura',
            'password_confirmation' => 'nueva-clave-segura',
        ])->assertOk();

        $operador->refresh();
        $this->assertTrue(Hash::check('nueva-clave-segura', $operador->password));
    }

    public function test_forgot_password_envia_notificacion_a_operador_de_campo(): void
    {
        Notification::fake();

        $this->seed(AnzoateguiGeografiaSeeder::class);
        $this->seed(DemoOperacionSeeder::class);

        $this->postJson('/api/mobile/forgot-password', [
            'email' => 'anfitrion@visitantes.test',
        ])->assertOk();

        $anfitrion = User::query()->where('email', 'anfitrion@visitantes.test')->firstOrFail();

        Notification::assertSentTo($anfitrion, FieldOperatorResetPasswordNotification::class);
    }

    public function test_reset_password_restaura_acceso_mobile(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);
        $this->seed(DemoOperacionSeeder::class);

        $anfitrion = User::query()->where('email', 'anfitrion@visitantes.test')->firstOrFail();
        $token = Password::createToken($anfitrion);

        $this->postJson('/api/mobile/reset-password', [
            'token' => $token,
            'email' => $anfitrion->email,
            'password' => 'clave-restaurada',
            'password_confirmation' => 'clave-restaurada',
        ])->assertOk();

        $anfitrion->refresh();
        $this->assertTrue(Hash::check('clave-restaurada', $anfitrion->password));
    }

    public function test_anfitrion_puede_ver_pagina_de_perfil_web(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);
        $this->seed(DemoOperacionSeeder::class);

        $anfitrion = User::query()->where('email', 'anfitrion@visitantes.test')->firstOrFail();

        $this->actingAs($anfitrion)
            ->get(route('anfitrion.perfil'))
            ->assertOk()
            ->assertSee('Mi perfil');
    }

    public function test_forgot_password_web_muestra_formulario(): void
    {
        $this->get(route('anfitrion.password.request'))
            ->assertOk()
            ->assertSee('Recuperar contraseña');
    }

    public function test_admin_no_recibe_reset_desde_api_campo(): void
    {
        Notification::fake();

        User::factory()->create([
            'email' => 'admin@visitantes.test',
            'rol' => UserRole::Admin,
        ]);

        $this->postJson('/api/mobile/forgot-password', [
            'email' => 'admin@visitantes.test',
        ])->assertOk();

        Notification::assertNothingSent();
    }
}
