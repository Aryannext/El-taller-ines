<?php

namespace Tests\Unit\Dominio\Ordenes;

use App\Dominio\Compartido\ReglaIncumplida;
use App\Dominio\Ordenes\NumeroDeOrden;
use PHPUnit\Framework\TestCase;

/**
 * RN-08 · Número de orden: su formato y el siguiente. Que no se reutilice lo prueba RegistrarOrdenTest.
 */
class NumeroDeOrdenTest extends TestCase
{
    public function test_rn_08_se_muestra_con_al_menos_cuatro_digitos(): void
    {
        $this->assertSame('#0042', NumeroDeOrden::desde(41)->siguiente()->formato());
        $this->assertSame(42, NumeroDeOrden::desde(41)->siguiente()->valor());
        $this->assertSame('#0001', NumeroDeOrden::desde(1)->formato());
        $this->assertSame('#10000', NumeroDeOrden::desde(10000)->formato());
    }

    public function test_rn_08_el_numero_empieza_en_uno(): void
    {
        $this->expectException(ReglaIncumplida::class);
        NumeroDeOrden::desde(0);
    }
}
