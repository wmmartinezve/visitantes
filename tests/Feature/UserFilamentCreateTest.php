<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserFilamentCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_abrir_formulario_crear_usuario(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::Admin]);

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->assertHasNoErrors();
    }
}
