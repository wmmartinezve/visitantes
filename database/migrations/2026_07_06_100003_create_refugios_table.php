<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refugios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->foreignId('parroquia_id')->constrained('parroquias')->restrictOnDelete();
            $table->decimal('latitud', 10, 8);
            $table->decimal('longitud', 11, 8);
            $table->text('direccion_exacta');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refugios');
    }
};
