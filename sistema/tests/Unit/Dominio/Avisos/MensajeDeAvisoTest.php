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
        $alGenerarse = MensajeDeAviso::construir('Marta Rincón', NumeroDeOrden::desde(42), 3, Dinero::pesos(21000));
        $alEnviarse = MensajeDeAviso::construir('Marta Rincón', NumeroDeOrden::desde(42), 3, Dinero::pesos(11000));

        $this->assertSame('Hola Marta, tu orden #0042 del taller está lista para recoger. Prendas listas: 3. Saldo pendiente: $21.000. Te esperamos.', $alGenerarse->texto());
        $this->assertSame('Hola Marta, tu orden #0042 del taller está lista para recoger. Prendas listas: 3. Saldo pendiente: $11.000. Te esperamos.', $alEnviarse->texto());
    }

    public function test_los_valores_de_la_plantilla_van_en_su_orden(): void
    {
        $this->assertSame(['Marta', '#0042', '3', '$21.000'], MensajeDeAviso::construir('Marta Rincón', NumeroDeOrden::desde(42), 3, Dinero::pesos(21000))->parametros());
        // El primer nombre es la primera palabra, aunque sobren espacios; sin saldo dice $0
        $this->assertSame(['Luis', '#0043', '1', '$0'], MensajeDeAviso::construir('  Luis   Pardo ', NumeroDeOrden::desde(43), 1, Dinero::pesos(0))->parametros());
        $this->assertSame('Rosa', MensajeDeAviso::construir('Rosa', NumeroDeOrden::desde(7), 2, Dinero::pesos(5000))->parametros()[0]);
    }
}
