<?php

declare(strict_types=1);

namespace App\Aplicacion\Consultas;

use App\Dominio\Compartido\Reloj;
use App\Dominio\Ordenes\EstadoDeOrden;
use App\Dominio\Ordenes\EstadoDePrenda;
use App\Dominio\Ordenes\NumeroDeOrden;
use App\Dominio\Pagos\CalculadoraDeSaldo;
use App\Dominio\Pagos\Dinero;
use App\Dominio\Pagos\EstadoDePago;
use App\Modelos\Negocio;
use App\Modelos\Orden;
use App\Modelos\Pago;
use App\Modelos\Prenda;
use Illuminate\Database\Eloquent\Builder;

/**
 * CU-35 · Las órdenes que llevan más días listas que el plazo del negocio (HU-34, PT-21, RF-39).
 * El plazo lo define cada negocio y son 30 días si no lo cambia (RN-35); cambiarlo llega con HU-35.
 * Las prendas sin reclamar son las Terminadas de esas órdenes (RN-35).
 */
class OrdenesSinReclamar
{
    public function __construct(
        private readonly ListarOrdenes $ordenes,
        private readonly CalculadoraDeSaldo $calculadora,
        private readonly Reloj $reloj,
    ) {}

    /**
     * De la mayor espera a la menor (CA-34.3).
     *
     * @return list<array{orden: Orden, numero: string, diasDeEspera: int, prendas: int, saldo: Dinero, estadoDePago: EstadoDePago}>
     */
    public function listar(Negocio $negocio): array
    {
        // RNF-01: cliente, prendas y pagos de la página en una consulta por relación
        return $this->sinReclamar($negocio)
            ->with(['cliente', 'prendas' => fn ($prendas) => $prendas->orderBy('id'), 'prendas.tipoPrenda', 'pagos'])
            ->orderBy('lista_en')
            ->orderBy('numero')
            ->get()
            ->map(function (Orden $orden): array {
                $terminadas = $orden->prendas->filter(fn (Prenda $prenda) => $prenda->estado === EstadoDePrenda::Terminada);
                $valor = $this->calculadora->valor($orden->prendas->map(fn (Prenda $prenda) => [$prenda->precio, $prenda->estado]));
                $saldo = $this->calculadora->saldo($valor, $orden->pagos->map(fn (Pago $pago) => [$pago->valor, $pago->anulado_en !== null]));

                return [
                    'orden' => $orden,
                    'numero' => NumeroDeOrden::desde($orden->numero)->formato(),
                    'diasDeEspera' => $this->diasDeEspera($orden),
                    'prendas' => $terminadas->count(),
                    'saldo' => $saldo,
                    // Solo se le cobra al cliente si queda saldo (RN-29)
                    'estadoDePago' => $this->calculadora->estadoDePago($saldo),
                ];
            })
            ->all();
    }

    /**
     * Cuántas órdenes esperan y cuántas prendas hay en ellas, para el panel (CA-32.1).
     *
     * @return array{ordenes: int, prendas: int}
     */
    public function contar(Negocio $negocio): array
    {
        return [
            'ordenes' => $this->sinReclamar($negocio)->count(),
            'prendas' => Prenda::where('estado', EstadoDePrenda::Terminada->value)
                ->whereIn('orden_id', $this->sinReclamar($negocio)->select('ordenes.id'))
                ->count(),
        ];
    }

    /**
     * RN-36: los días calendario desde que la orden quedó lista hasta hoy. La hora no cuenta:
     * una orden lista ayer a las 11 p. m. lleva un día de espera, no cero.
     */
    public function diasDeEspera(Orden $orden): int
    {
        return (int) $orden->lista_en->setTime(0, 0)->diff($this->reloj->hoy())->days;
    }

    /**
     * RN-35 en SQL: Lista para entregar y con más días de espera que el plazo. El estado se filtra con el
     * mismo cálculo que la lista de órdenes (RN-18). Con el plazo en 30, una orden lista hace 30 días
     * todavía no aparece: hace falta pasarlo (CA-34.2).
     *
     * @return Builder<Orden>
     */
    private function sinReclamar(Negocio $negocio): Builder
    {
        $limite = $this->reloj->hoy()->modify('-'.$negocio->dias_sin_reclamar.' days');

        return $this->ordenes
            ->conEstado(Orden::query(), EstadoDeOrden::ListaParaEntregar)
            ->whereNotNull('lista_en')
            ->whereDate('lista_en', '<', $limite);
    }
}
