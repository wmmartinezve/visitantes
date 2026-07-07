<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\InvitadoFotoStorage;
use App\Support\StorageErrorMessage;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ProbeInvitadoS3Command extends Command
{
    protected $signature = 'visitantes:probe-s3';

    protected $description = 'Verifica conexión y permisos S3 para fotos de Invitados';

    public function handle(): int
    {
        $disk = InvitadoFotoStorage::privateDisk();

        if ($disk !== 's3') {
            $this->warn("INVITADO_FOTOS_DISK={$disk} (no es s3). Prueba omitida.");

            return self::SUCCESS;
        }

        $key = (string) config('filesystems.disks.s3.key');
        $bucket = (string) config('filesystems.disks.s3.bucket');
        $region = (string) config('filesystems.disks.s3.region');

        $this->line("Disco: {$disk}");
        $this->line('Bucket: '.($bucket !== '' ? $bucket : '(vacío)'));
        $this->line('Región: '.($region !== '' ? $region : '(vacía)'));
        $this->line('Access Key: '.($key !== '' ? substr($key, 0, 8).'…' : '(vacía)'));

        if ($key === '' || $bucket === '' || $region === '') {
            $this->error('Faltan variables AWS en el entorno o en config:cache.');

            return self::FAILURE;
        }

        $testPath = 'invitados/fotos/_probe/'.Str::uuid().'.txt';

        try {
            Storage::disk('s3')->put($testPath, 'probe-'.now()->toIso8601String(), ['ACL' => '']);
            $this->info('PutObject OK → '.$testPath);
            Storage::disk('s3')->delete($testPath);
            $this->info('DeleteObject OK. S3 listo para fotos de Invitados.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error(StorageErrorMessage::for($e));
            $this->line('Detalle: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
