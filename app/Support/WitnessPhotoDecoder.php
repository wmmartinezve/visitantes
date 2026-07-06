<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

final class WitnessPhotoDecoder
{
    public static function toUploadedFile(string $base64, string $mime): UploadedFile
    {
        $raw = base64_decode(preg_replace('#^data:[^;]+;base64,#', '', $base64) ?: '', true);

        if ($raw === false) {
            throw new RuntimeException('No se pudo decodificar la foto.');
        }

        if (strlen($raw) > 8 * 1024 * 1024) {
            throw new RuntimeException('La foto supera el tamaño máximo permitido (8 MB).');
        }

        $imageInfo = @getimagesizefromstring($raw);

        if ($imageInfo === false) {
            throw new RuntimeException('El archivo no es una imagen válida.');
        }

        $extension = match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => 'jpg',
        };

        $tmp = tempnam(sys_get_temp_dir(), 'witness_foto_');
        if ($tmp === false) {
            throw new RuntimeException('No se pudo crear archivo temporal.');
        }

        file_put_contents($tmp, $raw);

        return new UploadedFile($tmp, Str::uuid().'.'.$extension, $mime, null, true);
    }
}
