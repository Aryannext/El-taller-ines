<?php

namespace Tests\Unit\Dominio\Pagos;

use App\Dominio\Compartido\ReglaIncumplida;
use App\Dominio\Pagos\Dinero;
use PHPUnit\Framework\TestCase;

/**
 * RN-11 · El precio es un valor entero en pesos. Un valor con centavos, como $15.000,50, lo rechaza la solicitud
 * antes de llegar aquí: Dinero solo recibe enteros.
 */
class DineroTest extends TestCase
{
    public function test_rn_11_el_precio_es_un_valor_entero_en_pesos(): void
    {
        $this->assertSame(15000, Dinero::precio(15000)->valor());

        try {
            Dinero::precio(0);
            $this->fail('Se aceptó un precio de $0');
        } catch (ReglaIncumplida $error) {
            $this->assertSame('RN-11', $error->regla);
            $this->assertSame('El precio debe ser mayor que cero.', $error->mensajeParaUsuaria);
        }
    }

    public function test_rnf_08_el_dinero_se_muestra_como_se_lee_en_colombia(): void
    {
        $this->assertSame('$15.000', Dinero::pesos(15000)->formato());
        $this->assertSame('$1.250.000', Dinero::pesos(1250000)->formato());
        $this->assertSame('$0', Dinero::pesos(0)->formato());
    }

    public function test_opera_sin_quedar_negativo(): void
    {
        $valor = Dinero::pesos(31000);

        $this->assertSame(41000, $valor->sumar(Dinero::pesos(10000))->valor());
        $this->assertSame(21000, $valor->restar(Dinero::pesos(10000))->valor());
        $this->assertTrue(Dinero::pesos(25000)->esMayorQue(Dinero::pesos(21000)));
        $this->assertFalse(Dinero::pesos(21000)->esMayorQue(Dinero::pesos(21000)));

        $this->expectException(ReglaIncumplida::class);
        Dinero::pesos(10000)->restar(Dinero::pesos(21000));
    }
}
