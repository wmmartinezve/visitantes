<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production') && ! filter_var(env('RUN_DEMO_SEED', false), FILTER_VALIDATE_BOOL)) {
            $this->command?->warn('Seeders demo omitidos en producción. Use RUN_DEMO_SEED=true solo en entornos controlados.');

            return;
        }

        $this->call(DemoOperacionSeeder::class);

        User::query()->updateOrCreate(
            ['email' => 'admin@visitantes.test'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'rol' => UserRole::Admin,
                'refugio_id' => null,
                'centro_acopio_id' => null,
            ],
        );
    }
}
