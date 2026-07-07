<?php

declare(strict_types=1);

namespace App\Support;

use League\Flysystem\FilesystemException;
use Throwable;

final class StorageErrorMessage
{
    public static function for(Throwable $e): string
    {
        $message = $e->getMessage();

        if (str_contains($message, 'InvalidAccessKeyId')) {
            return 'Credenciales AWS inválidas en el servidor (Access Key). Revise Railway.';
        }

        if (str_contains($message, 'SignatureDoesNotMatch')) {
            return 'Secret AWS incorrecto en el servidor. Revise Railway.';
        }

        if (str_contains($message, 'AccessDenied') || str_contains($message, 'Access Denied')) {
            return 'Permisos IAM insuficientes para subir fotos al bucket S3.';
        }

        if (str_contains($message, 'NoSuchBucket')) {
            return 'El bucket S3 configurado no existe o la región es incorrecta.';
        }

        if (str_contains($message, 'AccessControlListNotSupported')) {
            return 'El bucket S3 no permite ACL. Contacte al administrador.';
        }

        if (
            $e instanceof FilesystemException
            || str_contains($message, 'Unable to write')
            || str_contains($message, 'AwsS3V3')
            || str_contains($message, 'Driver [s3]')
        ) {
            return 'No se pudo guardar la foto en el almacenamiento. Contacte al administrador.';
        }

        return 'No se pudo guardar la foto en el almacenamiento. Contacte al administrador.';
    }

    public static function isStorageFailure(Throwable $e): bool
    {
        $message = $e->getMessage();

        return $e instanceof FilesystemException
            || str_contains($message, 'Unable to write')
            || str_contains($message, 'AwsS3V3')
            || str_contains($message, 'Access Denied')
            || str_contains($message, 'AccessDenied')
            || str_contains($message, 'AccessControlListNotSupported')
            || str_contains($message, 'InvalidAccessKeyId')
            || str_contains($message, 'SignatureDoesNotMatch')
            || str_contains($message, 'NoSuchBucket')
            || str_contains($message, 'Driver [s3]');
    }
}
