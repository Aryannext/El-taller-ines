<?php

namespace Tests\Soporte;

use App\Dominio\Compartido\Reloj;
use DateTimeImmutable;
use DateTimeZone;

/**
 * Doble de prueba de RelojDeColombia: «hoy» es la fecha del criterio, no la del día en que se corre la prueba.
 */
final class RelojFijo implements Reloj
{
    private readonly DateTimeImmutable $ahora;

    public function __construct(string $ahora)
    {
        $this->ahora = new DateTimeImmutable($ahora, new DateTimeZone('America/Bogota'));
    }

    public function ahora(): DateTimeImmutable
    {
        return $this->ahora;
    }

    public function hoy(): DateTimeImmutable
    {
        return $this->ahora->setTime(0, 0);
    }
}
