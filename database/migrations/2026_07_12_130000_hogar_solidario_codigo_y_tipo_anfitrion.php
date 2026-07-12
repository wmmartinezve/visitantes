<?php

use App\Support\HogarSolidarioCodigoGenerator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('hogares_solidarios')) {
            return;
        }

        Schema::table('hogares_solidarios', function (Blueprint $table) {
            if (! Schema::hasColumn('hogares_solidarios', 'codigo')) {
                $table->string('codigo', 32)->nullable()->after('id');
            }
            if (! Schema::hasColumn('hogares_solidarios', 'tipo_anfitrion')) {
                $table->string('tipo_anfitrion', 20)->default('familiar')->after('tipo_vivienda');
            }
            if (! Schema::hasColumn('hogares_solidarios', 'parentesco_anfitrion')) {
                $table->string('parentesco_anfitrion', 50)->nullable()->after('tipo_anfitrion');
            }
        });

        app(HogarSolidarioCodigoGenerator::class)->asignarCodigosFaltantes();

        if (Schema::hasColumn('hogares_solidarios', 'nombre')) {
            Schema::table('hogares_solidarios', function (Blueprint $table) {
                $table->dropColumn('nombre');
            });
        }

        Schema::table('hogares_solidarios', function (Blueprint $table) {
            $table->string('codigo', 32)->nullable(false)->unique()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('hogares_solidarios')) {
            return;
        }

        Schema::table('hogares_solidarios', function (Blueprint $table) {
            if (Schema::hasColumn('hogares_solidarios', 'codigo')) {
                $table->dropUnique(['codigo']);
                $table->dropColumn('codigo');
            }
            if (! Schema::hasColumn('hogares_solidarios', 'nombre')) {
                $table->string('nombre')->nullable();
            }
            if (Schema::hasColumn('hogares_solidarios', 'parentesco_anfitrion')) {
                $table->dropColumn('parentesco_anfitrion');
            }
            if (Schema::hasColumn('hogares_solidarios', 'tipo_anfitrion')) {
                $table->dropColumn('tipo_anfitrion');
            }
        });

        DB::table('hogares_solidarios')->update(['nombre' => 'Hogar solidario']);
    }
};
