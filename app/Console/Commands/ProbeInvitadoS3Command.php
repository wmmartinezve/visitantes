<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\AwsRuntimeConfig;
use App\Support\InvitadoFotoStorage;
use App\Support\StorageErrorMessage;
use Aws\Sts\StsClient;
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
        AwsRuntimeConfig::applyS3DiskFromEnvironment();

        $disk = InvitadoFotoStorage::privateDisk();

        if ($disk !== 's3') {
            $this->warn("INVITADO_FOTOS_DISK={$disk} (no es s3). Prueba omitida.");

            return self::SUCCESS;
        }

        $key = (string) config('filesystems.disks.s3.key');
        $secret = (string) config('filesystems.disks.s3.secret');
        $bucket = (string) config('filesystems.disks.s3.bucket');
        $region = (string) config('filesystems.disks.s3.region');

        $this->line("Disco: {$disk}");
        $this->line('Bucket: '.($bucket !== '' ? $bucket : '(vacío)'));
        $this->line('Región: '.($region !== '' ? $region : '(vacía)'));
        $this->line('Access Key: '.($key !== '' ? substr($key, 0, 8).'…'.substr($key, -4).' ('.strlen($key).' chars)' : '(vacía)'));
        $this->line('Secret: '.($secret !== '' ? strlen($secret).' caracteres' : '(vacío)'));
        $this->line('Config cache: '.($this->laravel->configurationIsCached() ? 'sí (env runtime sobrescribe credenciales)' : 'no'));

        if ($key === '' || $secret === '' || $bucket === '' || $region === '') {
            $this->error('Faltan variables AWS en el entorno o en config:cache.');

            return self::FAILURE;
        }

        if (strlen($secret) !== 40) {
            $this->error('AWS_SECRET_ACCESS_KEY debe tener 40 caracteres. Parece truncado al copiar/pegar en Railway.');

            return self::FAILURE;
        }

        $testPath = 'invitados/fotos/_probe/'.Str::uuid().'.txt';

        try {
            $sts = new StsClient([
                'version' => 'latest',
                'region' => $region,
                'credentials' => [
                    'key' => $key,
                    'secret' => $secret,
                ],
            ]);
            $identity = $sts->getCallerIdentity();
            $this->info('STS OK → '.($identity['Arn'] ?? 'identidad verificada'));
        } catch (Throwable $e) {
            $this->error('El Access Key y el Secret NO forman un par válido en AWS.');
            $this->line(StorageErrorMessage::for($e));
            $this->warn('Borrá AWS_ACCESS_KEY_ID y AWS_SECRET_ACCESS_KEY en Railway y pegá ambos desde el mismo archivo .csv.');

            return self::FAILURE;
        }

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
