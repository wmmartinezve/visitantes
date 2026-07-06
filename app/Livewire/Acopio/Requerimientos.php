<?php

declare(strict_types=1);

namespace App\Livewire\Acopio;

use App\Enums\RequerimientoEstatus;
use App\Models\Requerimiento;
use App\Services\RequerimientoAsignacionService;
use App\Support\GeoDistance;
use App\Support\GeoNavigation;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.m3.acopio-shell')]
class Requerimientos extends Component
{
    public ?string $mensaje = null;

    public ?string $error = null;

    public bool $alertaNuevaEntrega = false;

    public int $conteoAsignados = 0;

    public function mount(): void
    {
        $this->conteoAsignados = $this->contarAsignados();
    }

    public function marcarEntregado(int $id, RequerimientoAsignacionService $service): void
    {
        $this->reset(['mensaje', 'error', 'alertaNuevaEntrega']);

        $requerimiento = Requerimiento::query()
            ->where('centro_acopio_id', auth()->user()->centro_acopio_id)
            ->where('estatus', RequerimientoEstatus::Asignado)
            ->whereKey($id)
            ->firstOrFail();

        try {
            $this->authorize('entregar', $requerimiento);
            $service->marcarEntregado($requerimiento);
            $this->mensaje = 'Entrega registrada correctamente.';
            $this->conteoAsignados = $this->contarAsignados();
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
        }
    }

    public function render()
    {
        $this->authorize('viewAny', Requerimiento::class);

        $asignados = Requerimiento::query()
            ->with(['invitado.refugio', 'anfitrion'])
            ->where('centro_acopio_id', auth()->user()->centro_acopio_id)
            ->where('estatus', RequerimientoEstatus::Asignado)
            ->latest()
            ->get();

        $centro = auth()->user()->centroAcopio;

        $asignadosConRuta = $asignados
            ->map(function (Requerimiento $req) use ($centro): array {
                $refugio = $req->invitado?->refugio;
                $distancia = null;
                $rutaUrl = null;
                $refugioUrl = null;

                if ($centro !== null && $refugio !== null) {
                    $distancia = GeoDistance::kilometers(
                        (float) $centro->latitud,
                        (float) $centro->longitud,
                        (float) $refugio->latitud,
                        (float) $refugio->longitud,
                    );

                    $rutaUrl = GeoNavigation::directionsUrl(
                        (float) $centro->latitud,
                        (float) $centro->longitud,
                        (float) $refugio->latitud,
                        (float) $refugio->longitud,
                    );

                    $refugioUrl = GeoNavigation::mapsQueryUrl(
                        (float) $refugio->latitud,
                        (float) $refugio->longitud,
                    );
                }

                return [
                    'requerimiento' => $req,
                    'distancia_km' => $distancia,
                    'ruta_url' => $rutaUrl,
                    'refugio_url' => $refugioUrl,
                    'direccion' => $refugio?->direccion_exacta,
                ];
            })
            ->sortBy(fn (array $row) => $row['distancia_km'] ?? PHP_FLOAT_MAX)
            ->values();

        if ($this->conteoAsignados > 0 && $asignados->count() > $this->conteoAsignados) {
            $this->alertaNuevaEntrega = true;
        }

        $this->conteoAsignados = $asignados->count();

        return view('livewire.acopio.requerimientos', [
            'asignadosConRuta' => $asignadosConRuta,
            'entregados' => Requerimiento::query()
                ->with(['invitado'])
                ->where('centro_acopio_id', auth()->user()->centro_acopio_id)
                ->where('estatus', RequerimientoEstatus::Entregado)
                ->latest()
                ->limit(10)
                ->get(),
        ]);
    }

    private function contarAsignados(): int
    {
        return Requerimiento::query()
            ->where('centro_acopio_id', auth()->user()->centro_acopio_id)
            ->where('estatus', RequerimientoEstatus::Asignado)
            ->count();
    }
}
