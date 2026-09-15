<?php

namespace Tests\Unit\Dominio\Ordenes;

use App\Dominio\Ordenes\EstadoDeOrden;
use App\Dominio\Ordenes\EstadoDePrenda as P;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * RN-18 · El estado de la orden se calcula de sus prendas.
 */
class EstadoDeOrdenTest extends TestCase
{
    /**
     * @param  list<P>  $prendas
     */
    #[DataProvider('ejemplosDeLaRegla')]
    public function test_rn_18_el_estado_de_la_orden_se_calcula_de_sus_prendas(array $prendas, EstadoDeOrden $esperado): void
    {
        $this->assertSame($esperado, EstadoDeOrden::calcular($prendas, cancelada: false));
    }

    /**
     * La tabla de ejemplos de RN-18.
     *
     * @return array<string, array{list<P>, EstadoDeOrden}>
     */
    public static function ejemplosDeLaRegla(): array
    {
        return [
            'Terminada, En proceso, Pendiente' => [[P::Terminada, P::EnProceso, P::Pendiente], EstadoDeOrden::EnProceso],
            'Terminada, Terminada, Terminada' => [[P::Terminada, P::Terminada, P::Terminada], EstadoDeOrden::ListaParaEntregar],
            'Entregada, Terminada' => [[P::Entregada, P::Terminada], EstadoDeOrden::ListaParaEntregar],
            'Entregada, Entregada' => [[P::Entregada, P::Entregada], EstadoDeOrden::Entregada],
            'Terminada, Devuelta' => [[P::Terminada, P::Devuelta], EstadoDeOrden::ListaParaEntregar],
            'Entregada, Devuelta' => [[P::Entregada, P::Devuelta], EstadoDeOrden::Entregada],
            'Tres pendientes, como queda al registrarla' => [[P::Pendiente, P::Pendiente, P::Pendiente], EstadoDeOrden::EnProceso],
            'Sin prendas que cuenten' => [[P::Devuelta], EstadoDeOrden::EnProceso],
        ];
    }

    public function test_rn_18_una_orden_cancelada_no_depende_de_sus_prendas(): void
    {
        $this->assertSame(EstadoDeOrden::Cancelada, EstadoDeOrden::calcular([P::Terminada, P::Terminada], cancelada: true));
    }

    public function test_cada_estado_tiene_su_nombre_para_la_usuaria(): void
    {
        $this->assertSame(
            ['En proceso', 'Lista para entregar', 'Entregada', 'Cancelada'],
            array_map(fn (EstadoDeOrden $estado) => $estado->etiqueta(), EstadoDeOrden::cases()),
        );
    }
}
