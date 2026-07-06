<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Parroquia extends Model
{
    protected $fillable = [
        'municipio_id',
        'nombre',
    ];

    public function municipio(): BelongsTo
    {
        return $this->belongsTo(Municipio::class);
    }

    public function refugios(): HasMany
    {
        return $this->hasMany(Refugio::class);
    }

    public function centrosAcopio(): HasMany
    {
        return $this->hasMany(CentroAcopio::class);
    }
}
