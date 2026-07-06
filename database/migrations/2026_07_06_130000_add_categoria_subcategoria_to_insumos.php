<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventarios', function (Blueprint $table) {
            $table->string('categoria')->nullable()->after('centro_acopio_id');
            $table->string('subcategoria')->nullable()->after('categoria');
            $table->index(['centro_acopio_id', 'categoria', 'subcategoria']);
        });

        Schema::table('requerimientos', function (Blueprint $table) {
            $table->string('categoria')->nullable()->after('anfitrion_id');
            $table->string('subcategoria')->nullable()->after('categoria');
            $table->index(['categoria', 'subcategoria']);
        });

        $this->backfillInventarios();
        $this->backfillRequerimientos();
    }

    public function down(): void
    {
        Schema::table('requerimientos', function (Blueprint $table) {
            $table->dropIndex(['categoria', 'subcategoria']);
            $table->dropColumn(['categoria', 'subcategoria']);
        });

        Schema::table('inventarios', function (Blueprint $table) {
            $table->dropIndex(['centro_acopio_id', 'categoria', 'subcategoria']);
            $table->dropColumn(['categoria', 'subcategoria']);
        });
    }

    private function backfillInventarios(): void
    {
        DB::table('inventarios')->orderBy('id')->chunkById(100, function ($rows): void {
            foreach ($rows as $row) {
                $pair = \App\Support\InsumoCatalog::guessFromLabel((string) $row->item_nombre);

                if ($pair === null) {
                    continue;
                }

                DB::table('inventarios')->where('id', $row->id)->update([
                    'categoria' => $pair['categoria'],
                    'subcategoria' => $pair['subcategoria'],
                    'item_nombre' => \App\Support\InsumoCatalog::etiqueta($pair['categoria'], $pair['subcategoria']),
                ]);
            }
        });
    }

    private function backfillRequerimientos(): void
    {
        DB::table('requerimientos')->orderBy('id')->chunkById(100, function ($rows): void {
            foreach ($rows as $row) {
                $pair = \App\Support\InsumoCatalog::guessFromLabel((string) $row->item_solicitado);

                if ($pair === null) {
                    continue;
                }

                DB::table('requerimientos')->where('id', $row->id)->update([
                    'categoria' => $pair['categoria'],
                    'subcategoria' => $pair['subcategoria'],
                    'item_solicitado' => \App\Support\InsumoCatalog::etiqueta($pair['categoria'], $pair['subcategoria']),
                ]);
            }
        });
    }
};
