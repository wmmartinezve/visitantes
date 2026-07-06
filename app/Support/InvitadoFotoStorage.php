<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

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

        $privateDisk = self::privateDisk();

        if (Storage::disk($privateDisk)->exists($path)) {
            return $privateDisk;
        }

        if (Storage::disk(self::LEGACY_DISK)->exists($path)) {
            return self::LEGACY_DISK;
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
}
