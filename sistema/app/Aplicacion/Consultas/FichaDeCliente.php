<?php

declare(strict_types=1);

namespace App\Aplicacion\Consultas;

use App\Dominio\Ordenes\EstadoDeOrden;
use App\Dominio\Ordenes\NumeroDeOrden;
use App\Dominio\Pagos\CalculadoraDeSaldo;
use App\Dominio\Pagos\Dinero;
use App\Dominio\Pagos\EstadoDePago;
use App\Modelos\Cliente;
use App\Modelos\Orden;
use App\Modelos\Pago;
use App\Modelos\Prenda;

/**
 * CU-08 · Las órdenes de un cliente y cuánto debe en total (HU-05, PT-05). Solo del negocio de la sesión (RN-01).
 */
class FichaDeCliente
{
    public function __construct(private readonly CalculadoraDeSaldo $calculadora) {}

    /**
     * @return array{cliente: Cliente, ordenes: list<array{orden: Orden, numero: string, estado: EstadoDeOrden, estadoDePago: ?EstadoDePago, saldo: Dinero}>, debe: Dinero, ordenesQueDeben: list<string>}
     */
    public function obtener(Cliente $cliente): array
    {
        // RNF-01: las prendas y los pagos de todas sus órdenes se cargan juntos, no uno por orden
        $ordenes = $cliente->ordenes()
            ->with(['prendas', 'pagos'])
            ->orderByDesc('numero')
            ->get()
            ->map(fn (Orden $orden) => $this->fila($orden))
            ->values()
            ->all();

        // RN-32 para un cliente: los saldos de sus órdenes no canceladas, incluidas las entregadas (CA-05.2)
        $debe = $this->calculadora->porCobrar(array_map(
            fn (array $fila) => [$fila['saldo'], $fila['estado'] === EstadoDeOrden::Cancelada],
            $ordenes,
        ));

        $queDeben = array_filter($ordenes, fn (array $fila) => $fila['estadoDePago'] === EstadoDePago::PorCobrar);

        return [
            'cliente' => $cliente,
            'ordenes' => $ordenes,
            'debe' => $debe,
            'ordenesQueDeben' => array_values(array_map(fn (array $fila) => $fila['numero'], $queDeben)),
        ];
    }

    /**
     * @return array{orden: Orden, numero: string, estado: EstadoDeOrden, estadoDePago: ?EstadoDePago, saldo: Dinero}
     */
    private function fila(Orden $orden): array
    {
        $estado = EstadoDeOrden::calcular($orden->prendas->map(fn (Prenda $prenda) => $prenda->estado), $orden->cancelada_en !== null);
        $valor = $this->calculadora->valor($orden->prendas->map(fn (Prenda $prenda) => [$prenda->precio, $prenda->estado]));
        $saldo = $this->calculadora->saldo($valor, $orden->pagos->map(fn (Pago $pago) => [$pago->valor, $pago->anulado_en !== null]));

        return [
            'orden' => $orden,
            'numero' => NumeroDeOrden::desde($orden->numero)->formato(),
            'estado' => $estado,
            // Una orden cancelada no se cobra: no está Pagada ni Por cobrar (RN-29)
            'estadoDePago' => $estado === EstadoDeOrden::Cancelada ? null : $this->calculadora->estadoDePago($saldo),
            'saldo' => $saldo,
        ];
    }
}
