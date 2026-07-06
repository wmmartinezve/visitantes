<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InvitadoEstatus;
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
        'nombre',
        'apellido',
        'cedula',
        'fecha_nacimiento',
        'telefono',
        'foto_ingreso',
        'refugio_id',
        'estatus',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'estatus' => InvitadoEstatus::class,
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

    public function refugio(): BelongsTo
    {
        return $this->belongsTo(Refugio::class);
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
}
