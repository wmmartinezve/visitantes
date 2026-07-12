<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Concerns\HasProcedenciaDemo;

abstract class TestCase extends BaseTestCase
{
    use HasProcedenciaDemo;
}
