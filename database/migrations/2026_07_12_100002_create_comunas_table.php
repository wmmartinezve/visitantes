<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comunas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parroquia_id')->constrained('parroquias')->restrictOnDelete();
            $table->string('nombre');
            $table->timestamps();

            $table->unique(['parroquia_id', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comunas');
    }
};
