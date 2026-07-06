<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\CentroAcopio;
use App\Models\Refugio;
use Filament\Pages\Page;

class MapaOperacion extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationGroup = 'Operación';

    protected static ?string $navigationLabel = 'Mapa operativo';

    protected static ?string $title = 'Mapa operativo';

    protected static ?int $navigationSort = 15;

    protected static string $view = 'filament.pages.mapa-operacion';

    /**
     * @return array{
     *     refugios: list<array{id: int, nombre: string, lat: float, lng: float, municipio: string, parroquia: string, invitados: int}>,
     *     centros: list<array{id: int, nombre: string, lat: float, lng: float, municipio: string, parroquia: string, activo: bool}>
     * }
     */
    public function getPuntosProperty(): array
    {
        $refugios = Refugio::query()
            ->with(['parroquia.municipio'])
            ->withCount('invitados')
            ->get()
            ->map(fn (Refugio $refugio): array => [
                'id' => $refugio->id,
                'nombre' => $refugio->nombre,
                'lat' => (float) $refugio->latitud,
                'lng' => (float) $refugio->longitud,
                'municipio' => $refugio->parroquia?->municipio?->nombre ?? '—',
                'parroquia' => $refugio->parroquia?->nombre ?? '—',
                'invitados' => $refugio->invitados_count,
            ])
            ->values()
            ->all();

        $centros = CentroAcopio::query()
            ->with(['parroquia.municipio'])
            ->get()
            ->map(fn (CentroAcopio $centro): array => [
                'id' => $centro->id,
                'nombre' => $centro->nombre,
                'lat' => (float) $centro->latitud,
                'lng' => (float) $centro->longitud,
                'municipio' => $centro->parroquia?->municipio?->nombre ?? '—',
                'parroquia' => $centro->parroquia?->nombre ?? '—',
                'activo' => (bool) $centro->activo,
            ])
            ->values()
            ->all();

        return [
            'refugios' => $refugios,
            'centros' => $centros,
        ];
    }
}
