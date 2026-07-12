<?php

use App\Support\NucleoFamiliarPorHogar;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('invitados', 'hogar_solidario_id')) {
            return;
        }

        NucleoFamiliarPorHogar::deduplicarJefesPorHogar();

        DB::statement(
            'CREATE UNIQUE INDEX IF NOT EXISTS invitados_un_jefe_por_hogar_solidario '
            .'ON invitados (hogar_solidario_id) '
            .'WHERE jefe_familia_id IS NULL AND deleted_at IS NULL'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS invitados_un_jefe_por_hogar_solidario');
    }
};
