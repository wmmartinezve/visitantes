<?php

declare(strict_types=1);

namespace App\Enums;

enum ActivityChannel: string
{
    case Admin = 'admin';
    case MobileApi = 'mobile_api';
    case OfflineSync = 'offline_sync';
    case LivewireAnfitrion = 'livewire_anfitrion';
    case LivewireAcopio = 'livewire_acopio';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Panel admin',
            self::MobileApi => 'App móvil',
            self::OfflineSync => 'Sync offline',
            self::LivewireAnfitrion => 'Web anfitrión',
            self::LivewireAcopio => 'Web acopio',
            self::System => 'Sistema',
        };
    }
}
