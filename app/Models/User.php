<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'refugio_id',
        'centro_acopio_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'rol' => UserRole::class,
        ];
    }

    public function refugio(): BelongsTo
    {
        return $this->belongsTo(Refugio::class);
    }

    public function centroAcopio(): BelongsTo
    {
        return $this->belongsTo(CentroAcopio::class);
    }

    public function requerimientosComoAnfitrion(): HasMany
    {
        return $this->hasMany(Requerimiento::class, 'anfitrion_id');
    }

    public function isAdmin(): bool
    {
        return $this->rol === UserRole::Admin;
    }

    public function isAnfitrion(): bool
    {
        return $this->rol === UserRole::Anfitrion;
    }

    public function isCentroAcopio(): bool
    {
        return $this->rol === UserRole::CentroAcopio;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin();
    }
}
