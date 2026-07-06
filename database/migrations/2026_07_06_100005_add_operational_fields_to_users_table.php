<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('rol')->default('admin')->after('email');
            $table->foreignId('refugio_id')->nullable()->after('rol')->constrained('refugios')->nullOnDelete();
            $table->foreignId('centro_acopio_id')->nullable()->after('refugio_id')->constrained('centros_acopio')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('centro_acopio_id');
            $table->dropConstrainedForeignId('refugio_id');
            $table->dropColumn('rol');
        });
    }
};
