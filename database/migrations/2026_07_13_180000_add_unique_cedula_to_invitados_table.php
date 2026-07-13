<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('invitados') || ! Schema::hasColumn('invitados', 'cedula')) {
            return;
        }

        DB::table('invitados')->where('cedula', '')->update(['cedula' => null]);

        DB::statement('DROP INDEX IF EXISTS invitados_cedula_index');
        DB::statement('DROP INDEX IF EXISTS invitados_cedula_unique');

        DB::statement(
            'CREATE UNIQUE INDEX invitados_cedula_unique '
            .'ON invitados (cedula) '
            .'WHERE cedula IS NOT NULL AND deleted_at IS NULL',
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('invitados')) {
            return;
        }

        DB::statement('DROP INDEX IF EXISTS invitados_cedula_unique');

        Schema::table('invitados', function (Blueprint $table): void {
            $table->index('cedula');
        });
    }
};
