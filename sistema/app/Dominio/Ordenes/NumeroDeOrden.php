<?php

declare(strict_types=1);

namespace App\Dominio\Ordenes;

use App\Dominio\Compartido\ReglaIncumplida;

/**
 * Objeto de valor: número consecutivo de la orden dentro de su negocio, el que se escribe en la bolsa (RN-08).
 */
final class NumeroDeOrden
{
    private function __construct(private readonly int $valor) {}

    public static function desde(int $valor): self
    {
        if ($valor < 1) {
            throw new ReglaIncumplida('RN-08', 'El número de orden empieza en 1.');
        }

        return new self($valor);
    }

    public function siguiente(): self
    {
        return new self($this->valor + 1);
    }

    public function valor(): int
    {
        return $this->valor;
    }

    /**
     * Con al menos cuatro dígitos: #0042. Desde la orden 10.000 muestra todos los dígitos.
     */
    public function formato(): string
    {
        return '#'.str_pad((string) $this->valor, 4, '0', STR_PAD_LEFT);
    }
}
