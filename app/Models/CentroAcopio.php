<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CentroAcopio extends Model
{
    protected $table = 'centros_acopio';

    protected $fillable = [
        'nombre',
        'parroquia_id',
        'direccion_exacta',
        'latitud',
        'longitud',
        'contacto',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'latitud' => 'decimal:8',
            'longitud' => 'decimal:8',
            'activo' => 'boolean',
        ];
    }

    public function parroquia(): BelongsTo
    {
        return $this->belongsTo(Parroquia::class);
    }

    public function inventarios(): HasMany
    {
        return $this->hasMany(Inventario::class);
    }

    public function requerimientos(): HasMany
    {
        return $this->hasMany(Requerimiento::class);
    }

    public function operadores(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
