<?php

declare(strict_types=1);

namespace App\Dominio\Ordenes;

/**
 * RN-12: una prenda está siempre en uno solo de estos estados. Los valores son los del ENUM de prendas.estado.
 */
enum EstadoDePrenda: string
{
    case Pendiente = 'pendiente';
    case EnProceso = 'en_proceso';
    case Terminada = 'terminada';
    case Entregada = 'entregada';
    case Devuelta = 'devuelta';

    /**
     * Toda prenda nueva empieza Pendiente.
     */
    public static function inicial(): self
    {
        return self::Pendiente;
    }

    public function etiqueta(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::EnProceso => 'En proceso',
            self::Terminada => 'Terminada',
            self::Entregada => 'Entregada',
            self::Devuelta => 'Devuelta',
        };
    }
}
