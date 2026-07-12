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

        // Hogares creados antes que la cuenta del anfitrión = asignación inválida (p. ej. hogar demo #1).
        DB::statement('
            UPDATE users
            SET hogar_solidario_id = NULL
            WHERE rol = ?
              AND hogar_solidario_id IS NOT NULL
              AND EXISTS (
                SELECT 1 FROM hogares_solidarios h
                WHERE h.id = users.hogar_solidario_id
                  AND h.created_at < users.created_at
              )
        ', ['anfitrion']);
    }

    public function down(): void
    {
        // Sin reversión.
    }
};
