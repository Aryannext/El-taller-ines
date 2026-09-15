<?php

declare(strict_types=1);

namespace App\Dominio\Pagos;

use App\Dominio\Ordenes\EstadoDePrenda;

/**
 * Valor de la orden (RN-26). El saldo y el estado de pago se agregan con los pagos (HU-23).
 */
final class CalculadoraDeSaldo
{
    /**
     * Suma los precios de las prendas que no están Devueltas.
     *
     * @param  iterable<array{0: int, 1: EstadoDePrenda}>  $preciosYEstados
     */
    public function valor(iterable $preciosYEstados): Dinero
    {
        $valor = Dinero::pesos(0);

        foreach ($preciosYEstados as [$precio, $estado]) {
            if ($estado !== EstadoDePrenda::Devuelta) {
                $valor = $valor->sumar(Dinero::pesos($precio));
            }
        }

        return $valor;
    }
}
