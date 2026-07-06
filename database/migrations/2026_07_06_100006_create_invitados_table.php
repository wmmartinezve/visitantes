<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jefe_familia_id')->nullable()->constrained('invitados')->nullOnDelete();
            $table->string('nombre');
            $table->string('apellido');
            $table->string('cedula')->nullable();
            $table->date('fecha_nacimiento');
            $table->string('telefono')->nullable();
            $table->string('foto_ingreso')->nullable();
            $table->foreignId('refugio_id')->constrained('refugios')->restrictOnDelete();
            $table->string('estatus')->default('activo');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['refugio_id', 'estatus']);
            $table->index('cedula');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitados');
    }
};
