<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AnzoateguiGeografiaSeeder::class);
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
