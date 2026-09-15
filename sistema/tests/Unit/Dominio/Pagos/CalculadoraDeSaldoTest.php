<?php

namespace Tests\Unit\Dominio\Pagos;

use App\Dominio\Ordenes\EstadoDePrenda;
use App\Dominio\Pagos\CalculadoraDeSaldo;
use App\Dominio\Pagos\Dinero;
use App\Dominio\Pagos\EstadoDePago;
use PHPUnit\Framework\TestCase;

/**
 * RN-26 · Valor de la orden, RN-27 · Saldo pendiente y RN-29 · Estado de pago.
 */
class CalculadoraDeSaldoTest extends TestCase
{
    public function test_rn_26_valor_de_la_orden(): void
    {
        $calculadora = new CalculadoraDeSaldo;
        $prendas = [
            [15000, EstadoDePrenda::Terminada],
            [8000, EstadoDePrenda::Pendiente],
            [8000, EstadoDePrenda::Pendiente],
        ];

        $this->assertSame(31000, $calculadora->valor($prendas)->valor());

        // Si una camisa se devuelve sin arreglar, su precio deja de contar
        $prendas[2][1] = EstadoDePrenda::Devuelta;
        $this->assertSame(23000, $calculadora->valor($prendas)->valor());
    }

    public function test_rn_27_saldo_pendiente(): void
    {
        $calculadora = new CalculadoraDeSaldo;

        // Pagos de $10.000 y $5.000, este último anulado
        $this->assertSame(10000, $calculadora->pagado([[10000, false], [5000, true]])->valor());
        $this->assertSame(21000, $calculadora->saldo(Dinero::pesos(31000), [[10000, false], [5000, true]])->valor());
        $this->assertSame(31000, $calculadora->saldo(Dinero::pesos(31000), [])->valor());
        $this->assertSame(0, $calculadora->saldo(Dinero::pesos(31000), [[10000, false], [21000, false]])->valor());
    }

    public function test_rn_29_estado_de_pago(): void
    {
        $calculadora = new CalculadoraDeSaldo;

        // Solo mira el saldo: una orden Entregada puede estar Por cobrar, y una En proceso, Pagada
        $this->assertSame(EstadoDePago::PorCobrar, $calculadora->estadoDePago(Dinero::pesos(21000)));
        $this->assertSame(EstadoDePago::PorCobrar, $calculadora->estadoDePago(Dinero::pesos(1)));
        $this->assertSame(EstadoDePago::Pagada, $calculadora->estadoDePago(Dinero::pesos(0)));
        $this->assertSame('Por cobrar', EstadoDePago::PorCobrar->etiqueta());
        $this->assertSame('Pagada', EstadoDePago::Pagada->etiqueta());
    }
}
