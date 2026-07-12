<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Invitado;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

final class InvitadoFotoStorage
{
    public const LEGACY_DISK = 'public';

    public static function privateDisk(): string
    {
        return (string) config('visitantes.invitado_fotos_disk', 'local');
    }

    public static function storePath(int $invitadoId, string $filename): string
    {
        return "invitados/fotos/{$invitadoId}/{$filename}";
    }

    public static function diskForPath(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        foreach (array_unique([self::privateDisk(), 'local', self::LEGACY_DISK]) as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return $disk;
            }
        }

        return null;
    }

    public static function filesystem(?string $path): ?Filesystem
    {
        $disk = self::diskForPath($path);

        return $disk !== null ? Storage::disk($disk) : null;
    }

    public static function exists(?string $path): bool
    {
        return self::diskForPath($path) !== null;
    }

    /**
     * URL apta para etiquetas <img> (presigned S3 o ruta firmada sin depender de cookies).
     */
    public static function displayUrl(string $path, object $routeParameter, string $routeName = 'invitados.foto'): ?string
    {
        if ($path === '') {
            return null;
        }

        $disk = self::diskForPath($path);

        if ($disk === null) {
            return null;
        }

        if ($disk === 's3') {
            try {
                return Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(30));
            } catch (\Throwable $exception) {
                report($exception);

                return null;
            }
        }

        return URL::temporarySignedRoute($routeName, now()->addMinutes(30), $routeParameter);
    }

    public static function storeUploadedFile(UploadedFile $foto, int $invitadoId, string $filename): string
    {
        $disk = self::privateDisk();
        $directory = "invitados/fotos/{$invitadoId}";

        $path = \Illuminate\Support\Facades\Storage::disk($disk)->putFileAs(
            $directory,
            $foto,
            $filename,
            ['ACL' => ''],
        );

        if ($path === false || ! Storage::disk($disk)->exists($path)) {
            throw new \RuntimeException('Unable to write file to invitado photo storage.');
        }

        return $path;
    }

    public static function finalizeUploadedPath(string $uploadedPath, int $invitadoId): string
    {
        $finalPath = self::storePath($invitadoId, basename($uploadedPath));

        if ($uploadedPath === $finalPath) {
            return $finalPath;
        }

        $disk = self::privateDisk();
        $filesystem = Storage::disk($disk);

        if ($filesystem->exists($uploadedPath)) {
            $filesystem->move($uploadedPath, $finalPath);
        }

        return $finalPath;
    }

    /**
     * Mueve un archivo ya subido (p. ej. tmp de Filament en S3) a la ruta final del Invitado.
     *
     * @throws \RuntimeException
     */
    public static function attachTemporaryUpload(string $uploadedPath, int $invitadoId): string
    {
        if (! self::exists($uploadedPath)) {
            throw new \RuntimeException('No se encontró la foto temporal subida.');
        }

        return self::finalizeUploadedPath($uploadedPath, $invitadoId);
    }

    public static function fotoUploadDirectory(?Invitado $record): string
    {
        if ($record === null) {
            return 'invitados/fotos/pendientes';
        }

        $targetId = $record->esJefeDeFamilia()
            ? $record->id
            : ($record->jefe_familia_id ?? $record->id);

        return 'invitados/fotos/'.$targetId;
    }
}
