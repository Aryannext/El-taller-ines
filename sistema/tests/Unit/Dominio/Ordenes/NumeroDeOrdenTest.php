<?php

namespace Tests\Unit\Dominio\Ordenes;

use App\Dominio\Compartido\ReglaIncumplida;
use App\Dominio\Ordenes\NumeroDeOrden;
use PHPUnit\Framework\TestCase;

/**
 * RN-08 · Número de orden: su formato, el siguiente y cómo se lee lo que escribe la dueña. Que no se reutilice lo prueba RegistrarOrdenTest.
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

    public function test_rn_08_se_lee_como_se_escribe_en_la_bolsa(): void
    {
        foreach (['42', '0042', '#0042', ' #42 ', '# 0042'] as $texto) {
            $this->assertSame(42, NumeroDeOrden::leer($texto)?->valor(), "«{$texto}»");
        }
        $this->assertSame(10000, NumeroDeOrden::leer('#10000')?->valor());

        foreach (['', '0', '#', 'marta', '42a', '4 2', '-42', '4.2', '1234567890'] as $texto) {
            $this->assertNull(NumeroDeOrden::leer($texto), "«{$texto}» no es un número de orden");
        }
    }
}
