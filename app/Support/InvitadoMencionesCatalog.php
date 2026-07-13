<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Invitado;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

final class InvitadoMencionesCatalog
{
    public const CATEGORIA_AYUDAS = 'ayudas';

    public const CATEGORIA_SALUD = 'salud';

    public const CATEGORIA_TRAMITES = 'tramites';

    /** @return array<string, array<string, string>> */
    public static function catalog(): array
    {
        return config('visitantes.menciones', []);
    }

    /** @return array<string, string> */
    public static function opciones(string $categoria): array
    {
        return self::catalog()[$categoria] ?? [];
    }

    /** @return list<string> */
    public static function keys(string $categoria): array
    {
        return array_keys(self::opciones($categoria));
    }

    public static function label(string $categoria, string $key): ?string
    {
        return self::opciones($categoria)[$key] ?? null;
    }

    public static function isValidKey(string $categoria, string $key): bool
    {
        return array_key_exists($key, self::opciones($categoria));
    }

    /**
     * Normaliza una lista de claves: únicas, válidas, orden estable.
     *
     * @param  list<string>|null  $keys
     * @return list<string>|null
     */
    public static function normalizeKeys(?array $keys, string $categoria): ?array
    {
        if ($keys === null) {
            return null;
        }

        $valid = [];

        foreach ($keys as $key) {
            if (! is_string($key) || $key === '') {
                continue;
            }

            if (! self::isValidKey($categoria, $key)) {
                continue;
            }

            $valid[$key] = true;
        }

        if ($valid === []) {
            return null;
        }

        return array_keys($valid);
    }

    /**
     * @param  array{
     *     menciones_ayudas?: list<string>|null,
     *     menciones_salud?: list<string>|null,
     *     menciones_tramites?: list<string>|null,
     *     menciones_nota?: string|null,
     * }  $payload
     * @return array{
     *     menciones_ayudas: list<string>|null,
     *     menciones_salud: list<string>|null,
     *     menciones_tramites: list<string>|null,
     *     menciones_nota: string|null,
     * }
     */
    public static function normalizePayload(array $payload): array
    {
        $nota = isset($payload['menciones_nota']) ? trim((string) $payload['menciones_nota']) : null;

        return [
            'menciones_ayudas' => self::normalizeKeys($payload['menciones_ayudas'] ?? null, self::CATEGORIA_AYUDAS),
            'menciones_salud' => self::normalizeKeys($payload['menciones_salud'] ?? null, self::CATEGORIA_SALUD),
            'menciones_tramites' => self::normalizeKeys($payload['menciones_tramites'] ?? null, self::CATEGORIA_TRAMITES),
            'menciones_nota' => $nota !== '' ? $nota : null,
        ];
    }

