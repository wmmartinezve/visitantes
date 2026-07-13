<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Filament\Resources\InvitadoResource\Pages\EditInvitado;
use App\Models\Invitado;
use App\Models\Parroquia;
use App\Models\HogarSolidario;
use App\Models\User;
use Database\Seeders\AnzoateguiGeografiaSeeder;
use Database\Seeders\VenezuelaEstadosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\HasProcedenciaDemo;
use Tests\TestCase;

class InvitadoMencionesFilamentTest extends TestCase
{
    use HasProcedenciaDemo;
    use RefreshDatabase;

    public function test_admin_puede_guardar_menciones_opcionales_en_filament(): void
    {
        $this->seed(VenezuelaEstadosSeeder::class);
        $this->seed(AnzoateguiGeografiaSeeder::class);

        $admin = User::factory()->create(['rol' => UserRole::Admin]);

        $parroquia = Parroquia::query()->where('nombre', 'Puerto La Cruz')->firstOrFail();
        $hogar = HogarSolidario::query()->create([
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.214,
            'longitud' => -64.633,
            'direccion_exacta' => 'PLC',
        ]);

        $invitado = Invitado::query()->create([
            'nombre' => 'Luisa',
            'apellido' => 'Filament',
            'fecha_nacimiento' => '1988-03-12',
            'hogar_solidario_id' => $hogar->id,
            'estatus' => 'activo',
        ]);

        Livewire::actingAs($admin)
            ->test(EditInvitado::class, ['record' => $invitado->getKey()])
            ->fillForm([
                ...$this->procedenciaDemo(),
                'menciones_ayudas' => ['alimentos', 'bebidas'],
                'menciones_salud' => ['consultas'],
                'menciones_tramites' => ['cedula'],
                'menciones_nota' => 'Referencia admin',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $invitado->fresh();

        $this->assertSame(['alimentos', 'bebidas'], $fresh->menciones_ayudas);
        $this->assertSame(['consultas'], $fresh->menciones_salud);
        $this->assertSame(['cedula'], $fresh->menciones_tramites);
        $this->assertSame('Referencia admin', $fresh->menciones_nota);
    }
}
