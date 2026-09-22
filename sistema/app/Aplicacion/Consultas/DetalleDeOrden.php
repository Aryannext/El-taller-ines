<?php

declare(strict_types=1);

namespace App\Aplicacion\Consultas;

use App\Dominio\Ordenes\EstadoDeOrden;
use App\Dominio\Ordenes\EstadoDePrenda;
use App\Dominio\Ordenes\NumeroDeOrden;
use App\Dominio\Ordenes\TransicionesDePrenda;
use App\Dominio\Pagos\CalculadoraDeSaldo;
use App\Dominio\Pagos\Dinero;
use App\Dominio\Pagos\EstadoDePago;
use App\Modelos\Orden;
use App\Modelos\Pago;
use App\Modelos\Prenda;
use DateTimeImmutable;

/**
 * CU-16 · Todo lo de una orden en una pantalla (HU-14): PT-07, PT-09 y PT-13.
 */
class DetalleDeOrden
{
    public function __construct(
        private readonly CalculadoraDeSaldo $calculadora,
        private readonly TransicionesDePrenda $transiciones,
    ) {}

    /**
     * @return array{orden: Orden, numero: string, valor: Dinero, pagado: Dinero, saldo: Dinero, estado: EstadoDeOrden, estadoDePago: ?EstadoDePago, entregadaEn: ?DateTimeImmutable, puedeEntregarse: bool, puedeCancelarse: bool, puedeAgregarPrendas: bool, prendasCorregibles: list<int>}
     */
    public function obtener(Orden $orden): array
    {
        // RNF-01: una consulta por relación, no una por prenda
        $orden->loadMissing([
            'cliente',
            'prendas' => fn ($consulta) => $consulta->orderBy('id'),
            'prendas.tipoPrenda',
            'prendas.fotos' => fn ($consulta) => $consulta->orderBy('posicion'),
            'pagos' => fn ($consulta) => $consulta->orderBy('pagado_en')->orderBy('id'),
            'pagos.metodoPago',
            'avisos' => fn ($consulta) => $consulta->orderBy('generado_en')->orderBy('id'),
        ]);

        $estado = EstadoDeOrden::calcular($orden->prendas->map(fn (Prenda $prenda) => $prenda->estado), $orden->cancelada_en !== null);
        $valor = $this->calculadora->valor($orden->prendas->map(fn (Prenda $prenda) => [$prenda->precio, $prenda->estado]));
        $pagado = $this->calculadora->pagado($orden->pagos->map(fn (Pago $pago) => [$pago->valor, $pago->anulado_en !== null]));

        return [
            'orden' => $orden,
            'numero' => NumeroDeOrden::desde($orden->numero)->formato(),
            'valor' => $valor,
            'pagado' => $pagado,
            'saldo' => $valor->restar($pagado),
            'estado' => $estado,
            // Una orden cancelada no se cobra: no está Pagada ni Por cobrar
            'estadoDePago' => $estado === EstadoDeOrden::Cancelada ? null : $this->calculadora->estadoDePago($valor->restar($pagado)),
            // RN-23: la entrega real de la orden es la de su última prenda entregada
            'entregadaEn' => $estado === EstadoDeOrden::Entregada ? $orden->prendas->max('entregada_en') : null,
            // HU-12: las que se pueden corregir (RN-15); en una orden cancelada, ninguna (RN-24)
            // HU-21: hay algo Terminado para entregar y la orden no está cancelada (RN-20, RN-24)
            'puedeEntregarse' => $estado !== EstadoDeOrden::Cancelada && $orden->prendas->contains(fn (Prenda $prenda) => $prenda->estado === EstadoDePrenda::Terminada),
            // HU-22: solo una orden que no está Entregada ni ya Cancelada (RN-24)
            'puedeCancelarse' => ! in_array($estado, [EstadoDeOrden::Entregada, EstadoDeOrden::Cancelada], true),
            // HU-11: solo En proceso o Lista para entregar (RF-11, RN-24)
            'puedeAgregarPrendas' => in_array($estado, [EstadoDeOrden::EnProceso, EstadoDeOrden::ListaParaEntregar], true),
            'prendasCorregibles' => $estado === EstadoDeOrden::Cancelada ? [] : $orden->prendas
                ->filter(fn (Prenda $prenda) => $this->transiciones->puedeModificarse($prenda->estado))
                ->map(fn (Prenda $prenda) => $prenda->id)
                ->values()
                ->all(),
        ];
    }
}
