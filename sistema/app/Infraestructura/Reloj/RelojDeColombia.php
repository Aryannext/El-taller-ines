<?php

declare(strict_types=1);

namespace App\Infraestructura\Reloj;

use App\Dominio\Compartido\Reloj;
use Carbon\CarbonImmutable;
use DateTimeImmutable;

/**
 * Hora de Colombia, que no cambia de horario (RN-09). Usa Carbon para que las pruebas puedan mover el tiempo.
 */
final class RelojDeColombia implements Reloj
{
    private const ZONA = 'America/Bogota';

    public function ahora(): DateTimeImmutable
    {
        return CarbonImmutable::now(self::ZONA)->toDateTimeImmutable();
    }

    public function hoy(): DateTimeImmutable
    {
        return $this->ahora()->setTime(0, 0);
    }
}
