<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Municipio extends Model
{
    protected $fillable = [
        'nombre',
    ];

    public function parroquias(): HasMany
    {
        return $this->hasMany(Parroquia::class);
    }
}
