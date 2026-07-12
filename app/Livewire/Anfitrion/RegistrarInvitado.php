<?php

declare(strict_types=1);

namespace App\Livewire\Anfitrion;

use App\Enums\SituacionJefeFamilia;
use App\Enums\TipoAnfitrionHogar;
use App\Enums\TipoViviendaHogar;
use App\Models\Comuna;
use App\Models\Estado;
use App\Models\Invitado;
use App\Models\Municipio;
use App\Models\Parroquia;
use App\Services\NucleoFamiliarOnboardingService;
use App\Support\HogarSolidarioValidationRules;
use App\Support\NucleoFamiliarPorHogar;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.m3.anfitrion-shell')]
class RegistrarInvitado extends Component
{
    use WithFileUploads;

    public int $paso = 0;

    // Hogar solidario (solo si el anfitrión aún no tiene uno)
    public string $hogar_tipo_vivienda = 'casa';

    public string $hogar_tipo_anfitrion = 'familiar';

    public ?string $hogar_parentesco_anfitrion = null;

    public ?int $hogar_municipio_id = null;

    public ?int $hogar_parroquia_id = null;

    public ?int $hogar_comuna_id = null;

    public string $hogar_direccion = '';

    public string $hogar_latitud = '';

    public string $hogar_longitud = '';

    public string $responsable_nombre = '';

    public ?string $responsable_cedula = null;

    public ?string $responsable_telefono = null;

    // Jefe de familia
    public string $nombre = '';

    public string $apellido = '';

    public ?string $cedula = null;

    public ?string $telefono = null;

    public string $fecha_nacimiento = '';

    public ?int $procedencia_estado_id = null;

    public ?int $procedencia_municipio_id = null;

    public ?int $procedencia_parroquia_id = null;

    public ?string $situacion_jefe = null;

    public $foto = null;

    /** @var list<array{nombre: string, apellido: string, cedula: ?string, telefono: ?string, parentesco: string, fecha_nacimiento: string}> */
    public array $familiares = [];

    public function mount(): void
    {
        $this->authorize('create', Invitado::class);
        $this->familiares = [];

        $user = auth()->user();
        $hogarId = $user->hogar_solidario_id;

        if ($hogarId !== null && NucleoFamiliarPorHogar::hogarTieneNucleo($hogarId)) {
            session()->flash('info', 'Este hogar solidario ya tiene un núcleo familiar registrado.');
            $this->redirectRoute('anfitrion.invitados', navigate: true);
        }
    }

    public function getRequiereRegistroHogarProperty(): bool
    {
        return auth()->user()->hogar_solidario_id === null;
    }

    public function getTotalPasosProperty(): int
    {
        return $this->requiereRegistroHogar ? 4 : 3;
    }

    /** @return list<string> */
    public function getTitulosPasosProperty(): array
    {
        if ($this->requiereRegistroHogar) {
            return ['Hogar solidario', 'Jefe de familia', 'Familiares', 'Foto y confirmar'];
        }

        return ['Jefe de familia', 'Familiares', 'Foto y confirmar'];
    }

