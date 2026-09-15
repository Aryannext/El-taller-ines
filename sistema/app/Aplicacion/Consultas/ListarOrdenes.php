<?php

declare(strict_types=1);

namespace App\Aplicacion\Consultas;

use App\Dominio\Ordenes\EstadoDeOrden;
use App\Dominio\Ordenes\EstadoDePrenda;
use App\Dominio\Ordenes\NumeroDeOrden;
use App\Dominio\Pagos\CalculadoraDeSaldo;
use App\Dominio\Pagos\Dinero;
use App\Dominio\Pagos\EstadoDePago;
use App\Modelos\Orden;
use App\Modelos\Pago;
use App\Modelos\Prenda;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * HU-15 · Las órdenes del negocio por estado de avance, y la orden de un número de bolsa (RF-15).
 * Solo las del negocio de la sesión (RN-01).
 */
class ListarOrdenes
{
    public const POR_PAGINA = 30;

    public function __construct(private readonly CalculadoraDeSaldo $calculadora) {}

    /**
     * CA-15.2 y CA-15.3: la orden con ese número, o null si el negocio no tiene una.
     */
    public function buscarPorNumero(NumeroDeOrden $numero): ?Orden
    {
        return Orden::where('numero', $numero->valor())->first();
    }

    /**
     * @return LengthAwarePaginator<int, array{orden: Orden, numero: string, saldo: Dinero, estadoDePago: ?EstadoDePago}>
     */
    public function listar(EstadoDeOrden $estado, int $pagina = 1): LengthAwarePaginator
    {
        // RNF-01: las prendas y los pagos de la página se cargan juntos, no uno por orden
        $consulta = $this->conEstado(Orden::query(), $estado)
            ->with(['cliente', 'prendas' => fn ($prendas) => $prendas->orderBy('id'), 'prendas.tipoPrenda', 'pagos']);

        match ($estado) {
            // Primero la que hay que entregar antes
            EstadoDeOrden::EnProceso => $consulta->orderBy('fecha_entrega_acordada')->orderBy('numero'),
            // Primero la que quedó lista más recientemente, como en el mockup de PT-08
            EstadoDeOrden::ListaParaEntregar => $consulta->orderByDesc('lista_en')->orderByDesc('numero'),
            EstadoDeOrden::Entregada, EstadoDeOrden::Cancelada => $consulta->orderByDesc('numero'),
        };

        return $consulta->paginate(self::POR_PAGINA, ['*'], 'pagina', $pagina)
            ->appends(['estado' => $estado->value])
            ->through(fn (Orden $orden) => $this->fila($orden, $estado));
    }

    public function contar(EstadoDeOrden $estado): int
    {
        return $this->conEstado(Orden::query(), $estado)->count();
    }

    /**
     * RN-18 en SQL: el mismo cálculo de EstadoDeOrden::calcular, para filtrar sin traer todas las órdenes.
     * Las prendas Devueltas no cuentan (RN-44). ListarOrdenesTest comprueba que los dos coinciden.
     *
     * @param  Builder<Orden>  $consulta
     * @return Builder<Orden>
     */
    public function conEstado(Builder $consulta, EstadoDeOrden $estado): Builder
    {
        if ($estado === EstadoDeOrden::Cancelada) {
            return $consulta->whereNotNull('cancelada_en');
        }

        $porResolver = [EstadoDePrenda::Pendiente->value, EstadoDePrenda::EnProceso->value];
        $conEstados = fn (array $estados) => fn (Builder $prendas) => $prendas->whereIn('estado', $estados);
        $consulta->whereNull('cancelada_en');

        return match ($estado) {
            EstadoDeOrden::EnProceso => $consulta->whereHas('prendas', $conEstados($porResolver)),
            EstadoDeOrden::ListaParaEntregar => $consulta
                ->whereDoesntHave('prendas', $conEstados($porResolver))
                ->whereHas('prendas', $conEstados([EstadoDePrenda::Terminada->value])),
            EstadoDeOrden::Entregada => $consulta
                ->whereDoesntHave('prendas', $conEstados([...$porResolver, EstadoDePrenda::Terminada->value]))
                ->whereHas('prendas', $conEstados([EstadoDePrenda::Entregada->value])),
        };
    }

    /**
     * @return array{orden: Orden, numero: string, saldo: Dinero, estadoDePago: ?EstadoDePago}
     */
    private function fila(Orden $orden, EstadoDeOrden $estado): array
    {
        $valor = $this->calculadora->valor($orden->prendas->map(fn (Prenda $prenda) => [$prenda->precio, $prenda->estado]));
        $saldo = $this->calculadora->saldo($valor, $orden->pagos->map(fn (Pago $pago) => [$pago->valor, $pago->anulado_en !== null]));

        return [
            'orden' => $orden,
            'numero' => NumeroDeOrden::desde($orden->numero)->formato(),
            'saldo' => $saldo,
            // Una orden cancelada no se cobra: no está Pagada ni Por cobrar
            'estadoDePago' => $estado === EstadoDeOrden::Cancelada ? null : $this->calculadora->estadoDePago($saldo),
        ];
    }
}
