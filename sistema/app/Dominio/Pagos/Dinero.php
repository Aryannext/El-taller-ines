<?php

declare(strict_types=1);

namespace App\Dominio\Pagos;

use App\Dominio\Compartido\ReglaIncumplida;

/**
 * Objeto de valor: pesos colombianos enteros, nunca decimales de coma flotante (RN-11).
 */
final class Dinero
{
    private function __construct(private readonly int $pesos) {}

    public static function pesos(int $valor): self
    {
        if ($valor < 0) {
            throw new ReglaIncumplida('RN-11', 'El valor no puede ser negativo.');
        }

        return new self($valor);
    }

    /**
     * RN-11: el precio de una prenda es un número entero de pesos mayor que cero.
     */
    public static function precio(int $valor): self
    {
        if ($valor <= 0) {
            throw new ReglaIncumplida('RN-11', 'El precio debe ser mayor que cero.', 'precio');
        }

        return new self($valor);
    }

    public function sumar(self $otro): self
    {
        return new self($this->pesos + $otro->pesos);
    }

    public function restar(self $otro): self
    {
        return self::pesos($this->pesos - $otro->pesos);
    }

    public function esMayorQue(self $otro): bool
    {
        return $this->pesos > $otro->pesos;
    }

    public function valor(): int
    {
        return $this->pesos;
    }

    /**
     * RNF-08: con punto de miles y sin decimales, como $15.000.
     */
    public function formato(): string
    {
        return '$'.number_format($this->pesos, 0, ',', '.');
    }
}
