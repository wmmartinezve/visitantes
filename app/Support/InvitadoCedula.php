<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\Validator;

final class InvitadoCedula
{
    public static function normalize(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $cedula = trim((string) $value);

        return $cedula === '' ? null : $cedula;
    }

    /**
     * @return list<\Illuminate\Contracts\Validation\ValidationRule|string>
     */
    public static function rules(?int $ignoreId = null): array
    {
        return [
            'nullable',
            'string',
            'max:20',
            self::uniqueRule($ignoreId),
        ];
    }

    public static function uniqueRule(?int $ignoreId = null): Unique
    {
        $rule = Rule::unique('invitados', 'cedula')->whereNull('deleted_at');

        if ($ignoreId !== null) {
            $rule->ignore($ignoreId);
        }

        return $rule;
    }

    /**
     * @return array<string, string>
     */
    public static function validationMessages(): array
    {
        return [
            'cedula.unique' => 'Esta cédula ya está registrada.',
            'familiares.*.cedula.unique' => 'Esta cédula ya está registrada.',
            'jefe_cedula.unique' => 'Esta cédula ya está registrada.',
        ];
    }

    /**
     * Normaliza cédulas vacías a null en un payload de registro (jefe + familiares).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalizePayload(array $data): array
    {
        if (array_key_exists('cedula', $data)) {
            $data['cedula'] = self::normalize($data['cedula'] ?? null);
        }

        if (array_key_exists('jefe_cedula', $data)) {
            $data['jefe_cedula'] = self::normalize($data['jefe_cedula'] ?? null);
        }

        if (isset($data['familiares']) && is_array($data['familiares'])) {
            $data['familiares'] = array_map(function (mixed $familiar): mixed {
                if (! is_array($familiar)) {
                    return $familiar;
                }

                if (array_key_exists('cedula', $familiar)) {
                    $familiar['cedula'] = self::normalize($familiar['cedula'] ?? null);
                }

                return $familiar;
            }, $data['familiares']);
        }

        return $data;
    }

    /**
     * Verifica que jefe y familiares no repitan la misma cédula en el payload.
     *
     * @param  array<string, mixed>  $data
     */
    public static function validateDistinctInPayload(Validator $validator, array $data, string $jefeKey = 'cedula'): void
    {
        $seen = [];

        $jefe = self::normalize($data[$jefeKey] ?? null);
        if ($jefe !== null) {
            $seen[$jefe] = $jefeKey;
        }

        foreach ($data['familiares'] ?? [] as $index => $familiar) {
            if (! is_array($familiar)) {
                continue;
            }

            $cedula = self::normalize($familiar['cedula'] ?? null);
            if ($cedula === null) {
                continue;
            }

            if (isset($seen[$cedula])) {
                $validator->errors()->add(
                    "familiares.{$index}.cedula",
                    'Hay una cédula repetida en este registro (jefe u otro familiar).',
                );

                continue;
            }

            $seen[$cedula] = "familiares.{$index}.cedula";
        }
    }
}
