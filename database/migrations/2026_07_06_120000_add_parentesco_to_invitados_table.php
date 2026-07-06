<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitados', function (Blueprint $table) {
            $table->string('parentesco')->nullable()->after('jefe_familia_id');
        });
    }

    public function down(): void
    {
        Schema::table('invitados', function (Blueprint $table) {
            $table->dropColumn('parentesco');
        });
    }
};
