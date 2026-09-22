<?php

namespace Tests\Unit\Dominio\Ordenes;

use App\Dominio\Ordenes\EstadoDeOrden;
use App\Dominio\Ordenes\EstadoDePrenda;
use App\Dominio\Ordenes\ReglasDeSeguimiento;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

/**
 * RN-34, RN-35 y RN-36 · Las reglas de las listas de seguimiento, con los ejemplos de docs/02-requisitos/reglas-de-negocio.md.
 */
class ReglasDeSeguimientoTest extends TestCase
{
    private ReglasDeSeguimiento $reglas;

    protected function setUp(): void
    {
        $this->reglas = new ReglasDeSeguimiento;
    }

    public function test_rn_34_orden_atrasada(): void
    {
        $hoy = new DateTimeImmutable('2026-09-16 10:00:00');
        $el15 = new DateTimeImmutable('2026-09-15');

        // La #0042, En proceso con entrega el 15, está atrasada
        $this->assertTrue($this->reglas->estaAtrasada(EstadoDeOrden::EnProceso, $el15, $hoy));
        $this->assertSame(1, $this->reglas->diasDeAtraso($el15, $hoy));
        // La #0043, Lista para entregar con entrega el 15, no: el trabajo está hecho y falta que el cliente la recoja
        $this->assertFalse($this->reglas->estaAtrasada(EstadoDeOrden::ListaParaEntregar, $el15, $hoy));
        // Con entrega hoy todavía no está atrasada
        $this->assertFalse($this->reglas->estaAtrasada(EstadoDeOrden::EnProceso, new DateTimeImmutable('2026-09-16'), $hoy));
    }

    public function test_rn_35_orden_sin_reclamar(): void
    {
        // Con un plazo de 30 días, una orden que quedó lista el 1 de agosto está sin reclamar el 1 de septiembre
        $listaEn = new DateTimeImmutable('2026-08-01 17:00:00');
        $this->assertTrue($this->reglas->estaSinReclamar(EstadoDeOrden::ListaParaEntregar, $listaEn, new DateTimeImmutable('2026-09-01'), 30));
        // El 31 de agosto lleva justo 30 días: hace falta pasar el plazo
        $this->assertFalse($this->reglas->estaSinReclamar(EstadoDeOrden::ListaParaEntregar, $listaEn, new DateTimeImmutable('2026-08-31'), 30));
        // Una orden que ya no está lista no espera en el taller
        $this->assertFalse($this->reglas->estaSinReclamar(EstadoDeOrden::Entregada, $listaEn, new DateTimeImmutable('2026-09-01'), 30));

        // La fecha de corte que usa la consulta dice lo mismo: sin reclamar si quedó lista antes del 2 de agosto
        $this->assertSame('2026-08-02', $this->reglas->sinReclamarSiQuedoListaAntesDe(new DateTimeImmutable('2026-09-01 15:00:00'), 30)->format('Y-m-d'));

        // Tiene dos camisas Terminadas: son dos prendas sin reclamar. La entregada ya salió del taller
        $this->assertSame(2, $this->reglas->prendasSinReclamar([EstadoDePrenda::Terminada, EstadoDePrenda::Terminada, EstadoDePrenda::Entregada]));
    }

    public function test_rn_36_dias_de_espera(): void
    {
        // Quedó lista el 1 de septiembre a las 5:00 p. m.; el 16 de septiembre lleva 15 días de espera
        $this->assertSame(15, $this->reglas->diasDeEspera(new DateTimeImmutable('2026-09-01 17:00:00'), new DateTimeImmutable('2026-09-16 08:00:00')));
        // La hora no cuenta: lista ayer a las 11 p. m. es un día, no cero
        $this->assertSame(1, $this->reglas->diasDeEspera(new DateTimeImmutable('2026-09-15 23:00:00'), new DateTimeImmutable('2026-09-16 07:00:00')));
    }
}
