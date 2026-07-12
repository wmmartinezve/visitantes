<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('refugios', 'hogares_solidarios');

        Schema::table('hogares_solidarios', function (Blueprint $table) {
            $table->foreignId('comuna_id')
                ->nullable()
                ->after('parroquia_id')
                ->constrained('comunas')
                ->nullOnDelete();
            $table->string('tipo_vivienda')->default('casa')->after('nombre');
            $table->string('responsable_nombre')->nullable()->after('tipo_vivienda');
            $table->string('responsable_cedula', 20)->nullable()->after('responsable_nombre');
            $table->string('responsable_telefono', 30)->nullable()->after('responsable_cedula');
            $table->json('habitantes')->nullable()->after('responsable_telefono');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('refugio_id', 'hogar_solidario_id');
        });

        Schema::table('invitados', function (Blueprint $table) {
            $table->renameColumn('refugio_id', 'hogar_solidario_id');
        });
    }

    public function down(): void
    {
        Schema::table('invitados', function (Blueprint $table) {
            $table->renameColumn('hogar_solidario_id', 'refugio_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('hogar_solidario_id', 'refugio_id');
        });

        Schema::table('hogares_solidarios', function (Blueprint $table) {
            $table->dropConstrainedForeignId('comuna_id');
            $table->dropColumn([
                'tipo_vivienda',
                'responsable_nombre',
                'responsable_cedula',
                'responsable_telefono',
                'habitantes',
            ]);
        });

        Schema::rename('hogares_solidarios', 'refugios');
    }
};
