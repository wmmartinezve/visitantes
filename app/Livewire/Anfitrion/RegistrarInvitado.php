<?php

declare(strict_types=1);

namespace App\Livewire\Anfitrion;

use App\Models\Invitado;
use App\Services\InvitadoRegistrationService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.m3.anfitrion-shell')]
class RegistrarInvitado extends Component
{
    use WithFileUploads;

    public string $nombre = '';

    public string $apellido = '';

    public ?string $cedula = null;

    public ?string $telefono = null;

    public string $fecha_nacimiento = '';

    public $foto = null;

    /** @var list<array{nombre: string, apellido: string, cedula: ?string, telefono: ?string, fecha_nacimiento: string}> */
    public array $familiares = [];

    public function mount(): void
    {
        $this->authorize('create', Invitado::class);
        $this->familiares = [];
    }

    public function agregarFamiliar(): void
    {
        $this->familiares[] = [
            'nombre' => '',
            'apellido' => '',
            'parentesco' => '',
            'cedula' => null,
            'telefono' => null,
            'fecha_nacimiento' => '',
        ];
    }

    public function quitarFamiliar(int $index): void
    {
        unset($this->familiares[$index]);
        $this->familiares = array_values($this->familiares);
    }

    public function quitarFoto(): void
    {
        $this->foto = null;
    }

    public function guardar(InvitadoRegistrationService $service): void
    {
        $this->authorize('create', Invitado::class);

        $rules = [
            'nombre' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'cedula' => ['nullable', 'string', 'max:20'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'fecha_nacimiento' => ['required', 'date', 'before_or_equal:today'],
            'foto' => ['nullable', 'image', 'max:10240'],
            'familiares' => ['array'],
            'familiares.*.nombre' => ['required_with:familiares.*.apellido', 'string', 'max:255'],
            'familiares.*.apellido' => ['required_with:familiares.*.nombre', 'string', 'max:255'],
            'familiares.*.cedula' => ['nullable', 'string', 'max:20'],
            'familiares.*.telefono' => ['nullable', 'string', 'max:30'],
            'familiares.*.parentesco' => ['required_with:familiares.*.nombre', 'string', 'max:50'],
            'familiares.*.fecha_nacimiento' => ['required_with:familiares.*.nombre', 'date', 'before_or_equal:today'],
        ];

        $validated = $this->validate($rules);

        $anfitrion = auth()->user();

        $jefe = $service->register(
            $anfitrion,
            [
                'nombre' => $validated['nombre'],
                'apellido' => $validated['apellido'],
                'cedula' => $validated['cedula'] ?? null,
                'telefono' => $validated['telefono'] ?? null,
                'fecha_nacimiento' => $validated['fecha_nacimiento'],
            ],
            $this->foto,
            $validated['familiares'] ?? [],
        );

        session()->flash('success', "Invitado {$jefe->nombreCompleto()} registrado correctamente.");

        $this->redirectRoute('anfitrion.invitado', ['invitado' => $jefe->id], navigate: true);
    }

    public function render()
    {
        return view('livewire.anfitrion.registrar-invitado', [
            'refugio' => auth()->user()->refugio,
        ]);
    }
}
