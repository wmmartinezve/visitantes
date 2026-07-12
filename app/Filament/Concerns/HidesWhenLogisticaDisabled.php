<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Support\VisitantesFeatures;

trait HidesWhenLogisticaDisabled
{
    public static function shouldRegisterNavigation(): bool
    {
        return VisitantesFeatures::logistica();
    }

    public static function canAccess(): bool
    {
        return VisitantesFeatures::logistica();
    }
}
