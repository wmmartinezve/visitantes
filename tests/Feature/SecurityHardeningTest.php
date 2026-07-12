<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RequerimientoEstatus;
use App\Enums\UserRole;
use App\Models\CentroAcopio;
use App\Models\Inventario;
use App\Models\Invitado;
use App\Models\Parroquia;
use App\Models\HogarSolidario;
use App\Models\Requerimiento;
use App\Models\User;
use App\Services\RequerimientoAsignacionService;
use App\Support\InvitadoFotoStorage;
use Database\Seeders\AnzoateguiGeografiaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Support\VisitantesFeatureTest;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    /** JPEG mínimo válido (1×1 px). */
    private function tinyJpegBytes(): string
    {
        return base64_decode(
            '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAr/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFAEBAAAAAAAAAAAAAAAAAAAAAP/EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAMAwEAAhEDEQA/AL+AAf/Z',
            true,
        ) ?: '';
    }

    public function test_login_mobile_bloquea_tras_demasiados_intentos(): void
    {
        RateLimiter::clear('login|attacker@example.com|127.0.0.1');

        $this->seed(AnzoateguiGeografiaSeeder::class);

        $parroquia = Parroquia::query()->where('nombre', 'Puerto La Cruz')->firstOrFail();
        $refugio = HogarSolidario::query()->create([
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.214,
            'longitud' => -64.633,
            'direccion_exacta' => 'PLC',
        ]);

        User::factory()->create([
            'email' => 'attacker@example.com',
            'rol' => UserRole::Anfitrion,
            'hogar_solidario_id' => $refugio->id,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/mobile/login', [
                'email' => 'attacker@example.com',
                'password' => 'wrong-password',
            ])->assertUnprocessable();
        }

        $this->postJson('/api/mobile/login', [
            'email' => 'attacker@example.com',
            'password' => 'wrong-password',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_foto_invitado_requiere_autenticacion(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);

        Storage::fake(InvitadoFotoStorage::privateDisk());

        $parroquia = Parroquia::query()->where('nombre', 'Puerto La Cruz')->firstOrFail();
        $refugio = HogarSolidario::query()->create([
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.214,
            'longitud' => -64.633,
            'direccion_exacta' => 'PLC',
        ]);

        $path = InvitadoFotoStorage::storePath(1, 'test.jpg');
        Storage::disk(InvitadoFotoStorage::privateDisk())->put($path, $this->tinyJpegBytes());

        $invitado = Invitado::query()->create([
            'nombre' => 'Foto',
            'apellido' => 'Test',
            'fecha_nacimiento' => '1990-01-01',
            'hogar_solidario_id' => $refugio->id,
            'estatus' => 'activo',
            'foto_ingreso' => $path,
        ]);

        $this->get(route('invitados.foto', $invitado))
            ->assertForbidden();

        $signedUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'invitados.foto',
            now()->addMinutes(5),
            $invitado,
        );

        $this->get($signedUrl)
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');
    }

    public function test_anfitrion_no_puede_ver_foto_de_otro_refugio(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);

        Storage::fake(InvitadoFotoStorage::privateDisk());

        $parroquia = Parroquia::query()->where('nombre', 'Puerto La Cruz')->firstOrFail();

        $refugioA = HogarSolidario::query()->create([
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.214,
            'longitud' => -64.633,
            'direccion_exacta' => 'A',
        ]);

        $refugioB = HogarSolidario::query()->create([
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.220,
            'longitud' => -64.640,
            'direccion_exacta' => 'B',
        ]);

        $path = InvitadoFotoStorage::storePath(99, 'privada.jpg');
        Storage::disk(InvitadoFotoStorage::privateDisk())->put($path, $this->tinyJpegBytes());

        $invitado = Invitado::query()->create([
            'nombre' => 'Ajeno',
            'apellido' => 'HogarSolidario',
            'fecha_nacimiento' => '1990-01-01',
            'hogar_solidario_id' => $refugioB->id,
            'estatus' => 'activo',
            'foto_ingreso' => $path,
        ]);

        $anfitrionA = User::factory()->create([
            'rol' => UserRole::Anfitrion,
            'hogar_solidario_id' => $refugioA->id,
        ]);

        Sanctum::actingAs($anfitrionA);

        $this->getJson(route('api.mobile.invitados.foto', $invitado))
            ->assertForbidden();
    }

    public function test_anfitrion_puede_ver_foto_de_su_refugio(): void
    {
        $this->seed(AnzoateguiGeografiaSeeder::class);

        Storage::fake(InvitadoFotoStorage::privateDisk());

        $parroquia = Parroquia::query()->where('nombre', 'Puerto La Cruz')->firstOrFail();
        $refugio = HogarSolidario::query()->create([
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.214,
            'longitud' => -64.633,
            'direccion_exacta' => 'PLC',
        ]);

        $path = InvitadoFotoStorage::storePath(2, 'propia.jpg');
        Storage::disk(InvitadoFotoStorage::privateDisk())->put($path, $this->tinyJpegBytes());

        $invitado = Invitado::query()->create([
            'nombre' => 'Propio',
            'apellido' => 'HogarSolidario',
            'fecha_nacimiento' => '1990-01-01',
            'hogar_solidario_id' => $refugio->id,
            'estatus' => 'activo',
            'foto_ingreso' => $path,
        ]);

        $anfitrion = User::factory()->create([
            'rol' => UserRole::Anfitrion,
            'hogar_solidario_id' => $refugio->id,
        ]);

        Sanctum::actingAs($anfitrion);

        $this->getJson(route('api.mobile.invitados.foto', $invitado))
            ->assertOk();
    }

    public function test_marcar_entregado_es_idempotente(): void
    {
        VisitantesFeatureTest::skipUnlessLogistica($this);
        $this->seed(AnzoateguiGeografiaSeeder::class);

        $parroquia = Parroquia::query()->where('nombre', 'Puerto La Cruz')->firstOrFail();

        $centro = CentroAcopio::query()->create([
            'nombre' => 'Centro Idempotente',
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.245,
            'longitud' => -64.655,
            'direccion_exacta' => 'PLC',
            'activo' => true,
        ]);

        $refugio = HogarSolidario::query()->create([
            'parroquia_id' => $parroquia->id,
            'latitud' => 10.214,
            'longitud' => -64.633,
            'direccion_exacta' => 'PLC',
        ]);

        $anfitrion = User::factory()->create([
            'rol' => UserRole::Anfitrion,
            'hogar_solidario_id' => $refugio->id,
        ]);

        $invitado = Invitado::query()->create([
            'nombre' => 'Entrega',
            'apellido' => 'Test',
            'fecha_nacimiento' => '1990-01-01',
            'hogar_solidario_id' => $refugio->id,
            'estatus' => 'activo',
        ]);

        Inventario::query()->create([
            'centro_acopio_id' => $centro->id,
            'categoria' => 'Abrigo y descanso',
            'subcategoria' => 'Colchoneta',
            'item_nombre' => 'Abrigo y descanso · Colchoneta',
            'cantidad' => 10,
            'unidad_medida' => 'unidad',
        ]);

        $requerimiento = Requerimiento::query()->create([
            'invitado_id' => $invitado->id,
            'anfitrion_id' => $anfitrion->id,
            'categoria' => 'Abrigo y descanso',
            'subcategoria' => 'Colchoneta',
            'item_solicitado' => 'Abrigo y descanso · Colchoneta',
            'cantidad' => 3,
            'estatus' => RequerimientoEstatus::Asignado,
            'centro_acopio_id' => $centro->id,
        ]);

        $service = app(RequerimientoAsignacionService::class);

        $service->marcarEntregado($requerimiento);

        $stockTrasPrimera = Inventario::query()->firstOrFail()->cantidad;

        $service->marcarEntregado($requerimiento->fresh());

        $this->assertSame(7, $stockTrasPrimera);
        $this->assertSame(7, Inventario::query()->firstOrFail()->cantidad);
        $this->assertSame(RequerimientoEstatus::Entregado, $requerimiento->fresh()->estatus);
    }
}
