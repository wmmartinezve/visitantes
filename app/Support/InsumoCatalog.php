<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

final class InsumoCatalog
{
    /** @return array<string, list<string>> */
    public static function catalog(): array
    {
        return config('visitantes.insumos_catalogo', []);
    }

    /** @return list<string> */
    public static function categorias(): array
    {
        return array_keys(self::catalog());
    }

    /** @return list<string> */
    public static function subcategorias(string $categoria): array
    {
        return self::catalog()[$categoria] ?? [];
    }

    public static function isValidPair(?string $categoria, ?string $subcategoria): bool
    {
        if ($categoria === null || $subcategoria === null || $categoria === '' || $subcategoria === '') {
            return false;
        }

        return in_array($subcategoria, self::subcategorias($categoria), true);
    }

    public static function etiqueta(string $categoria, string $subcategoria): string
    {
        return "{$categoria} · {$subcategoria}";
    }

    /**
     * @return array{categoria: string, subcategoria: string}|null
     */
    public static function resolvePair(string $categoria, string $subcategoria): ?array
    {
        if (! self::isValidPair($categoria, $subcategoria)) {
            return null;
        }

        return [
            'categoria' => $categoria,
            'subcategoria' => $subcategoria,
        ];
    }

    /**
     * @return array{categoria: string, subcategoria: string}
     */
    public static function assertPair(string $categoria, string $subcategoria): array
    {
        $pair = self::resolvePair($categoria, $subcategoria);

        if ($pair === null) {
            throw new InvalidArgumentException('Categoría o subcategoría de insumo no válida.');
        }

        return $pair;
    }

    /** @return list<string> */
    public static function flatSubcategorias(): array
    {
        return array_values(array_unique(array_merge(...array_values(self::catalog()))));
    }

    /**
     * Intenta inferir categoría/subcategoría desde un nombre legado.
     *
     * @return array{categoria: string, subcategoria: string}|null
     */
    public static function guessFromLabel(string $label): ?array
    {
        $label = trim($label);

        if ($label === '') {
            return null;
        }

        if (str_contains($label, ' · ')) {
            [$categoria, $subcategoria] = explode(' · ', $label, 2);

            return self::resolvePair(trim($categoria), trim($subcategoria));
        }

        foreach (self::catalog() as $categoria => $subcategorias) {
            foreach ($subcategorias as $subcategoria) {
                if (strcasecmp($subcategoria, $label) === 0) {
                    return ['categoria' => $categoria, 'subcategoria' => $subcategoria];
                }
            }
        }

        foreach (self::catalog() as $categoria => $subcategorias) {
            if (strcasecmp($categoria, $label) === 0 && count($subcategorias) === 1) {
                return ['categoria' => $categoria, 'subcategoria' => $subcategorias[0]];
            }
        }

        return null;
    }

    /**
     * @return array{categoria: string, subcategoria: string, item_nombre: string}
     */
    public static function normalizeInventario(array $data): array
    {
        $categoria = trim((string) ($data['categoria'] ?? ''));
        $subcategoria = trim((string) ($data['subcategoria'] ?? ''));

        if ($categoria !== '' && $subcategoria !== '') {
            $pair = self::assertPair($categoria, $subcategoria);
        } else {
            $legacy = trim((string) ($data['item_nombre'] ?? ''));
            $pair = self::guessFromLabel($legacy);

            if ($pair === null) {
                throw new InvalidArgumentException('Debe indicar categoría y subcategoría del insumo.');
            }
        }

        return [
            'categoria' => $pair['categoria'],
            'subcategoria' => $pair['subcategoria'],
            'item_nombre' => self::etiqueta($pair['categoria'], $pair['subcategoria']),
        ];
    }

    /**
     * @return array{categoria: string, subcategoria: string, item_solicitado: string}
     */
    public static function normalizeRequerimiento(array $data): array
    {
        $categoria = trim((string) ($data['categoria'] ?? ''));
        $subcategoria = trim((string) ($data['subcategoria'] ?? ''));

        if ($categoria !== '' && $subcategoria !== '') {
            $pair = self::assertPair($categoria, $subcategoria);
        } else {
            $legacy = trim((string) ($data['item_solicitado'] ?? ''));
            $pair = self::guessFromLabel($legacy);

            if ($pair === null) {
                throw new InvalidArgumentException('Debe indicar categoría y subcategoría del insumo.');
            }
        }

        return [
            'categoria' => $pair['categoria'],
            'subcategoria' => $pair['subcategoria'],
            'item_solicitado' => self::etiqueta($pair['categoria'], $pair['subcategoria']),
        ];
    }
}
