<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class EnsureDemoUsersCommand extends Command
{
    protected $signature = 'visitantes:ensure-demo-users {--force : Permitir en producción (no recomendado)}';

    protected $description = 'Garantiza cuentas demo solo en entornos locales o con --force';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Comando bloqueado en producción. Use --force solo en entornos controlados.');

            return self::FAILURE;
        }

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@visitantes.test'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'rol' => UserRole::Admin,
                'hogar_solidario_id' => null,
                'centro_acopio_id' => null,
            ],
        );

        $this->info("Admin listo: {$admin->email} / password");

        $total = User::query()->count();
        if ($total <= 1) {
            $this->warn('Pocos usuarios en la base. Ejecute: php artisan db:seed --class=DemoDatabaseSeeder');
        } else {
            $this->info("Total usuarios en la base: {$total}");
        }

        return self::SUCCESS;
    }
}
