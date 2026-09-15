<?php

declare(strict_types=1);

namespace App\Dominio\Compartido;

use DateTimeImmutable;

/**
 * La fecha y la hora de Colombia (RN-09). Todo «hoy» y «ahora» sale de aquí, para poder fijarlo en las pruebas.
 */
interface Reloj
{
    public function ahora(): DateTimeImmutable;

    public function hoy(): DateTimeImmutable;
}
