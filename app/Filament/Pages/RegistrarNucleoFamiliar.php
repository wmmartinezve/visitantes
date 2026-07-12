<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Enums\SituacionJefeFamilia;
use App\Enums\TipoAnfitrionHogar;
use App\Enums\TipoViviendaHogar;
use App\Enums\UserRole;
use App\Filament\Support\CondicionInvitadoSelectFields;
use App\Filament\Support\GeografiaSelectFields;
use App\Filament\Support\GeolocalizacionFields;
use App\Filament\Support\HogarAnfitrionFields;
use App\Models\User;
use App\Models\Estado;
use App\Services\NucleoFamiliarOnboardingService;
use App\Support\InvitadoFotoStorage;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class RegistrarNucleoFamiliar extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    protected static ?string $navigationGroup = 'Operación';

    protected static ?string $navigationLabel = 'Registrar núcleo familiar';

    protected static ?string $title = 'Registrar núcleo familiar';

    protected static ?string $slug = 'registrar-nucleo-familiar';

    protected static ?int $navigationSort = 11;

    protected static string $view = 'filament.pages.registrar-nucleo-familiar';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'tipo_vivienda' => TipoViviendaHogar::Casa->value,
            'tipo_anfitrion' => TipoAnfitrionHogar::Familiar->value,
            'familiares' => [],
            'estado_id' => Estado::query()->where('nombre', 'Anzoátegui')->value('id'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Step::make('Hogar solidario')
                        ->description('Ubicación y datos del hogar. El código se asigna automáticamente (municipio · parroquia · correlativo).')
                        ->schema([
                            Select::make('tipo_vivienda')
                                ->label('Tipo de vivienda')
                                ->options(collect(TipoViviendaHogar::cases())->mapWithKeys(
                                    fn (TipoViviendaHogar $tipo): array => [$tipo->value => $tipo->label()]
                                ))
                                ->required()
                                ->default(TipoViviendaHogar::Casa->value),
                            ...HogarAnfitrionFields::make(),
                            ...GeografiaSelectFields::hogar(),
                            ...GeolocalizacionFields::make(),
                        ])
                        ->columns(2),

                    Step::make('Responsable del hogar')
                        ->description('Persona del hogar anfitrion que recibe al Invitado (puede ser distinta del jefe de familia hospedado).')
                        ->schema([
                            TextInput::make('responsable_nombre')
                                ->label('Responsable del hogar')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('responsable_cedula')
                                ->label('Cédula del responsable')
                                ->maxLength(20),
                            TextInput::make('responsable_telefono')
                                ->label('Teléfono del responsable')
                                ->tel()
                                ->maxLength(30),
                            Select::make('anfitrion_id')
                                ->label('Anfitrión (opcional)')
                                ->options(fn (): array => User::query()
                                    ->where('rol', UserRole::Anfitrion)
                                    ->whereNull('hogar_solidario_id')
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all())
                                ->searchable()
                                ->helperText('Si selecciona un anfitrión sin hogar, quedará vinculado automáticamente.')
                                ->columnSpanFull(),
                        ])
                        ->columns(2)
                        ->afterValidation(fn (): mixed => $this->resetProcedenciaJefe()),

                    Step::make('Jefe de familia (Invitado)')
                        ->description('Datos del jefe del núcleo familiar hospedado.')
                        ->schema([
                            TextInput::make('jefe_nombre')
                                ->label('Nombre')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('jefe_apellido')
                                ->label('Apellido')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('jefe_cedula')
                                ->label('Cédula')
                                ->maxLength(20),
                            TextInput::make('jefe_telefono')
                                ->label('Teléfono')
                                ->tel()
                                ->maxLength(30),
                            DatePicker::make('jefe_fecha_nacimiento')
                                ->label('Fecha de nacimiento')
                                ->required()
                                ->maxDate(now())
                                ->native(false),
                            ...GeografiaSelectFields::procedencia('jefe_procedencia_'),
                            Select::make('jefe_situacion')
                                ->label('Situación laboral del jefe')
                                ->options(collect(SituacionJefeFamilia::cases())->mapWithKeys(
                                    fn (SituacionJefeFamilia $s): array => [$s->value => $s->label()]
                                ))
                                ->required(),
                            CondicionInvitadoSelectFields::make('jefe_condicion'),
                            FileUpload::make('foto_reemplazo')
                                ->label('Foto testigo de ingreso')
                                ->disk(InvitadoFotoStorage::privateDisk())
                                ->directory('invitados/tmp')
                                ->fetchFileInformation(false)
                                ->image()
                                ->maxSize(8192)
                                ->columnSpanFull(),
                        ])
                        ->columns(2),

                    Step::make('Núcleo familiar')
                        ->description('Familiares que integran el núcleo hospedado (opcional).')
                        ->schema([
                            Repeater::make('familiares')
                                ->label('Familiares')
                                ->schema([
                                    Select::make('parentesco')
                                        ->label('Parentesco')
                                        ->options(collect(config('visitantes.parentescos'))->mapWithKeys(
                                            fn (string $parentesco): array => [$parentesco => $parentesco]
                                        ))
                                        ->searchable()
                                        ->required(),
                                    CondicionInvitadoSelectFields::make('condicion'),
                                    TextInput::make('nombre')
                                        ->label('Nombre')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('apellido')
                                        ->label('Apellido')
                                        ->required()
                                        ->maxLength(255),
                                    TextInput::make('cedula')
                                        ->label('Cédula')
                                        ->maxLength(20),
                                    TextInput::make('telefono')
                                        ->label('Teléfono')
                                        ->maxLength(30),
                                    DatePicker::make('fecha_nacimiento')
                                        ->label('Fecha de nacimiento')
                                        ->required()
                                        ->maxDate(now())
                                        ->native(false),
                                ])
                                ->columns(2)
                                ->columnSpanFull()
                                ->addActionLabel('Agregar familiar'),
                        ]),
                ])
                    ->submitAction(
                        Action::make('submitRegistration')
                            ->label('Registrar y finalizar')
                            ->icon('heroicon-o-check')
                            ->submit('form'),
                    )
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function create(): void
    {
        try {
            $data = $this->form->getState();
        } catch (\Illuminate\Validation\ValidationException $exception) {
            Notification::make()
                ->title('Revise los datos del formulario')
                ->body('Complete los campos obligatorios de todos los pasos antes de finalizar.')
                ->danger()
                ->send();

            throw $exception;
        }

        if (blank($data['parroquia_id'] ?? null)) {
            Notification::make()
                ->title('Falta la parroquia del hogar')
                ->body('Regrese al paso «Hogar solidario» y seleccione municipio y parroquia.')
                ->danger()
                ->send();

            return;
        }

        $hogarData = [
            'tipo_vivienda' => $data['tipo_vivienda'],
            'tipo_anfitrion' => $data['tipo_anfitrion'],
            'parentesco_anfitrion' => $data['parentesco_anfitrion'] ?? null,
            'comuna_id' => $data['comuna_id'],
            'parroquia_id' => $data['parroquia_id'],
            'responsable_nombre' => $data['responsable_nombre'],
            'responsable_cedula' => $data['responsable_cedula'] ?? null,
            'responsable_telefono' => $data['responsable_telefono'] ?? null,
            'habitantes' => [],
            'latitud' => $data['latitud'],
            'longitud' => $data['longitud'],
            'direccion_exacta' => $data['direccion_exacta'],
        ];

        $jefeData = [
            'nombre' => $data['jefe_nombre'],
            'apellido' => $data['jefe_apellido'],
            'cedula' => $data['jefe_cedula'] ?? null,
            'telefono' => $data['jefe_telefono'] ?? null,
            'fecha_nacimiento' => $data['jefe_fecha_nacimiento'],
            'procedencia_estado_id' => $data['jefe_procedencia_estado_id'] ?? null,
            'procedencia_municipio_id' => $data['jefe_procedencia_municipio_id'] ?? null,
            'procedencia_parroquia_id' => $data['jefe_procedencia_parroquia_id'] ?? null,
            'situacion_jefe' => $data['jefe_situacion'],
            'condicion' => $data['jefe_condicion'] ?? 'ninguna',
        ];

        $foto = $this->resolveUploadedFoto($data['foto_reemplazo'] ?? null);

        $result = app(NucleoFamiliarOnboardingService::class)->registerFromAdmin(
            $hogarData,
            $jefeData,
            $foto,
            $data['familiares'] ?? [],
            isset($data['anfitrion_id']) ? (int) $data['anfitrion_id'] : null,
        );

        Notification::make()
            ->title('Núcleo familiar registrado')
            ->body("Hogar {$result['hogar']->codigo} y jefe {$result['jefe']->nombreCompleto()} creados.")
            ->success()
            ->send();

        $this->redirect(\App\Filament\Resources\InvitadoResource::getUrl('edit', ['record' => $result['jefe']]));
    }

    /** Limpia procedencia incompleta antes del paso del jefe (evita municipio/parroquia huérfanos). */
    private function resetProcedenciaJefe(): void
    {
        if (filled($this->data['jefe_procedencia_estado_id'] ?? null)) {
            return;
        }

        $this->data['jefe_procedencia_estado_id'] = null;
        $this->data['jefe_procedencia_municipio_id'] = null;
        $this->data['jefe_procedencia_parroquia_id'] = null;
    }

    private function resolveUploadedFoto(mixed $uploaded): ?\Illuminate\Http\UploadedFile
    {
        if (blank($uploaded)) {
            return null;
        }

        $path = is_array($uploaded) ? ($uploaded[0] ?? null) : $uploaded;

        if (! is_string($path) || $path === '') {
            return null;
        }

        $disk = InvitadoFotoStorage::privateDisk();

        if (! Storage::disk($disk)->exists($path)) {
            return null;
        }

        $absolute = Storage::disk($disk)->path($path);

        return new \Illuminate\Http\UploadedFile(
            $absolute,
            basename($absolute),
            mime_content_type($absolute) ?: 'image/jpeg',
            null,
            true,
        );
    }
}
