<?php

namespace Tests;

use App\Dominio\Compartido\Reloj;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Soporte\RelojFijo;

abstract class TestCase extends BaseTestCase
{
    /**
     * Fija «hoy» y «ahora» en la fecha del criterio de aceptación, en hora de Colombia.
     */
    protected function fijarReloj(string $ahora): void
    {
        $this->app->instance(Reloj::class, new RelojFijo($ahora));
    }
}
