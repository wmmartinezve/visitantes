<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\CentroAcopio;
use App\Models\Municipio;
use App\Models\Parroquia;
use App\Models\HogarSolidario;
use Carbon\CarbonImmutable;

final readonly class OperacionFiltros
{
    public function __construct(
        public CarbonImmutable $desde,
        public CarbonImmutable $hasta,
        public ?int $municipioId = null,
        public ?int $parroquiaId = null,
        public ?int $refugioId = null,
        public ?int $centroAcopioId = null,
    ) {}

    /**
     * @param  array<string, mixed>|null  $raw
     */
    public static function fromArray(?array $raw): self
    {
        $hasta = self::parseDate($raw['hasta'] ?? null) ?? CarbonImmutable::today();
        $desde = self::parseDate($raw['desde'] ?? null) ?? $hasta->subDays(30);

        if ($desde->greaterThan($hasta)) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        return new self(
            desde: $desde->startOfDay(),
            hasta: $hasta->endOfDay(),
            municipioId: self::parseInt($raw['municipio_id'] ?? null),
            parroquiaId: self::parseInt($raw['parroquia_id'] ?? null),
            refugioId: self::parseInt($raw['hogar_solidario_id'] ?? null),
            centroAcopioId: self::parseInt($raw['centro_acopio_id'] ?? null),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'desde' => $this->desde->toDateString(),
            'hasta' => $this->hasta->toDateString(),
            'municipio_id' => $this->municipioId,
            'parroquia_id' => $this->parroquiaId,
            'hogar_solidario_id' => $this->refugioId,
            'centro_acopio_id' => $this->centroAcopioId,
        ];
    }

    /**
     * @return list<string>
     */
    public function descripcionEtiquetas(): array
    {
        $etiquetas = [
            'Período: '.$this->desde->format('d/m/Y').' — '.$this->hasta->format('d/m/Y'),
        ];

        if ($this->municipioId) {
            $etiquetas[] = 'Municipio: '.(Municipio::query()->find($this->municipioId)?->nombre ?? '—');
        }

        if ($this->parroquiaId) {
            $etiquetas[] = 'Parroquia: '.(Parroquia::query()->find($this->parroquiaId)?->nombre ?? '—');
        }

        if ($this->refugioId) {
            $etiquetas[] = 'HogarSolidario: '.(HogarSolidario::query()->find($this->refugioId)?->codigo ?? '—');
        }

        if ($this->centroAcopioId) {
            $etiquetas[] = 'Centro de acopio: '.(CentroAcopio::query()->find($this->centroAcopioId)?->nombre ?? '—');
        }

        if (count($etiquetas) === 1) {
            $etiquetas[] = 'Ámbito: todo '.config('visitantes.estado');
        }

        return $etiquetas;
    }

    public function tieneFiltroGeografico(): bool
    {
        return $this->municipioId !== null
            || $this->parroquiaId !== null
            || $this->refugioId !== null;
    }

    private static function parseDate(mixed $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        return CarbonImmutable::parse($value);
    }

    private static function parseInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
