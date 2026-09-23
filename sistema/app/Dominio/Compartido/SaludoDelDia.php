<?php

declare(strict_types=1);

namespace App\Dominio\Compartido;

use DateTimeInterface;

/**
 * RN-47 · Cómo saluda el panel según la hora del taller: buenos días hasta las 11:59, buenas tardes hasta
 * las 18:59 y buenas noches desde las 19:00. La hora es la de Colombia, la que da el Reloj (RN-09).
 */
final class SaludoDelDia
{
    public function para(DateTimeInterface $momento): string
    {
        $hora = (int) $momento->format('G');

        return match (true) {
            $hora < 12 => 'Buenos días',
            $hora < 19 => 'Buenas tardes',
            default => 'Buenas noches',
        };
    }
}
