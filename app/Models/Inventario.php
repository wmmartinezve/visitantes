<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\InsumoCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventario extends Model
{
    protected $fillable = [
        'centro_acopio_id',
        'categoria',
        'subcategoria',
        'item_nombre',
        'cantidad',
        'unidad_medida',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
        ];
    }

    public function centroAcopio(): BelongsTo
    {
        return $this->belongsTo(CentroAcopio::class);
    }

    protected static function booted(): void
    {
        static::saving(function (Inventario $inventario): void {
            if ($inventario->categoria && $inventario->subcategoria) {
                $inventario->item_nombre = InsumoCatalog::etiqueta(
                    $inventario->categoria,
                    $inventario->subcategoria,
                );
            }
        });
    }
}
