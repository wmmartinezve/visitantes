<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'hogar_solidario_id')) {
            return;
        }

        DB::table('users')
            ->where('email', 'anfitrion@visitantes.test')
            ->update(['hogar_solidario_id' => null]);
    }

    public function down(): void
    {
        // Sin reversión: el anfitrión demo no debe quedar pre-asignado a un hogar.
    }
};
