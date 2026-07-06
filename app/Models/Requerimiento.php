<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RequerimientoEstatus;
use App\Support\InsumoCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Requerimiento extends Model
{
    protected $fillable = [
        'invitado_id',
        'anfitrion_id',
        'categoria',
        'subcategoria',
        'item_solicitado',
        'cantidad',
        'estatus',
        'centro_acopio_id',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'estatus' => RequerimientoEstatus::class,
        ];
    }

    public function invitado(): BelongsTo
    {
        return $this->belongsTo(Invitado::class);
    }

    public function anfitrion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anfitrion_id');
    }

    public function centroAcopio(): BelongsTo
    {
        return $this->belongsTo(CentroAcopio::class);
    }

    protected static function booted(): void
    {
        static::saving(function (Requerimiento $requerimiento): void {
            if ($requerimiento->categoria && $requerimiento->subcategoria) {
                $requerimiento->item_solicitado = InsumoCatalog::etiqueta(
                    $requerimiento->categoria,
                    $requerimiento->subcategoria,
                );
            }
        });
    }
}
