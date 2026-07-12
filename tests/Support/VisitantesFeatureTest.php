<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Support\VisitantesFeatures;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

final class VisitantesFeatureTest
{
    public static function skipUnlessLogistica(PHPUnitTestCase $test): void
    {
        if (! VisitantesFeatures::logistica()) {
            $test->markTestSkipped('Módulo de logística deshabilitado.');
        }
    }
}
