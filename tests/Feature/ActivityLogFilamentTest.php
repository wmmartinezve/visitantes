<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ActivityAction;
use App\Enums\ActivityChannel;
use App\Enums\UserRole;
use App\Filament\Resources\ActivityLogResource\Pages\ListActivityLogs;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ActivityLogFilamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_puede_abrir_detalle_bitacora(): void
    {
        $admin = User::factory()->create(['rol' => UserRole::Admin]);

        $log = ActivityLog::query()->create([
            'user_id' => $admin->id,
            'user_role' => 'admin',
            'subject_type' => User::class,
            'subject_id' => $admin->id,
            'action' => ActivityAction::ProfileUpdated,
            'channel' => ActivityChannel::Admin,
            'properties' => ['campo' => 'nombre'],
            'description' => 'Perfil actualizado',
        ]);

        Livewire::actingAs($admin)
            ->test(ListActivityLogs::class)
            ->mountTableAction('view', $log)
            ->assertHasNoErrors();
    }
}
