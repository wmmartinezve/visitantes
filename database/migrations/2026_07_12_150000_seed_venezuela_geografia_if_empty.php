<?php

use Database\Seeders\VenezuelaGeografiaSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('municipios') || ! Schema::hasTable('estados')) {
            return;
        }

        $municipiosNacionales = \App\Models\Municipio::query()
            ->whereNotNull('estado_id')
            ->whereHas('estado', fn ($q) => $q->where('nombre', '!=', 'Anzoátegui'))
            ->count();

        if ($municipiosNacionales > 0) {
            return;
        }

        (new VenezuelaGeografiaSeeder)->run();
    }

    public function down(): void
    {
        // Datos de referencia; no se revierte.
    }
};
