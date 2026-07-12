<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Concerns\HidesWhenLogisticaDisabled;
use App\Enums\RequerimientoEstatus;
use App\Models\Requerimiento;
use App\Services\RequerimientoAsignacionService;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class BusquedaCruzadaInsumos extends Page implements HasForms
{
    use HidesWhenLogisticaDisabled;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass-circle';

    protected static ?string $navigationGroup = 'Logística';

    protected static ?string $navigationLabel = 'Búsqueda cruzada';

    protected static ?string $title = 'Búsqueda cruzada de insumos';

    protected static ?int $navigationSort = 25;

    protected static string $view = 'filament.pages.busqueda-cruzada-insumos';

    /** @var array{requerimiento_id: ?int} */
    public ?array $data = [];

    /** @var Collection<int, array{centro: \App\Models\CentroAcopio, inventario: \App\Models\Inventario, distancia_km: ?float, cantidad: int}> */
    public Collection $resultados;

    public function mount(): void
    {
        $this->resultados = collect();
        $this->form->fill(['requerimiento_id' => null]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('requerimiento_id')
                    ->label('Requerimiento pendiente')
                    ->placeholder('Selecciona un requerimiento…')
                    ->options(fn (): array => Requerimiento::query()
                        ->with(['invitado.refugio'])
                        ->where('estatus', RequerimientoEstatus::Pendiente)
                        ->latest()
                        ->get()
                        ->mapWithKeys(function (Requerimiento $r): array {
                            $invitado = $r->invitado?->nombreCompleto() ?? 'Invitado';
                            $refugio = $r->invitado?->refugio?->nombre ?? '—';

                            return [
                                $r->id => "{$r->item_solicitado} (×{$r->cantidad}) — {$invitado} · {$refugio}",
                            ];
                        })
                        ->all())
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn () => $this->buscar()),
            ])
            ->statePath('data');
    }

    public function buscar(): void
    {
        $requerimientoId = $this->data['requerimiento_id'] ?? null;

        if ($requerimientoId === null) {
            $this->resultados = collect();

            return;
        }

        $requerimiento = Requerimiento::query()
            ->with('invitado.refugio')
            ->where('estatus', RequerimientoEstatus::Pendiente)
            ->find($requerimientoId);

        if ($requerimiento === null) {
            $this->resultados = collect();

            return;
        }

        $this->resultados = app(RequerimientoAsignacionService::class)
            ->buscarCentrosConStock($requerimiento);
    }

    public function asignar(int $centroAcopioId): void
    {
        $requerimientoId = $this->data['requerimiento_id'] ?? null;

        if ($requerimientoId === null) {
            return;
        }

        $requerimiento = Requerimiento::query()
            ->where('estatus', RequerimientoEstatus::Pendiente)
            ->findOrFail($requerimientoId);

        try {
            $this->authorize('assign', $requerimiento);
            app(RequerimientoAsignacionService::class)->asignar($requerimiento, $centroAcopioId);

            Notification::make()
                ->success()
                ->title('Requerimiento asignado')
                ->body('El centro de acopio puede procesar la entrega desde su app.')
                ->send();

            $this->form->fill(['requerimiento_id' => null]);
            $this->resultados = collect();
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title('No se pudo asignar')
                ->body($e->getMessage())
                ->send();
        }
    }
}
