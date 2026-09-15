<?php

declare(strict_types=1);

namespace App\Dominio\Pagos;

use App\Dominio\Ordenes\EstadoDePrenda;

/**
 * Valor de la orden (RN-26), saldo pendiente (RN-27) y estado de pago (RN-29). Siempre se calculan, nunca se escriben.
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

    /**
     * Suma los pagos que no están anulados (RN-31).
     *
     * @param  iterable<array{0: int, 1: bool}>  $pagos  valor y si está anulado
     */
    public function pagado(iterable $pagos): Dinero
    {
        $pagado = Dinero::pesos(0);

        foreach ($pagos as [$pago, $anulado]) {
            if (! $anulado) {
                $pagado = $pagado->sumar(Dinero::pesos($pago));
            }
        }

        return $pagado;
    }

    /**
     * El valor menos los pagos no anulados. Nunca queda negativo: RN-16 y RN-28 impiden que lo pagado supere el valor.
     *
     * @param  iterable<array{0: int, 1: bool}>  $pagos  valor y si está anulado
     */
    public function saldo(Dinero $valor, iterable $pagos): Dinero
    {
        return $valor->restar($this->pagado($pagos));
    }

    public function estadoDePago(Dinero $saldo): EstadoDePago
    {
        return $saldo->esMayorQue(Dinero::pesos(0)) ? EstadoDePago::PorCobrar : EstadoDePago::Pagada;
    }
}
