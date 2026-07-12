<?php

use Database\Seeders\VenezuelaEstadosSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('estados')) {
            return;
        }

        if (\App\Models\Estado::query()->exists()) {
            return;
        }

        (new VenezuelaEstadosSeeder)->run();
    }

    public function down(): void
    {
        // Datos de referencia; no se revierte.
    }
};
