<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\InvitadoResource\Pages\CreateInvitado;
use App\Models\User;
use Database\Seeders\AnzoateguiGeografiaSeeder;
use Database\Seeders\DemoOperacionSeeder;
use Database\Seeders\VenezuelaEstadosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InvitadoFilamentCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_abrir_formulario_crear_invitado(): void
    {
        $this->seed(VenezuelaEstadosSeeder::class);
        $this->seed(AnzoateguiGeografiaSeeder::class);
        $this->seed(DemoOperacionSeeder::class);

        $admin = User::factory()->create(['rol' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(CreateInvitado::class)
            ->assertHasNoErrors();
    }
}