    public function getPasoLogicoProperty(): int
    {
        return $this->paso;
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

    public function updatedHogarMunicipioId(): void
    {
        $this->hogar_parroquia_id = null;
        $this->hogar_comuna_id = null;
    }

    public function updatedHogarParroquiaId(): void
    {
        $this->hogar_comuna_id = null;
        $this->prefillProcedenciaDesdeHogar(force: true);
    }

    public function updatedHogarTipoAnfitrion(): void
    {
        if ($this->hogar_tipo_anfitrion === 'amigo') {
            $this->hogar_parentesco_anfitrion = null;
        }
    }

    public function updatedProcedenciaEstadoId(): void
    {
        $this->procedencia_municipio_id = null;
        $this->procedencia_parroquia_id = null;
    }

    public function updatedProcedenciaMunicipioId(): void
    {
        $this->procedencia_parroquia_id = null;
    }

    public function anterior(): void
    {
        if ($this->paso > 0) {
            $this->paso--;
        }
    }

    public function siguiente(): void
    {
        $this->validate($this->reglasPasoActual());

        if ($this->requiereRegistroHogar && $this->paso === 0) {
            $this->prefillProcedenciaDesdeHogar();
        }

        if ($this->paso < $this->totalPasos - 1) {
            $this->paso++;
        }
    }

    private function prefillProcedenciaDesdeHogar(bool $force = false): void
    {
        if ($this->hogar_parroquia_id === null) {
            return;
        }

        if (! $force && $this->procedencia_parroquia_id !== null) {
            return;
        }

        $parroquia = Parroquia::query()->with('municipio')->find($this->hogar_parroquia_id);

        if ($parroquia?->municipio === null) {
            return;
        }

        $this->procedencia_estado_id = $parroquia->municipio->estado_id;
        $this->procedencia_municipio_id = $parroquia->municipio_id;
        $this->procedencia_parroquia_id = $parroquia->id;
    }

    /**
     * @return array<string, mixed>
     */
    private function reglasPasoActual(): array
    {
        if ($this->requiereRegistroHogar && $this->paso === 0) {
            return HogarSolidarioValidationRules::forLivewirePasoHogar();
        }

        $pasoJefe = $this->requiereRegistroHogar ? 1 : 0;

        if ($this->paso === $pasoJefe) {
            return [
                'nombre' => ['required', 'string', 'max:255'],
                'apellido' => ['required', 'string', 'max:255'],
                'cedula' => ['nullable', 'string', 'max:20'],
                'telefono' => ['nullable', 'string', 'max:30'],
                'fecha_nacimiento' => ['required', 'date', 'before_or_equal:today'],
                'procedencia_estado_id' => ['required', 'integer', 'exists:estados,id'],
                'procedencia_municipio_id' => ['required', 'integer', 'exists:municipios,id'],
                'procedencia_parroquia_id' => ['required', 'integer', 'exists:parroquias,id'],
                'situacion_jefe' => ['required', 'string', 'in:trabajando,desempleado,pensionado,estudiante,otro'],
            ];
        }

        return [];
    }

    /**
     * @return array<string, mixed>
     */
    private function reglasCompletas(): array
    {
        $rules = [
            'nombre' => ['required', 'string', 'max:255'],
            'apellido' => ['required', 'string', 'max:255'],
            'cedula' => ['nullable', 'string', 'max:20'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'fecha_nacimiento' => ['required', 'date', 'before_or_equal:today'],
            'procedencia_estado_id' => ['required', 'integer', 'exists:estados,id'],
            'procedencia_municipio_id' => ['required', 'integer', 'exists:municipios,id'],
            'procedencia_parroquia_id' => ['required', 'integer', 'exists:parroquias,id'],
            'situacion_jefe' => ['required', 'string', 'in:trabajando,desempleado,pensionado,estudiante,otro'],
            'foto' => ['nullable', 'image', 'max:10240'],
            'familiares' => ['array'],
            'familiares.*.nombre' => ['required_with:familiares.*.apellido', 'string', 'max:255'],
            'familiares.*.apellido' => ['required_with:familiares.*.nombre', 'string', 'max:255'],
            'familiares.*.cedula' => ['nullable', 'string', 'max:20'],
            'familiares.*.telefono' => ['nullable', 'string', 'max:30'],
            'familiares.*.parentesco' => ['required_with:familiares.*.nombre', 'string', 'max:50'],
            'familiares.*.fecha_nacimiento' => ['required_with:familiares.*.nombre', 'date', 'before_or_equal:today'],
        ];

        if ($this->requiereRegistroHogar) {
            $rules = array_merge($rules, HogarSolidarioValidationRules::forLivewirePasoHogar());
        }

        return $rules;
    }

    public function guardar(NucleoFamiliarOnboardingService $onboarding): void
    {
        $this->authorize('create', Invitado::class);

        $validated = $this->validate($this->reglasCompletas());

        $anfitrion = auth()->user();

        $hogarData = null;
        if ($this->requiereRegistroHogar) {
            $hogarData = [
                'tipo_vivienda' => $validated['hogar_tipo_vivienda'],
                'tipo_anfitrion' => $validated['hogar_tipo_anfitrion'],
                'parentesco_anfitrion' => $validated['hogar_parentesco_anfitrion'] ?? null,
                'comuna_id' => $validated['hogar_comuna_id'] ?? null,
                'parroquia_id' => $validated['hogar_parroquia_id'],
                'responsable_nombre' => $validated['responsable_nombre'],
                'responsable_cedula' => $validated['responsable_cedula'] ?? null,
                'responsable_telefono' => $validated['responsable_telefono'] ?? null,
                'habitantes' => [],
                'latitud' => $validated['hogar_latitud'],
                'longitud' => $validated['hogar_longitud'],
                'direccion_exacta' => $validated['hogar_direccion'],
            ];
        }

        $result = $onboarding->register(
            $anfitrion,
            $hogarData,
            [
                'nombre' => $validated['nombre'],
                'apellido' => $validated['apellido'],
                'cedula' => $validated['cedula'] ?? null,
                'telefono' => $validated['telefono'] ?? null,
                'fecha_nacimiento' => $validated['fecha_nacimiento'],
                'procedencia_estado_id' => $validated['procedencia_estado_id'],
                'procedencia_municipio_id' => $validated['procedencia_municipio_id'],
                'procedencia_parroquia_id' => $validated['procedencia_parroquia_id'],
                'situacion_jefe' => $validated['situacion_jefe'],
            ],
            $this->foto,
            $validated['familiares'] ?? [],
        );

        $jefe = $result['jefe'];

        session()->flash(
            'success',
            $result['hogar_creado']
                ? "Hogar {$result['hogar']->codigo} y núcleo familiar registrados correctamente."
                : "Invitado {$jefe->nombreCompleto()} registrado correctamente.",
        );

        $this->redirectRoute('anfitrion.invitado', ['invitado' => $jefe->id], navigate: true);
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.anfitrion.registrar-invitado', [
            'refugio' => $user->refugio,
            'estados' => Estado::query()->orderBy('nombre')->get(['id', 'nombre']),
            'municipios' => Municipio::query()->orderBy('nombre')->get(['id', 'nombre', 'estado_id']),
            'parroquias' => Parroquia::query()->orderBy('nombre')->get(['id', 'nombre', 'municipio_id']),
            'comunas' => Comuna::query()->orderBy('nombre')->get(['id', 'nombre', 'parroquia_id']),
            'tiposVivienda' => TipoViviendaHogar::cases(),
            'tiposAnfitrion' => TipoAnfitrionHogar::cases(),
            'situacionesJefe' => SituacionJefeFamilia::cases(),
            'parroquiasHogar' => $this->hogar_municipio_id
                ? Parroquia::query()->where('municipio_id', $this->hogar_municipio_id)->orderBy('nombre')->get()
                : collect(),
            'comunasHogar' => $this->hogar_parroquia_id
                ? Comuna::query()->where('parroquia_id', $this->hogar_parroquia_id)->orderBy('nombre')->get()
                : collect(),
            'municipiosProcedencia' => $this->procedencia_estado_id
                ? Municipio::query()->where('estado_id', $this->procedencia_estado_id)->orderBy('nombre')->get()
                : collect(),
            'parroquiasProcedencia' => $this->procedencia_municipio_id
                ? Parroquia::query()->where('municipio_id', $this->procedencia_municipio_id)->orderBy('nombre')->get()
                : collect(),
        ]);
    }
}
