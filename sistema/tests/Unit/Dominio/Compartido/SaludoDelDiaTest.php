<?php

namespace Tests\Unit\Dominio\Compartido;

use App\Dominio\Compartido\SaludoDelDia;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class SaludoDelDiaTest extends TestCase
{
    public function test_rn_47_el_saludo_cambia_con_la_hora(): void
    {
        $saludo = new SaludoDelDia;

        // Inés abre el sistema a las 2:30 p. m. y lee «Buenas tardes»
        $this->assertSame('Buenas tardes', $saludo->para(new DateTimeImmutable('2026-09-23 14:30:00')));

        // Los bordes de la regla: hasta las 11:59 es de día; de 12:00 a 18:59, tarde; desde las 19:00, noche
        $esperados = [
            '00:00' => 'Buenos días',
            '05:59' => 'Buenos días',
            '11:59' => 'Buenos días',
            '12:00' => 'Buenas tardes',
            '18:59' => 'Buenas tardes',
            '19:00' => 'Buenas noches',
            '23:59' => 'Buenas noches',
        ];

        foreach ($esperados as $hora => $esperado) {
            $this->assertSame($esperado, $saludo->para(new DateTimeImmutable("2026-09-23 {$hora}:00")), "A las {$hora} el saludo debe ser «{$esperado}».");
        }
    }
}
