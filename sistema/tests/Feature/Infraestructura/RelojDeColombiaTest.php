<?php

namespace Tests\Feature\Infraestructura;

use App\Dominio\Compartido\Reloj;
use App\Infraestructura\Reloj\RelojDeColombia;
use Carbon\CarbonImmutable;
use Tests\TestCase;

/**
 * RN-09 · Las fechas se interpretan en hora de Colombia.
 */
class RelojDeColombiaTest extends TestCase
{
    public function test_rn_09_las_fechas_se_interpretan_en_hora_de_colombia(): void
    {
        // Las 3:00 a. m. del 15 de septiembre en UTC son las 10:00 p. m. del 14 en Colombia: todavía es el 14
        $this->travelTo(CarbonImmutable::parse('2026-09-15 03:00:00', 'UTC'));
        $reloj = new RelojDeColombia;

        $this->assertSame('2026-09-14 22:00', $reloj->ahora()->format('Y-m-d H:i'));
        $this->assertSame('America/Bogota', $reloj->ahora()->getTimezone()->getName());
        $this->assertSame('2026-09-14 00:00', $reloj->hoy()->format('Y-m-d H:i'));
        $this->assertInstanceOf(RelojDeColombia::class, app(Reloj::class), 'El sistema usa el reloj de Colombia');
    }
}
