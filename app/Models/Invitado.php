<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CondicionInvitado;
use App\Enums\InvitadoEstatus;
use App\Enums\SituacionJefeFamilia;
use App\Support\InvitadoFotoStorage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invitado extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'jefe_familia_id',
        'parentesco',
        'condicion',
        'nombre',
        'apellido',
        'cedula',
        'fecha_nacimiento',
        'telefono',
        'foto_ingreso',
        'hogar_solidario_id',
        'procedencia_estado_id',
        'procedencia_municipio_id',
        'procedencia_parroquia_id',
        'situacion_jefe',
        'estatus',
        'menciones_ayudas',
        'menciones_salud',
        'menciones_tramites',
        'menciones_nota',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'estatus' => InvitadoEstatus::class,
            'condicion' => CondicionInvitado::class,
            'situacion_jefe' => SituacionJefeFamilia::class,
            'menciones_ayudas' => 'array',
            'menciones_salud' => 'array',
            'menciones_tramites' => 'array',
        ];
    }

    public function jefeFamilia(): BelongsTo
    {
        return $this->belongsTo(self::class, 'jefe_familia_id');
    }

    public function miembrosFamilia(): HasMany
    {
        return $this->hasMany(self::class, 'jefe_familia_id');
    }

    public function hogarSolidario(): BelongsTo
    {
        return $this->belongsTo(HogarSolidario::class);
    }

    /** @deprecated Use hogarSolidario() */
    public function refugio(): BelongsTo
    {
        return $this->hogarSolidario();
    }

    public function procedenciaEstado(): BelongsTo
    {
        return $this->belongsTo(Estado::class, 'procedencia_estado_id');
    }

    public function procedenciaMunicipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class, 'procedencia_municipio_id');
    }

    public function procedenciaParroquia(): BelongsTo
    {
        return $this->belongsTo(Parroquia::class, 'procedencia_parroquia_id');
    }

    public function requerimientos(): HasMany
    {
        return $this->hasMany(Requerimiento::class);
    }

    public function esJefeDeFamilia(): bool
    {
        return $this->jefe_familia_id === null;
    }

    public function nombreCompleto(): string
    {
        return trim("{$this->nombre} {$this->apellido}");
    }

    public function invitadoConFoto(): ?self
    {
        if (! blank($this->foto_ingreso)) {
            return $this;
        }

        if ($this->jefe_familia_id === null) {
            return null;
        }

        $jefe = $this->relationLoaded('jefeFamilia')
            ? $this->jefeFamilia
            : $this->jefeFamilia()->first();

        if ($jefe === null || blank($jefe->foto_ingreso)) {
            return null;
        }

        return $jefe;
    }

    public function fotoUrl(string $routeName = 'invitados.foto'): ?string
    {
        $invitadoConFoto = $this->invitadoConFoto();

        return $invitadoConFoto !== null
            ? route($routeName, $invitadoConFoto)
            : null;
    }

    public function fotoDisplayUrl(string $routeName = 'invitados.foto'): ?string
    {
        $invitadoConFoto = $this->invitadoConFoto();

        if ($invitadoConFoto === null || blank($invitadoConFoto->foto_ingreso)) {
            return null;
        }

        return InvitadoFotoStorage::displayUrl(
            $invitadoConFoto->foto_ingreso,
            $invitadoConFoto,
            $routeName,
        );
    }
}