    /**
     * @param  list<string>|null  $keys
     * @return list<array{value: string, label: string}>
     */
    public static function labelsForApi(?array $keys, string $categoria): array
    {
        if ($keys === null || $keys === []) {
            return [];
        }

        return collect($keys)
            ->map(fn (string $key): ?array => self::label($categoria, $key) !== null
                ? ['value' => $key, 'label' => self::label($categoria, $key)]
                : null)
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Catálogo completo para offline / selectores móviles.
     *
     * @return array<string, list<array{value: string, label: string}>>
     */
    public static function forApi(): array
    {
        $result = [];

        foreach (self::catalog() as $categoria => $opciones) {
            $result[$categoria] = collect($opciones)
                ->map(fn (string $label, string $value): array => ['value' => $value, 'label' => $label])
                ->values()
                ->all();
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public static function validationRules(string $prefix = ''): array
    {
        $field = static fn (string $name): string => $prefix.$name;

        return [
            $field('menciones_ayudas') => ['sometimes', 'nullable', 'array'],
            $field('menciones_ayudas.*') => ['string', Rule::in(self::keys(self::CATEGORIA_AYUDAS))],
            $field('menciones_salud') => ['sometimes', 'nullable', 'array'],
            $field('menciones_salud.*') => ['string', Rule::in(self::keys(self::CATEGORIA_SALUD))],
            $field('menciones_tramites') => ['sometimes', 'nullable', 'array'],
            $field('menciones_tramites.*') => ['string', Rule::in(self::keys(self::CATEGORIA_TRAMITES))],
            $field('menciones_nota') => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function resourcePayload(?Invitado $invitado = null): array
    {
        if ($invitado === null) {
            return self::emptyResourcePayload();
        }

        return [
            'menciones_ayudas' => $invitado->menciones_ayudas ?? [],
            'menciones_salud' => $invitado->menciones_salud ?? [],
            'menciones_tramites' => $invitado->menciones_tramites ?? [],
            'menciones_nota' => $invitado->menciones_nota,
            'menciones' => [
                'ayudas' => self::labelsForApi($invitado->menciones_ayudas, self::CATEGORIA_AYUDAS),
                'salud' => self::labelsForApi($invitado->menciones_salud, self::CATEGORIA_SALUD),
                'tramites' => self::labelsForApi($invitado->menciones_tramites, self::CATEGORIA_TRAMITES),
                'nota' => $invitado->menciones_nota,
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function emptyResourcePayload(): array
    {
        return [
            'menciones_ayudas' => [],
            'menciones_salud' => [],
            'menciones_tramites' => [],
            'menciones_nota' => null,
            'menciones' => [
                'ayudas' => [],
                'salud' => [],
                'tramites' => [],
                'nota' => null,
            ],
        ];
    }

    public static function columnaParaCategoria(string $categoria): string
    {
        return match ($categoria) {
            self::CATEGORIA_AYUDAS => 'menciones_ayudas',
            self::CATEGORIA_SALUD => 'menciones_salud',
            self::CATEGORIA_TRAMITES => 'menciones_tramites',
            default => throw new \InvalidArgumentException("Categoría de menciones desconocida: {$categoria}"),
        };
    }

    /** @return list<string> */
    public static function columnasMenciones(): array
    {
        return [
            'menciones_ayudas',
            'menciones_salud',
            'menciones_tramites',
        ];
    }

    public static function tieneMenciones(?Invitado $invitado): bool
    {
        if ($invitado === null) {
            return false;
        }

        foreach (self::columnasMenciones() as $column) {
            $keys = $invitado->{$column};
            if (is_array($keys) && $keys !== []) {
                return true;
            }
        }

        return filled(trim((string) ($invitado->menciones_nota ?? '')));
    }

    public static function resumenTexto(Invitado $invitado): string
    {
        $partes = [];

        foreach ([
            self::CATEGORIA_AYUDAS => $invitado->menciones_ayudas,
            self::CATEGORIA_SALUD => $invitado->menciones_salud,
            self::CATEGORIA_TRAMITES => $invitado->menciones_tramites,
        ] as $categoria => $keys) {
            if (! is_array($keys) || $keys === []) {
                continue;
            }

            $etiquetas = collect(self::labelsForApi($keys, $categoria))
                ->pluck('label')
                ->implode(', ');

            if ($etiquetas !== '') {
                $partes[] = $etiquetas;
            }
        }

        return $partes === [] ? '—' : implode(' · ', $partes);
    }

    /**
     * @param  list<string>|null  $keys
     */
    public static function etiquetasCsv(?array $keys, string $categoria): string
    {
        if ($keys === null || $keys === []) {
            return '';
        }

        return collect(self::labelsForApi($keys, $categoria))
            ->pluck('label')
            ->implode('; ');
    }

    /**
     * @param  Builder<Invitado>  $query
     * @return Builder<Invitado>
     */
    public static function scopeConValoresEnColumna(Builder $query, string $column): Builder
    {
        return $query
            ->whereNotNull($column)
            ->where($column, '!=', '[]')
            ->where($column, '!=', 'null');
    }

    /**
     * @param  Builder<Invitado>  $query
     * @return Builder<Invitado>
     */
    public static function scopeConAlgunaMencion(Builder $query): Builder
    {
        return $query->where(function (Builder $sub): void {
            foreach (self::columnasMenciones() as $column) {
                $sub->orWhere(fn (Builder $q) => self::scopeConValoresEnColumna($q, $column));
            }

            $sub->orWhere(fn (Builder $q) => $q
                ->whereNotNull('menciones_nota')
                ->where('menciones_nota', '!=', ''));
        });
    }
}
