<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TipoAnfitrionHogar;
use App\Enums\TipoViviendaHogar;
use App\Support\HogarSolidarioCodigoGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class HogarSolidario extends Model
{
    use SoftDeletes;

    protected $table = 'hogares_solidarios';

    protected $fillable = [
        'codigo',
        'parroquia_id',
        'comuna_id',
        'tipo_vivienda',
        'tipo_anfitrion',
        'parentesco_anfitrion',
        'responsable_nombre',
        'responsable_cedula',
        'responsable_telefono',
        'habitantes',
        'latitud',
        'longitud',
        'direccion_exacta',
    ];

    protected function casts(): array
    {
        return [
            'tipo_vivienda' => TipoViviendaHogar::class,
            'tipo_anfitrion' => TipoAnfitrionHogar::class,
            'habitantes' => 'array',
            'latitud' => 'decimal:8',
            'longitud' => 'decimal:8',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (HogarSolidario $hogar): void {
            if (blank($hogar->codigo) && $hogar->parroquia_id !== null) {
                $hogar->codigo = app(HogarSolidarioCodigoGenerator::class)
                    ->generar((int) $hogar->parroquia_id);
            }
        });
    }

    /** Alias retrocompatible: el código es el identificador visible del hogar. */
    public function getNombreAttribute(): ?string
    {
        return $this->codigo;
    }

    public function parroquia(): BelongsTo
    {
        return $this->belongsTo(Parroquia::class);
    }

    public function comuna(): BelongsTo
    {
        return $this->belongsTo(Comuna::class);
    }

    public function invitados(): HasMany
    {
        return $this->hasMany(Invitado::class);
    }

    /** Jefe del único núcleo familiar hospedado en este hogar (1:1). */
    public function jefeFamilia(): HasOne
    {
        return $this->hasOne(Invitado::class)->whereNull('jefe_familia_id');
    }

    /** Todos los Invitados del núcleo (jefe + familiares). */
    public function nucleoFamiliar(): HasMany
    {
        return $this->invitados();
    }

    public function tieneNucleoFamiliar(): bool
    {
        if ($this->relationLoaded('jefeFamilia')) {
            return $this->jefeFamilia !== null;
        }

        return $this->jefeFamilia()->exists();
    }

    public function anfitriones(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
