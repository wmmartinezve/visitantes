<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

final class InvitadoFotoStorage
{
    public const PRIVATE_DISK = 'local';

    public const LEGACY_DISK = 'public';

    public static function storePath(int $invitadoId, string $filename): string
    {
        return "invitados/fotos/{$invitadoId}/{$filename}";
    }

    public static function diskForPath(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (Storage::disk(self::PRIVATE_DISK)->exists($path)) {
            return self::PRIVATE_DISK;
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
