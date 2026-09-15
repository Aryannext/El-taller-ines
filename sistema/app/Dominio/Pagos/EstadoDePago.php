<?php

declare(strict_types=1);

namespace App\Dominio\Pagos;

/**
 * RN-29: Pagada si el saldo pendiente es cero, Por cobrar si es mayor. No depende del estado de avance.
 */
enum EstadoDePago: string
{
    case Pagada = 'pagada';
    case PorCobrar = 'por-cobrar';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Pagada => 'Pagada',
            self::PorCobrar => 'Por cobrar',
        };
    }
}
