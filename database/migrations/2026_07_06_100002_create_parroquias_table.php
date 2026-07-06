<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parroquias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('municipio_id')->constrained('municipios')->cascadeOnDelete();
            $table->string('nombre');
            $table->timestamps();

            $table->index(['municipio_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parroquias');
    }
};
