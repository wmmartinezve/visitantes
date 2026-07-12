<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\CentroAcopio;
use App\Models\Invitado;
use App\Models\Municipio;
use App\Models\Parroquia;
use App\Models\HogarSolidario;
use App\Support\VisitantesFeatures;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;

class MapaOperacion extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationGroup = 'Operación';

    protected static ?string $navigationLabel = 'Mapa operativo';

    protected static ?string $title = 'Mapa operativo';

    protected static ?int $navigationSort = 15;

    protected static string $view = 'filament.pages.mapa-operacion';

    /** @var array{municipio_id: ?int, parroquia_id: ?int} */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'municipio_id' => null,
            'parroquia_id' => null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('municipio_id')
                    ->label('Municipio')
                    ->placeholder('Todos los municipios')
                    ->options(fn (): array => Municipio::query()->orderBy('nombre')->pluck('nombre', 'id')->all())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (Set $set): void {
                        $set('parroquia_id', null);
                        $this->dispatchMapaRefresh();
                    }),

                Select::make('parroquia_id')
                    ->label('Parroquia')
                    ->placeholder('Todas las parroquias')
                    ->options(function (Get $get): array {
                        $municipioId = $get('municipio_id');
                        if (! $municipioId) {
                            return [];
                        }

                        return Parroquia::query()
                            ->where('municipio_id', $municipioId)
                            ->orderBy('nombre')
                            ->pluck('nombre', 'id')
                            ->all();
                    })
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn () => $this->dispatchMapaRefresh())
                    ->disabled(fn (Get $get): bool => ! $get('municipio_id')),
            ])
            ->columns(2)
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('limpiarFiltros')
                ->label('Limpiar filtros')
                ->icon('heroicon-o-x-mark')
                ->color('gray')
                ->visible(fn (): bool => ($this->data['municipio_id'] ?? null) !== null
                    || ($this->data['parroquia_id'] ?? null) !== null)
                ->action(function (): void {
                    $this->form->fill([
                        'municipio_id' => null,
                        'parroquia_id' => null,
                    ]);

                    $this->dispatchMapaRefresh();
                }),
        ];
    }

    public function updatedData(): void
    {
        $this->dispatchMapaRefresh();
    }

    private function dispatchMapaRefresh(): void
    {
        $this->dispatch('refresh-mapa-operacion', puntos: $this->puntos);
    }

    /**
     * @return array{
     *     refugios: list<array{id: int, nombre: string, lat: float, lng: float, municipio: string, parroquia: string, invitados: int}>,
     *     centros: list<array{id: int, nombre: string, lat: float, lng: float, municipio: string, parroquia: string, activo: bool}>
     * }
     */
    public function getPuntosProperty(): array
    {
        $refugios = $this->refugiosQuery()
            ->with(['parroquia.municipio'])
            ->withCount('invitados')
            ->get()
            ->map(fn (HogarSolidario $refugio): array => [
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

        $centros = VisitantesFeatures::logistica()
            ? $this->centrosQuery()
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
                ->all()
            : [];

        return [
            'refugios' => $refugios,
            'centros' => $centros,
        ];
    }

    /**
     * @return array{hogares_solidarios: int, invitados: int}
     */
    public function getResumenProperty(): array
    {
        return [
            'hogares_solidarios' => $this->refugiosQuery()->count(),
            'invitados' => $this->invitadosQuery()->count(),
        ];
    }

    /** @return Builder<Invitado> */
    private function invitadosQuery(): Builder
    {
        return Invitado::query()->whereHas('hogarSolidario', function (Builder $query): void {
            $this->aplicarFiltroTerritorial($query);
        });
    }

    /** @return Builder<HogarSolidario> */
    private function refugiosQuery(): Builder
    {
        $query = HogarSolidario::query();
        $this->aplicarFiltroTerritorial($query);

        return $query;
    }

    /** @return Builder<CentroAcopio> */
    private function centrosQuery(): Builder
    {
        $query = CentroAcopio::query();
        $this->aplicarFiltroTerritorial($query);

        return $query;
    }

    /** @param  Builder<HogarSolidario>|Builder<CentroAcopio>  $query */
    private function aplicarFiltroTerritorial(Builder $query): void
    {
        $parroquiaId = filled($this->data['parroquia_id'] ?? null)
            ? (int) $this->data['parroquia_id']
            : null;
        $municipioId = filled($this->data['municipio_id'] ?? null)
            ? (int) $this->data['municipio_id']
            : null;

        if ($parroquiaId) {
            $query->where('parroquia_id', $parroquiaId);

            return;
        }

        if ($municipioId) {
            $query->whereHas('parroquia', fn (Builder $q) => $q->where('municipio_id', $municipioId));
        }
    }
}
