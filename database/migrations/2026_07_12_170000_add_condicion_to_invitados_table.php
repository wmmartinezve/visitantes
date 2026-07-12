<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitados', function (Blueprint $table) {
            $table->string('condicion', 30)
                ->default('ninguna')
                ->after('parentesco');
        });
    }

    public function down(): void
    {
        Schema::table('invitados', function (Blueprint $table) {
            $table->dropColumn('condicion');
        });
    }
};
