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

    /**
     * RN-32: suma los saldos de las órdenes que no están canceladas, incluidas las entregadas.
     * Sirve para lo que debe un cliente (HU-05) y para el total por cobrar del negocio (HU-25, HU-26).
     *
     * @param  iterable<array{0: Dinero, 1: bool}>  $saldos  saldo y si la orden está cancelada
     */
    public function porCobrar(iterable $saldos): Dinero
    {
        $total = Dinero::pesos(0);

        foreach ($saldos as [$saldo, $cancelada]) {
            if (! $cancelada) {
                $total = $total->sumar($saldo);
            }
        }

        return $total;
    }

    public function estadoDePago(Dinero $saldo): EstadoDePago
    {
        return $saldo->esMayorQue(Dinero::pesos(0)) ? EstadoDePago::PorCobrar : EstadoDePago::Pagada;
    }
}
