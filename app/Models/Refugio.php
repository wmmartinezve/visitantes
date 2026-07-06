<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Refugio extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nombre',
        'parroquia_id',
        'latitud',
        'longitud',
        'direccion_exacta',
    ];

    protected function casts(): array
    {
        return [
            'latitud' => 'decimal:8',
            'longitud' => 'decimal:8',
        ];
    }

    public function parroquia(): BelongsTo
    {
        return $this->belongsTo(Parroquia::class);
    }

    public function invitados(): HasMany
    {
        return $this->hasMany(Invitado::class);
    }

    public function anfitriones(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
