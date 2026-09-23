<?php

namespace Tests\Unit\Dominio\Avisos;

use App\Dominio\Avisos\MensajeDeAviso;
use App\Dominio\Ordenes\NumeroDeOrden;
use App\Dominio\Pagos\Dinero;
use PHPUnit\Framework\TestCase;

class MensajeDeAvisoTest extends TestCase
{
    public function test_rn_42_el_aviso_usa_los_datos_del_momento_del_envio(): void
    {
        // Se generó con saldo de $21.000, pero Marta abonó $10.000 antes de que saliera
        $alGenerarse = MensajeDeAviso::construir('Marta Rincón', 'Modistería Inés', NumeroDeOrden::desde(42), 3, Dinero::pesos(21000));
        $alEnviarse = MensajeDeAviso::construir('Marta Rincón', 'Modistería Inés', NumeroDeOrden::desde(42), 3, Dinero::pesos(11000));

        $this->assertSame('Hola Marta, le escribimos de Modistería Inés. Su orden #0042 ya está lista 🧵 Son 3 prendas, con un saldo de $21.000. La esperamos cuando pueda pasar.', $alGenerarse->texto());
        $this->assertSame('Hola Marta, le escribimos de Modistería Inés. Su orden #0042 ya está lista 🧵 Son 3 prendas, con un saldo de $11.000. La esperamos cuando pueda pasar.', $alEnviarse->texto());
    }

    public function test_rn_46_lo_que_dice_el_aviso(): void
    {
        // Cada taller se nombra con el nombre que le puso su dueña (HU-38)
        $otroTaller = MensajeDeAviso::construir('Marta Rincón', 'Arreglos Donde Rosa', NumeroDeOrden::desde(42), 3, Dinero::pesos(21000));
        $this->assertStringContainsString('le escribimos de Arreglos Donde Rosa', $otroTaller->texto());

        // Sin saldo no se escribe «$0»: se dice que ya está pagada
        $pagada = MensajeDeAviso::construir('Luis Pardo', 'Modistería Inés', NumeroDeOrden::desde(43), 2, Dinero::pesos(0));
        $this->assertSame('Hola Luis, le escribimos de Modistería Inés. Su orden #0043 ya está lista 🧵 Son 2 prendas y ya está pagada: solo pasar a recogerla. La esperamos cuando pueda pasar.', $pagada->texto());

        // Una sola prenda no se escribe «1 prendas»
        $una = MensajeDeAviso::construir('Rosa', 'Modistería Inés', NumeroDeOrden::desde(7), 1, Dinero::pesos(5000));
        $this->assertStringContainsString('Es 1 prenda, con un saldo de $5.000.', $una->texto());

        // Y trata de usted, no de tú
        $this->assertStringContainsString('Su orden', $una->texto());
        $this->assertStringNotContainsString('tu orden', $una->texto());
    }

    public function test_los_valores_de_la_plantilla_van_en_su_orden(): void
    {
        $this->assertSame(
            ['Marta', 'Modistería Inés', '#0042', 'Son 3 prendas, con un saldo de $21.000.'],
            MensajeDeAviso::construir('Marta Rincón', 'Modistería Inés', NumeroDeOrden::desde(42), 3, Dinero::pesos(21000))->parametros(),
        );
        // El primer nombre es la primera palabra, aunque sobren espacios
        $this->assertSame(
            ['Luis', 'Modistería Inés', '#0043', 'Es 1 prenda y ya está pagada: solo pasar a recogerla.'],
            MensajeDeAviso::construir('  Luis   Pardo ', 'Modistería Inés', NumeroDeOrden::desde(43), 1, Dinero::pesos(0))->parametros(),
        );
        $this->assertSame('Rosa', MensajeDeAviso::construir('Rosa', 'Modistería Inés', NumeroDeOrden::desde(7), 2, Dinero::pesos(5000))->parametros()[0]);
    }
}
