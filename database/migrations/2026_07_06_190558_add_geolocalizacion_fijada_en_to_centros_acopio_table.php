<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('centros_acopio', function (Blueprint $table) {
            $table->timestamp('geolocalizacion_fijada_en')->nullable()->after('longitud');
        });
    }

    public function down(): void
    {
        Schema::table('centros_acopio', function (Blueprint $table) {
            $table->dropColumn('geolocalizacion_fijada_en');
        });
    }
};
