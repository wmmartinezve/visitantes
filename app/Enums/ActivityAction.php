<?php

declare(strict_types=1);

namespace App\Enums;

enum ActivityAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case ForceDeleted = 'force_deleted';
    case Restored = 'restored';
    case FotoAttached = 'foto_attached';
    case Asignado = 'asignado';
    case Entregado = 'entregado';
    case StockDecremented = 'stock_decremented';
    case PasswordChanged = 'password_changed';
    case ProfileUpdated = 'profile_updated';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Alta',
            self::Updated => 'Edición',
            self::Deleted => 'Eliminación',
            self::ForceDeleted => 'Eliminación permanente',
            self::Restored => 'Restauración',
            self::FotoAttached => 'Foto testigo',
            self::Asignado => 'Asignación',
            self::Entregado => 'Entrega',
            self::StockDecremented => 'Descuento inventario',
            self::PasswordChanged => 'Cambio de contraseña',
            self::ProfileUpdated => 'Actualización de perfil',
        };
    }
}
