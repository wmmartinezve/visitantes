<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitados', function (Blueprint $table) {
            $table->foreignId('procedencia_estado_id')
                ->nullable()
                ->after('hogar_solidario_id')
                ->constrained('estados')
                ->nullOnDelete();
            $table->foreignId('procedencia_municipio_id')
                ->nullable()
                ->after('procedencia_estado_id')
                ->constrained('municipios')
                ->nullOnDelete();
            $table->foreignId('procedencia_parroquia_id')
                ->nullable()
                ->after('procedencia_municipio_id')
                ->constrained('parroquias')
                ->nullOnDelete();
            $table->string('situacion_jefe')->nullable()->after('procedencia_parroquia_id');
        });
    }

    public function down(): void
    {
        Schema::table('invitados', function (Blueprint $table) {
            $table->dropConstrainedForeignId('procedencia_estado_id');
            $table->dropConstrainedForeignId('procedencia_municipio_id');
            $table->dropConstrainedForeignId('procedencia_parroquia_id');
            $table->dropColumn('situacion_jefe');
        });
    }
};
