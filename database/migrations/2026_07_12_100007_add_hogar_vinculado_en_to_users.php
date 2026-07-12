<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'hogar_vinculado_en')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->timestamp('hogar_vinculado_en')->nullable()->after('hogar_solidario_id');
            });
        }

        $anfitriones = DB::table('users')
            ->where('rol', 'anfitrion')
            ->whereNotNull('hogar_solidario_id')
            ->get(['id', 'hogar_solidario_id', 'created_at']);

        foreach ($anfitriones as $anfitrion) {
            if (DB::table('users')->where('id', $anfitrion->id)->value('hogar_vinculado_en') !== null) {
                continue;
            }

            $hogar = DB::table('hogares_solidarios')
                ->where('id', $anfitrion->hogar_solidario_id)
                ->first(['created_at']);

            if ($hogar !== null && $hogar->created_at > $anfitrion->created_at) {
                DB::table('users')
                    ->where('id', $anfitrion->id)
                    ->update(['hogar_vinculado_en' => $hogar->created_at]);
            }
        }

        DB::table('users')
            ->where('rol', 'anfitrion')
            ->whereNotNull('hogar_solidario_id')
            ->whereNull('hogar_vinculado_en')
            ->update(['hogar_solidario_id' => null]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'hogar_vinculado_en')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('hogar_vinculado_en');
            });
        }
    }
};
