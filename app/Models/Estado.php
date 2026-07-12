<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estado extends Model
{
    protected $fillable = [
        'nombre',
        'codigo_ine',
    ];

    public function municipios(): HasMany
    {
        return $this->hasMany(Municipio::class);
    }
}
