<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Storage;

final class AwsRuntimeConfig
{
    /**
     * AWS credentials must leerse en runtime (getenv) para no quedar obsoletas
     * tras config:cache o al usar `railway run` con variables inyectadas.
     */
    public static function applyS3DiskFromEnvironment(): void
    {
        $overrides = [];

        foreach ([
            'AWS_ACCESS_KEY_ID' => 'filesystems.disks.s3.key',
            'AWS_SECRET_ACCESS_KEY' => 'filesystems.disks.s3.secret',
            'AWS_DEFAULT_REGION' => 'filesystems.disks.s3.region',
            'AWS_BUCKET' => 'filesystems.disks.s3.bucket',
        ] as $env => $configKey) {
            $value = getenv($env);

            if ($value === false || $value === '') {
                continue;
            }

            $overrides[$configKey] = trim($value);
        }

        if ($overrides === []) {
            return;
        }

        config($overrides);

        if (app()->bound('filesystem')) {
            Storage::forgetDisk('s3');
        }
    }
}
