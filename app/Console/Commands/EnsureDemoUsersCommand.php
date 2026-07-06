<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class EnsureDemoUsersCommand extends Command
{
    protected $signature = 'visitantes:ensure-demo-users';

    protected $description = 'Garantiza que existan las cuentas demo (admin, anfitrión, acopio) con contraseña conocida';

    public function handle(): int
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@visitantes.test'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'rol' => UserRole::Admin,
                'refugio_id' => null,
                'centro_acopio_id' => null,
            ],
        );

        $this->info("Admin listo: {$admin->email} / password");

        $total = User::query()->count();
        if ($total <= 1) {
            $this->warn('Pocos usuarios en la base. Ejecute: php artisan db:seed --class=DemoOperacionSeeder');
        } else {
            $this->info("Total usuarios en la base: {$total}");
        }

        return self::SUCCESS;
    }
}
