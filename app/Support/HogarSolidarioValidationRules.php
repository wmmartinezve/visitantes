<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Validation\Rule;

final class HogarSolidarioValidationRules
{
    /**
     * @return array<string, mixed>
     */
    public static function forPayload(string $prefix = 'hogar'): array
    {
        $key = fn (string $field): string => "{$prefix}.{$field}";

        return array_merge(
            self::camposBase($key),
            self::reglasTipoAnfitrion($key),
        );
    }

    /**
     * @param  callable(string): string  $key
     * @return array<string, mixed>
     */
    private static function camposBase(callable $key): array
    {
        return [
            $key('tipo_vivienda') => ['required', 'string', 'in:casa,edificio'],
            $key('comuna_id') => ['required', 'integer', 'exists:comunas,id'],
            $key('parroquia_id') => ['required', 'integer', 'exists:parroquias,id'],
            $key('responsable_nombre') => ['required', 'string', 'max:255'],
            $key('responsable_cedula') => ['nullable', 'string', 'max:20'],
            $key('responsable_telefono') => ['nullable', 'string', 'max:30'],
            $key('direccion_exacta') => ['required', 'string', 'max:500'],
            $key('latitud') => ['required', 'numeric', 'between:-90,90'],
            $key('longitud') => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    /**
     * @param  callable(string): string  $key
     * @return array<string, mixed>
     */
    private static function reglasTipoAnfitrion(callable $key): array
    {
        $tipoField = $key('tipo_anfitrion');
        $parentescoField = $key('parentesco_anfitrion');

        return [
            $tipoField => ['required', 'string', 'in:familiar,amigo'],
            $parentescoField => [
                'nullable',
                'string',
                'max:50',
                Rule::requiredIf(fn () => request()->input($tipoField) === 'familiar'),
                Rule::prohibitedIf(fn () => request()->input($tipoField) === 'amigo'),
            ],
        ];
    }

    /**
     * Reglas para el paso «Hogar solidario» en Livewire / Flutter web (prefijo hogar_).
     *
     * @return array<string, mixed>
     */
    public static function forLivewirePasoHogar(): array
    {
        return [
            'hogar_tipo_vivienda' => ['required', 'string', 'in:casa,edificio'],
            'hogar_municipio_id' => ['required', 'integer', 'exists:municipios,id'],
            'hogar_parroquia_id' => ['required', 'integer', 'exists:parroquias,id'],
            'hogar_comuna_id' => ['required', 'integer', 'exists:comunas,id'],
            'hogar_direccion' => ['required', 'string', 'max:500'],
            'hogar_latitud' => ['required', 'numeric', 'between:-90,90'],
            'hogar_longitud' => ['required', 'numeric', 'between:-180,180'],
            'responsable_nombre' => ['required', 'string', 'max:255'],
            'responsable_cedula' => ['nullable', 'string', 'max:20'],
            'responsable_telefono' => ['nullable', 'string', 'max:30'],
            'hogar_tipo_anfitrion' => ['required', 'string', 'in:familiar,amigo'],
            'hogar_parentesco_anfitrion' => [
                'nullable',
                'string',
                'max:50',
                Rule::requiredIf(fn (): bool => request()->input('hogar_tipo_anfitrion') === 'familiar'),
                Rule::prohibitedIf(fn (): bool => request()->input('hogar_tipo_anfitrion') === 'amigo'),
            ],
        ];
    }

    /**
     * @deprecated Use forLivewirePasoHogar()
     *
     * @return array<string, mixed>
     */
    public static function forLivewire(bool $requiereHogar = true): array
    {
        return $requiereHogar ? self::forLivewirePasoHogar() : [];
    }
}
