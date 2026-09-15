<?php

namespace Tests\Unit\Dominio\Ordenes;

use App\Dominio\Compartido\ReglaIncumplida;
use App\Dominio\Ordenes\EstadoDePrenda;
use App\Dominio\Ordenes\TransicionesDePrenda;
use PHPUnit\Framework\TestCase;

/**
 * RN-13 · Solo se entrega lo terminado, RN-14 · Un retoque devuelve la prenda a En proceso y RN-15 · Una prenda entregada no se modifica.
 */
class TransicionesDePrendaTest extends TestCase
{
    public function test_rn_13_solo_se_entrega_lo_terminado(): void
    {
        $transiciones = new TransicionesDePrenda;

        // Un vestido En proceso no se puede marcar como Entregado
        foreach ([EstadoDePrenda::Pendiente, EstadoDePrenda::EnProceso] as $desde) {
            $this->assertRegla('RN-13', 'Una prenda solo se entrega cuando está Terminada.', fn () => $transiciones->exigir($desde, EstadoDePrenda::Entregada));
        }

        // Desde Terminada sí: es lo que hace entregar la orden (RN-20)
        $transiciones->exigir(EstadoDePrenda::Terminada, EstadoDePrenda::Entregada);
        // Pero no es una opción de la prenda sola (CA-20.2)
        foreach (EstadoDePrenda::cases() as $desde) {
            $this->assertNotContains(EstadoDePrenda::Entregada, $transiciones->permitidas($desde));
        }
    }

    public function test_rn_14_un_retoque_devuelve_la_prenda_a_en_proceso(): void
    {
        $transiciones = new TransicionesDePrenda;

        $this->assertSame([EstadoDePrenda::EnProceso], $transiciones->permitidas(EstadoDePrenda::Terminada));
        $transiciones->exigir(EstadoDePrenda::Terminada, EstadoDePrenda::EnProceso);

        // Los demás cambios del diagrama 4, y ninguno de vuelta a Pendiente
        $this->assertSame([EstadoDePrenda::EnProceso, EstadoDePrenda::Terminada], $transiciones->permitidas(EstadoDePrenda::Pendiente));
        $this->assertSame([EstadoDePrenda::Terminada], $transiciones->permitidas(EstadoDePrenda::EnProceso));
        $this->assertRegla('RN-12', 'Elige uno de los estados que se muestran.', fn () => $transiciones->exigir(EstadoDePrenda::EnProceso, EstadoDePrenda::Pendiente));
        $this->assertRegla('RN-12', 'Elige uno de los estados que se muestran.', fn () => $transiciones->exigir(EstadoDePrenda::Terminada, EstadoDePrenda::Terminada));
    }

    public function test_rn_15_una_prenda_entregada_no_se_modifica(): void
    {
        $transiciones = new TransicionesDePrenda;

        foreach ([EstadoDePrenda::Pendiente, EstadoDePrenda::EnProceso, EstadoDePrenda::Terminada] as $estado) {
            $this->assertTrue($transiciones->puedeModificarse($estado), "{$estado->value} se puede modificar");
        }
        foreach ([EstadoDePrenda::Entregada, EstadoDePrenda::Devuelta] as $estado) {
            $this->assertFalse($transiciones->puedeModificarse($estado), "{$estado->value} no se puede modificar");
            $this->assertSame([], $transiciones->permitidas($estado));
        }

        $this->assertRegla('RN-15', 'Esta prenda ya fue entregada y no se puede modificar.', fn () => $transiciones->exigir(EstadoDePrenda::Entregada, EstadoDePrenda::EnProceso));
        $this->assertRegla('RN-15', 'Esta prenda ya fue devuelta y no se puede modificar.', fn () => $transiciones->exigir(EstadoDePrenda::Devuelta, EstadoDePrenda::Terminada));
    }

    private function assertRegla(string $regla, string $mensaje, callable $accion): void
    {
        try {
            $accion();
            $this->fail("Se esperaba {$regla}");
        } catch (ReglaIncumplida $error) {
            $this->assertSame([$regla, $mensaje], [$error->regla, $error->mensajeParaUsuaria]);
        }
    }
}
