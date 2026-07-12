<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\RequerimientoEstatus;
use App\Models\HogarSolidario;
use App\Services\RequerimientoAsignacionService;
use App\Services\RequerimientoConsolidacionService;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class DemandaConsolidadaRefugio extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Logística';

    protected static ?string $navigationLabel = 'Demanda por refugio';

    protected static ?string $title = 'Demanda consolidada por refugio';

    protected static ?int $navigationSort = 22;

    protected static string $view = 'filament.pages.demanda-consolidada-refugio';

    /** @var array{hogar_solidario_id: ?int, estatus: string} */
    public ?array $filtros = [];

    /** @var Collection<int, array<string, mixed>> */
    public Collection $demanda;

    public ?string $grupoSeleccionado = null;

    /** @var Collection<int, array{centro: \App\Models\CentroAcopio, inventario: \App\Models\Inventario, distancia_km: ?float, cantidad: int}> */
    public Collection $centrosDisponibles;

    /** @var array<string, mixed>|null */
    public ?array $grupoActivo = null;

    public function mount(): void
    {
        $this->demanda = collect();
        $this->centrosDisponibles = collect();
        $this->form->fill([
            'hogar_solidario_id' => null,
            'estatus' => RequerimientoEstatus::Pendiente->value,
        ]);
        $this->cargarDemanda();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('hogar_solidario_id')
                    ->label('HogarSolidario')
                    ->placeholder('Todos los refugios')
                    ->options(fn (): array => HogarSolidario::query()
                        ->orderBy('codigo')
                        ->pluck('codigo', 'id')
                        ->all())
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn () => $this->aplicarFiltros()),
                Select::make('estatus')
                    ->label('Estatus')
                    ->options(collect(RequerimientoEstatus::cases())->mapWithKeys(
                        fn (RequerimientoEstatus $estatus): array => [$estatus->value => $estatus->label()],
                    ))
                    ->default(RequerimientoEstatus::Pendiente->value)
                    ->live()
                    ->afterStateUpdated(fn () => $this->aplicarFiltros()),
            ])
            ->columns(2)
            ->statePath('filtros');
    }

    public function aplicarFiltros(): void
    {
        $this->grupoSeleccionado = null;
        $this->grupoActivo = null;
        $this->centrosDisponibles = collect();
        $this->cargarDemanda();
    }

    public function cargarDemanda(): void
    {
        $estatus = RequerimientoEstatus::from($this->filtros['estatus'] ?? RequerimientoEstatus::Pendiente->value);
        $refugioId = $this->filtros['hogar_solidario_id'] ?? null;

        $this->demanda = app(RequerimientoConsolidacionService::class)->demandaPorRefugio(
            $estatus,
            $refugioId !== null ? (int) $refugioId : null,
        );
    }

    public function seleccionarGrupo(string $grupoKey): void
    {
        $this->grupoSeleccionado = $grupoKey;
        $this->grupoActivo = $this->demanda->first(
            fn (array $fila): bool => app(RequerimientoConsolidacionService::class)->grupoKey(
                $fila['hogar_solidario_id'],
                $fila['categoria'],
                $fila['subcategoria'],
            ) === $grupoKey,
        );

        if ($this->grupoActivo === null) {
            $this->centrosDisponibles = collect();

            return;
        }

        $refugio = HogarSolidario::query()->findOrFail($this->grupoActivo['hogar_solidario_id']);

        $this->centrosDisponibles = app(RequerimientoAsignacionService::class)->buscarCentrosConStockConsolidado(
            $refugio,
            $this->grupoActivo['categoria'],
            $this->grupoActivo['subcategoria'],
            $this->grupoActivo['item_solicitado'],
            (int) $this->grupoActivo['cantidad_total'],
        );
    }

    public function asignarLote(int $centroAcopioId): void
    {
        if ($this->grupoActivo === null) {
            return;
        }

        $estatus = RequerimientoEstatus::from($this->filtros['estatus'] ?? RequerimientoEstatus::Pendiente->value);

        if ($estatus !== RequerimientoEstatus::Pendiente) {
            Notification::make()
                ->warning()
                ->title('Solo pendientes')
                ->body('La asignación en lote aplica a requerimientos pendientes.')
                ->send();

            return;
        }

        $requerimientos = app(RequerimientoConsolidacionService::class)->requerimientosDelGrupo(
            $this->grupoActivo['requerimiento_ids'],
            $estatus,
        );

        foreach ($requerimientos as $requerimiento) {
            $this->authorize('assign', $requerimiento);
        }

        try {
            $asignados = app(RequerimientoAsignacionService::class)->asignarLote($requerimientos, $centroAcopioId);

            Notification::make()
                ->success()
                ->title('Envío consolidado asignado')
                ->body("Se asignaron {$asignados} requerimiento(s) al centro de acopio para entrega al refugio.")
                ->send();

            $this->grupoSeleccionado = null;
            $this->grupoActivo = null;
            $this->centrosDisponibles = collect();
            $this->cargarDemanda();
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title('No se pudo asignar el lote')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function grupoKey(array $fila): string
    {
        return app(RequerimientoConsolidacionService::class)->grupoKey(
            $fila['hogar_solidario_id'],
            $fila['categoria'],
            $fila['subcategoria'],
        );
    }
}
