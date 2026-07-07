<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Invitado;
use App\Support\AwsRuntimeConfig;
use App\Support\InvitadoFotoStorage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class AuditInvitadoFotosCommand extends Command
{
    protected $signature = 'visitantes:audit-fotos
                            {--migrate-local : Copia fotos del disco local al S3 configurado}
                            {--clear-missing : Limpia foto_ingreso en registros cuyo archivo no existe en ningún disco}';

    protected $description = 'Audita rutas foto_ingreso vs archivos reales; opcionalmente migra local→S3 o limpia huérfanos';

    public function handle(): int
    {
        AwsRuntimeConfig::applyS3DiskFromEnvironment();

        $invitados = Invitado::query()
            ->whereNotNull('foto_ingreso')
            ->where('foto_ingreso', '!=', '')
            ->orderBy('id')
            ->get(['id', 'nombre', 'apellido', 'foto_ingreso']);

        if ($invitados->isEmpty()) {
            $this->info('No hay Invitados con foto_ingreso en la base de datos.');

            return self::SUCCESS;
        }

        $ok = 0;
        $missing = [];
        $migrated = 0;

        foreach ($invitados as $invitado) {
            $path = (string) $invitado->foto_ingreso;
            $disk = InvitadoFotoStorage::diskForPath($path);

            if ($disk !== null) {
                $ok++;

                if ($this->option('migrate-local') && $disk === 'local' && InvitadoFotoStorage::privateDisk() === 's3') {
                    if ($this->migrateLocalToS3($path)) {
                        $migrated++;
                        $this->line("  ↑ migrado a S3: #{$invitado->id} {$path}");
                    }
                }

                continue;
            }

            $missing[] = $invitado;
            $this->warn("  ✗ #{$invitado->id} {$invitado->nombreCompleto()} → {$path}");
        }

        $this->newLine();
        $this->info("OK: {$ok} · Faltantes: ".count($missing)." · Total: {$invitados->count()}");

        if ($migrated > 0) {
            $this->info("Migrados local→S3: {$migrated}");
        }

        if ($missing === []) {
            return self::SUCCESS;
        }

        if ($this->option('clear-missing')) {
            if (! $this->confirm('¿Limpiar foto_ingreso de '.count($missing).' registro(s) sin archivo?')) {
                return self::SUCCESS;
            }

            foreach ($missing as $invitado) {
                $invitado->update(['foto_ingreso' => null]);
            }

            $this->info('Rutas huérfanas limpiadas. Vuelva a subir la foto desde la app o el panel admin.');

            return self::SUCCESS;
        }

        $this->comment('Las fotos faltantes deben volver a subirse (app móvil o panel admin → Reemplazar foto).');
        $this->comment('Opciones: --migrate-local (si aún están en disco local) · --clear-missing (limpia rutas rotas).');

        return self::FAILURE;
    }

    private function migrateLocalToS3(string $path): bool
    {
        if (! Storage::disk('local')->exists($path)) {
            return false;
        }

        if (Storage::disk('s3')->exists($path)) {
            return false;
        }

        $contents = Storage::disk('local')->get($path);

        Storage::disk('s3')->put($path, $contents, ['ACL' => '']);

        return Storage::disk('s3')->exists($path);
    }
}
