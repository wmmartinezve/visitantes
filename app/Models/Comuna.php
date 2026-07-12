<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comuna extends Model
{
    protected $fillable = [
        'parroquia_id',
        'nombre',
    ];

    public function parroquia(): BelongsTo
    {
        return $this->belongsTo(Parroquia::class);
    }

    public function hogaresSolidarios(): HasMany
    {
        return $this->hasMany(HogarSolidario::class);
    }
}
