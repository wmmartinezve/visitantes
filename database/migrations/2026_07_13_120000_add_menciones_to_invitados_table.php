<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invitados', function (Blueprint $table): void {
            $table->json('menciones_ayudas')->nullable()->after('estatus');
            $table->json('menciones_salud')->nullable()->after('menciones_ayudas');
            $table->json('menciones_tramites')->nullable()->after('menciones_salud');
            $table->string('menciones_nota', 500)->nullable()->after('menciones_tramites');
        });
    }

    public function down(): void
    {
        Schema::table('invitados', function (Blueprint $table): void {
            $table->dropColumn([
                'menciones_ayudas',
                'menciones_salud',
                'menciones_tramites',
                'menciones_nota',
            ]);
        });
    }
};
