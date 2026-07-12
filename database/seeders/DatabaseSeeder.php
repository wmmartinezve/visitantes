<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(VenezuelaEstadosSeeder::class);
        $this->call(AnzoateguiGeografiaSeeder::class);

        if ($this->shouldSeedDemoData()) {
            $this->call(DemoDatabaseSeeder::class);
        }
    }

    private function shouldSeedDemoData(): bool
    {
        if (filter_var(env('RUN_DEMO_SEED', false), FILTER_VALIDATE_BOOL)) {
            return true;
        }

        return app()->environment('local', 'testing');
    }
}
