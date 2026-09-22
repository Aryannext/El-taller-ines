<?php

declare(strict_types=1);

namespace App\Aplicacion\Consultas;

use App\Dominio\Ordenes\EstadoDeOrden;
use App\Dominio\Ordenes\EstadoDePrenda;
use App\Dominio\Pagos\Dinero;
use App\Modelos\Orden;
use App\Modelos\Prenda;

/**
 * CU-28 · HU-26 · Ver quién me debe (PT-22): las órdenes con saldo, de la mayor deuda a la menor, y el total por cobrar (RN-32).
 * Las pagadas no aparecen (RN-29, CA-26.2), y las canceladas tampoco: no se cobran (RN-24).
 */
class QuienMeDebe
{
    /**
     * @return array{ordenes: list<array{orden: Orden, saldo: Dinero, estado: EstadoDeOrden}>, total: Dinero}
     */
    public function obtener(): array
    {
        // El saldo se calcula en SQL, como el total por cobrar del panel: las 750 órdenes del volumen de referencia
        // no se traen a memoria para quedarse con las que deben (RNF-01, RNF-02)
        $saldo = 'coalesce(valor, 0) - coalesce(pagado, 0)';

        $ordenes = Orden::query()
            ->whereNull('cancelada_en')
            // Las prendas Devueltas no se cobran (RN-44) y los pagos anulados no cuentan (RN-31)
            ->withSum(['prendas as valor' => fn ($prendas) => $prendas->where('estado', '!=', EstadoDePrenda::Devuelta->value)], 'precio')
            ->withSum(['pagos as pagado' => fn ($pagos) => $pagos->whereNull('anulado_en')], 'valor')
            ->havingRaw("{$saldo} > 0")
            ->orderByRaw("{$saldo} desc")
            ->orderBy('numero')
            ->with(['cliente', 'prendas' => fn ($prendas) => $prendas->select(['id', 'orden_id', 'estado'])])
            ->get();

        $filas = $ordenes->map(fn (Orden $orden) => [
            'orden' => $orden,
            'saldo' => Dinero::pesos((int) $orden->getAttribute('valor') - (int) $orden->getAttribute('pagado')),
            'estado' => EstadoDeOrden::calcular($orden->prendas->map(fn (Prenda $prenda) => $prenda->estado), false),
        ])->values()->all();

        // RN-32: la suma de los saldos es el total por cobrar; ningún saldo es negativo (RN-16, RN-28)
        $total = array_reduce($filas, fn (Dinero $suma, array $fila) => $suma->sumar($fila['saldo']), Dinero::pesos(0));

        return ['ordenes' => $filas, 'total' => $total];
    }
}
