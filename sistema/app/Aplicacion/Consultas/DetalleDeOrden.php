<?php

declare(strict_types=1);

namespace App\Aplicacion\Consultas;

use App\Dominio\Ordenes\EstadoDeOrden;
use App\Dominio\Ordenes\NumeroDeOrden;
use App\Dominio\Pagos\CalculadoraDeSaldo;
use App\Dominio\Pagos\Dinero;
use App\Modelos\Orden;
use App\Modelos\Prenda;

/**
 * Lo que se muestra de una orden. Por ahora número, valor y estado (PT-07, PT-09); HU-14 agrega pagos, saldo y avisos.
 */
class DetalleDeOrden
{
    public function __construct(private readonly CalculadoraDeSaldo $calculadora) {}

    /**
     * @return array{orden: Orden, numero: string, valor: Dinero, estado: EstadoDeOrden}
     */
    public function obtener(Orden $orden): array
    {
        $orden->loadMissing(['cliente', 'prendas' => fn ($consulta) => $consulta->orderBy('id'), 'prendas.tipoPrenda']);

        return [
            'orden' => $orden,
            'numero' => NumeroDeOrden::desde($orden->numero)->formato(),
            'valor' => $this->calculadora->valor($orden->prendas->map(fn (Prenda $prenda) => [$prenda->precio, $prenda->estado])),
            'estado' => EstadoDeOrden::calcular($orden->prendas->map(fn (Prenda $prenda) => $prenda->estado), $orden->cancelada_en !== null),
        ];
    }
}
