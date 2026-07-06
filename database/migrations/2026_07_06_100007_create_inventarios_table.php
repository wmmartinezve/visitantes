<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('centro_acopio_id')->constrained('centros_acopio')->cascadeOnDelete();
            $table->string('item_nombre');
            $table->unsignedInteger('cantidad')->default(0);
            $table->string('unidad_medida');
            $table->timestamps();

            $table->index(['centro_acopio_id', 'item_nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventarios');
    }
};
