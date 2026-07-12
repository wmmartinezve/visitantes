<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\CentroAcopio;
use App\Models\Municipio;
use App\Models\Parroquia;
use App\Models\HogarSolidario;
use App\Support\OperacionFiltros;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function mount(): void
    {
        if ($this->filters === null) {
            $this->filters = OperacionFiltros::fromArray(null)->toArray();
        }

        $this->mountHasFilters();
    }

    public function getHeading(): string
    {
        return 'Operación — '.config('visitantes.estado');
    }

    public function getSubheading(): ?string
    {
        return 'Indicadores, filtros y reportes de Invitados, refugios y logística en '
            .config('visitantes.estado').', '.config('visitantes.pais').'.';
    }

    public function getColumns(): int|string|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 3,
        ];
    }

    public function filtersForm(Form $form): Form
    {
        return $form->schema([
            Section::make('Filtros de operación')
                ->description('Los indicadores y el reporte PDF responden a estos criterios.')
                ->schema([
                    DatePicker::make('desde')
                        ->label('Desde')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->closeOnDateSelection()
                        ->maxDate(fn (Get $get): ?string => $get('hasta'))
                        ->default(now()->subDays(30)->toDateString()),

                    DatePicker::make('hasta')
                        ->label('Hasta')
                        ->native(false)
                        ->displayFormat('d/m/Y')
                        ->closeOnDateSelection()
                        ->minDate(fn (Get $get): ?string => $get('desde'))
                        ->default(now()->toDateString()),

                    Select::make('municipio_id')
                        ->label('Municipio')
                        ->options(fn (): array => Municipio::query()->orderBy('nombre')->pluck('nombre', 'id')->all())
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function (Set $set): void {
                            $set('parroquia_id', null);
                            $set('hogar_solidario_id', null);
                        }),

                    Select::make('parroquia_id')
                        ->label('Parroquia')
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
                        ->afterStateUpdated(fn (Set $set) => $set('hogar_solidario_id', null)),

                    Select::make('hogar_solidario_id')
                        ->label('HogarSolidario')
                        ->options(function (Get $get): array {
                            $parroquiaId = $get('parroquia_id');
                            if (! $parroquiaId) {
                                return [];
                            }

                            return HogarSolidario::query()
                                ->where('parroquia_id', $parroquiaId)
                                ->orderBy('nombre')
                                ->pluck('nombre', 'id')
                                ->all();
                        })
                        ->searchable(),

                    Select::make('centro_acopio_id')
                        ->label('Centro de acopio')
                        ->options(fn (): array => CentroAcopio::query()->orderBy('nombre')->pluck('nombre', 'id')->all())
                        ->searchable()
                        ->preload(),
                ])
                ->columns([
                    'default' => 1,
                    'md' => 2,
                    'xl' => 3,
                ])
                ->collapsible(),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPdf')
                ->label('Exportar PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->url(function (): string {
                    $filtros = OperacionFiltros::fromArray($this->filters);

                    return route('filament.admin.dashboard.export-pdf', array_filter(
                        $filtros->toArray(),
                        fn (mixed $value): bool => $value !== null && $value !== '',
                    ));
                }),
        ];
    }
}
