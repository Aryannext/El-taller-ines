<?php

namespace Tests\Unit\Dominio\Pagos;

use App\Dominio\Compartido\ReglaIncumplida;
use App\Dominio\Pagos\Dinero;
use App\Dominio\Pagos\ReglasDeValor;
use PHPUnit\Framework\TestCase;

/**
 * RN-16 · Lo pagado no puede quedar por encima del valor.
 */
class ReglasDeValorTest extends TestCase
{
    public function test_rn_16_lo_pagado_no_puede_quedar_por_encima_del_valor(): void
    {
        $reglas = new ReglasDeValor;

        // Orden de $30.000 con $25.000 pagados: quitar $10.000 la dejaría en $20.000
        try {
            $reglas->exigirValorNoMenorQuePagado(Dinero::pesos(20000), Dinero::pesos(25000));
            $this->fail('Se aceptó un valor menor que lo pagado');
        } catch (ReglaIncumplida $error) {
            $this->assertSame('RN-16', $error->regla);
            $this->assertSame('precio', $error->campo);
            $this->assertSame('La orden quedaría valiendo $20.000 y ya tiene $25.000 pagados. Primero anula el pago que sobra.', $error->mensajeParaUsuaria);
        }

        // Igual o por encima de lo pagado sí se permite
        $reglas->exigirValorNoMenorQuePagado(Dinero::pesos(25000), Dinero::pesos(25000));
        $reglas->exigirValorNoMenorQuePagado(Dinero::pesos(30000), Dinero::pesos(25000));
        $reglas->exigirValorNoMenorQuePagado(Dinero::pesos(8000), Dinero::pesos(0));
        $this->addToAssertionCount(3);
    }
}
