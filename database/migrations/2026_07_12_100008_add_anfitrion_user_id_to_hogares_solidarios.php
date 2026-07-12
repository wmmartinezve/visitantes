<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\AnfitrionMobileProfileService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('hogares_solidarios', 'anfitrion_user_id')) {
            Schema::table('hogares_solidarios', function (Blueprint $table): void {
                $table->foreignId('anfitrion_user_id')
                    ->nullable()
                    ->after('codigo')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        $anfitriones = DB::table('users')
            ->where('rol', 'anfitrion')
            ->whereNotNull('hogar_solidario_id')
            ->whereNotNull('hogar_vinculado_en')
            ->get(['id', 'hogar_solidario_id', 'hogar_vinculado_en']);

        foreach ($anfitriones as $anfitrion) {
            $hogarId = (int) $anfitrion->hogar_solidario_id;

            $tieneInvitadosPrevios = DB::table('invitados')
                ->where('hogar_solidario_id', $hogarId)
                ->where('created_at', '<=', $anfitrion->hogar_vinculado_en)
                ->exists();

            if ($tieneInvitadosPrevios) {
                continue;
            }

            DB::table('hogares_solidarios')
                ->where('id', $hogarId)
                ->whereNull('anfitrion_user_id')
                ->update(['anfitrion_user_id' => $anfitrion->id]);
        }

        User::query()
            ->where('rol', 'anfitrion')
            ->whereNotNull('hogar_solidario_id')
            ->each(fn (User $user) => app(AnfitrionMobileProfileService::class)->normalize($user));
    }

    public function down(): void
    {
        if (Schema::hasColumn('hogares_solidarios', 'anfitrion_user_id')) {
            Schema::table('hogares_solidarios', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('anfitrion_user_id');
            });
        }
    }
};
