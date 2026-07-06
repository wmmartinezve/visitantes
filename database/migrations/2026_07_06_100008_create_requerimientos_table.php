<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requerimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invitado_id')->constrained('invitados')->restrictOnDelete();
            $table->foreignId('anfitrion_id')->constrained('users')->restrictOnDelete();
            $table->string('item_solicitado');
            $table->unsignedInteger('cantidad')->default(1);
            $table->string('estatus')->default('pendiente');
            $table->foreignId('centro_acopio_id')->nullable()->constrained('centros_acopio')->nullOnDelete();
            $table->timestamps();

            $table->index(['estatus', 'centro_acopio_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requerimientos');
    }
};
