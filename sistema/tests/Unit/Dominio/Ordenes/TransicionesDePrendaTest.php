<?php

namespace Tests\Unit\Dominio\Ordenes;

use App\Dominio\Ordenes\EstadoDePrenda;
use App\Dominio\Ordenes\TransicionesDePrenda;
use PHPUnit\Framework\TestCase;

/**
 * RN-15 · Una prenda entregada no se modifica. Los cambios de estado permitidos (RN-13) llegan con HU-20.
 */
class TransicionesDePrendaTest extends TestCase
{
    public function test_rn_15_una_prenda_entregada_no_se_modifica(): void
    {
        $transiciones = new TransicionesDePrenda;

        foreach ([EstadoDePrenda::Pendiente, EstadoDePrenda::EnProceso, EstadoDePrenda::Terminada] as $estado) {
            $this->assertTrue($transiciones->puedeModificarse($estado), "{$estado->value} se puede modificar");
        }
        foreach ([EstadoDePrenda::Entregada, EstadoDePrenda::Devuelta] as $estado) {
            $this->assertFalse($transiciones->puedeModificarse($estado), "{$estado->value} no se puede modificar");
        }
    }
}
