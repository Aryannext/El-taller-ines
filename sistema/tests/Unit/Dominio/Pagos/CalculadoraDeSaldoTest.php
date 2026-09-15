<?php

namespace Tests\Unit\Dominio\Pagos;

use App\Dominio\Ordenes\EstadoDePrenda;
use App\Dominio\Pagos\CalculadoraDeSaldo;
use PHPUnit\Framework\TestCase;

/**
 * RN-26 · Valor de la orden.
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
}
