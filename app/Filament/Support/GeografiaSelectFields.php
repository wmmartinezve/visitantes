<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Models\Estado;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Database\Eloquent\Model;

final class GeografiaSelectFields
{
    /**
     * Cascada Estado → Municipio → Parroquia → Comuna (opcional).
     *
     * @param  array{
     *     prefix?: string,
     *     labels?: array{estado?: string, municipio?: string, parroquia?: string, comuna?: string},
     *     includeComuna?: bool,
     *     estadoScope?: 'all'|'anzoategui'|null,
     *     estadoHelper?: string|null,
     *     persistEstadoMunicipio?: bool,
     *     dehydrateEstadoMunicipio?: bool,
     *     requireEstadoMunicipio?: bool,
     *     record?: Model|null,
     * }  $options
     * @return array<int, Forms\Components\Component>
     */
    public static function make(array $options = []): array
    {
        $prefix = $options['prefix'] ?? '';
        $labels = $options['labels'] ?? [];
        $includeComuna = $options['includeComuna'] ?? true;
        $estadoScope = $options['estadoScope'] ?? 'all';
        $record = $options['record'] ?? null;
        $persistEstadoMunicipio = $options['persistEstadoMunicipio'] ?? false;
        $dehydrateEstadoMunicipio = $options['dehydrateEstadoMunicipio'] ?? $persistEstadoMunicipio;
        $requireEstadoMunicipio = $options['requireEstadoMunicipio'] ?? $persistEstadoMunicipio;

        $estadoField = $prefix.'estado_id';
        $municipioField = $prefix.'municipio_id';
        $parroquiaField = $prefix.'parroquia_id';
        $comunaField = $prefix.'comuna_id';

        $defaultEstadoId = self::defaultEstadoId($record, $estadoScope);
        $defaultMunicipioId = $record?->parroquia?->municipio_id;
        $estadoFijo = $estadoScope === 'anzoategui';

        $fields = [
            Forms\Components\Select::make($estadoField)
                ->label($labels['estado'] ?? 'Estado')
                ->options(fn (): array => GeografiaSelectOptions::estados($estadoScope))
                ->searchable(! $estadoFijo)
                ->required($requireEstadoMunicipio)
                ->live()
                ->dehydrated($dehydrateEstadoMunicipio)
                ->default($defaultEstadoId)
                ->helperText($options['estadoHelper'] ?? ($estadoFijo
                    ? 'Los hogares solidarios operan en el estado Anzoátegui.'
                    : null))
                ->afterStateUpdated(function (Set $set) use ($municipioField, $parroquiaField, $comunaField, $includeComuna): void {
                    $set($municipioField, null);
                    $set($parroquiaField, null);
                    if ($includeComuna) {
                        $set($comunaField, null);
                    }
                }),

            Forms\Components\Select::make($municipioField)
                ->label($labels['municipio'] ?? 'Municipio')
                ->options(fn (Get $get): array => GeografiaSelectOptions::municipios(
                    $get,
                    $estadoField,
                    $estadoFijo ? $defaultEstadoId : null,
                ))
                ->searchable()
                ->required($requireEstadoMunicipio)
                ->live()
                ->dehydrated($dehydrateEstadoMunicipio)
                ->default($defaultMunicipioId)
                ->placeholder(fn (Get $get): string => filled($get($estadoField) ?? ($estadoFijo ? $defaultEstadoId : null))
                    ? 'Seleccione un municipio'
                    : 'Primero seleccione el estado')
                ->afterStateUpdated(function (Set $set) use ($parroquiaField, $comunaField, $includeComuna): void {
                    $set($parroquiaField, null);
                    if ($includeComuna) {
                        $set($comunaField, null);
                    }
                }),

            Forms\Components\Select::make($parroquiaField)
                ->label($labels['parroquia'] ?? 'Parroquia')
                ->options(fn (Get $get): array => GeografiaSelectOptions::parroquias($get, $municipioField))
                ->searchable()
                ->required()
                ->live()
                ->placeholder(fn (Get $get): string => filled($get($municipioField))
                    ? 'Seleccione una parroquia'
                    : 'Primero seleccione el municipio')
                ->afterStateUpdated(fn (Set $set) => $includeComuna ? $set($comunaField, null) : null),
        ];

        if ($includeComuna) {
            $fields[] = Forms\Components\Select::make($comunaField)
                ->label($labels['comuna'] ?? 'Comuna')
                ->options(fn (Get $get): array => GeografiaSelectOptions::comunas($get, $parroquiaField))
                ->searchable()
                ->placeholder('Opcional');
        }

        return $fields;
    }

    /**
     * Procedencia del Invitado (sin comuna, cualquier estado).
     *
     * @return array<int, Forms\Components\Component>
     */
    public static function procedencia(string $prefix = 'procedencia_'): array
    {
        return self::make([
            'prefix' => $prefix,
            'includeComuna' => false,
            'estadoScope' => 'all',
            'persistEstadoMunicipio' => true,
            'labels' => [
                'estado' => 'Estado de procedencia',
                'municipio' => 'Municipio de procedencia',
                'parroquia' => 'Parroquia de procedencia',
            ],
            'estadoHelper' => 'Seleccione el estado de procedencia del Invitado (puede ser cualquier estado de Venezuela).',
        ]);
    }

    /**
     * Ubicación del hogar solidario (Anzoátegui, comuna opcional).
     *
     * @return array<int, Forms\Components\Component>
     */
    public static function hogar(?Model $record = null): array
    {
        return self::make([
            'includeComuna' => true,
            'estadoScope' => 'anzoategui',
            'record' => $record,
            // Incluir estado/municipio en el estado del formulario para validar parroquia,
            // pero no exigirlos al guardar (solo persiste parroquia_id en el hogar).
            'dehydrateEstadoMunicipio' => true,
            'requireEstadoMunicipio' => false,
        ]);
    }

    private static function defaultEstadoId(?Model $record, ?string $estadoScope): ?int
    {
        if ($record?->parroquia?->municipio?->estado_id) {
            return (int) $record->parroquia->municipio->estado_id;
        }

        if ($estadoScope === 'anzoategui') {
            return Estado::query()->where('nombre', 'Anzoátegui')->value('id');
        }

        return null;
    }
}
