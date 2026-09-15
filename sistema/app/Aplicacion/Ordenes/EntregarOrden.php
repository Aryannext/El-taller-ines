<?php

declare(strict_types=1);

namespace App\Aplicacion\Ordenes;

use App\Aplicacion\Consultas\DetalleDeOrden;
use App\Dominio\Compartido\ReglaIncumplida;
use App\Dominio\Compartido\Reloj;
use App\Dominio\Ordenes\EstadoDeOrden;
use App\Dominio\Ordenes\EstadoDePrenda;
use App\Dominio\Pagos\Dinero;
use App\Modelos\Orden;
use App\Modelos\Prenda;
use Illuminate\Support\Facades\DB;

/**
 * HU-21 · Entregar la orden al cliente. Se entregan las prendas Terminadas; si queda alguna sin terminar, la entrega es parcial (RN-20).
 */
class EntregarOrden
{
    public function __construct(
        private readonly Reloj $reloj,
        private readonly SincronizarEstadoDeOrden $sincronizarEstadoDeOrden,
        private readonly DetalleDeOrden $detalleDeOrden,
    ) {}

    /**
     * Lo que PT-16 muestra antes de confirmar: qué sale del taller y qué se queda. Las Devueltas y las ya Entregadas no aparecen.
     *
     * @return array{seEntregan: list<Prenda>, seQuedan: list<Prenda>}
     */
    public function repartir(Orden $orden): array
    {
        $prendas = $orden->prendas->sortBy('id');

        return [
            'seEntregan' => $prendas->filter(fn (Prenda $prenda) => $prenda->estado === EstadoDePrenda::Terminada)->values()->all(),
            'seQuedan' => $prendas->filter(fn (Prenda $prenda) => in_array($prenda->estado, [EstadoDePrenda::Pendiente, EstadoDePrenda::EnProceso], true))->values()->all(),
        ];
    }

    /**
     * Lanza la regla que impide entregar la orden, si la hay.
     */
    public function exigirQueSePuedaEntregar(Orden $orden): void
    {
        if ($orden->cancelada_en !== null) {
            throw new ReglaIncumplida('RN-24', 'Esta orden está cancelada y no admite cambios.');
        }

        if ($this->repartir($orden)['seEntregan'] === []) {
            throw new ReglaIncumplida('RN-20', 'Esta orden no tiene prendas terminadas para entregar.');
        }
    }

    /**
     * RN-21: lo que se muestra antes de entregar con saldo; null si no se debe nada.
     */
    public function avisoDeSaldo(Orden $orden, Dinero $saldo): ?string
    {
        if ($saldo->valor() === 0) {
            return null;
        }

        $nombre = strtok($orden->cliente->nombre, ' ') ?: $orden->cliente->nombre;

        return "{$nombre} debe {$saldo->formato()}. ¿Entregar de todos modos?";
    }

    /**
     * Con saldo, solo entrega si la usuaria confirmó después de ver cuánto se debe (RN-21).
     *
     * @return array{estado: EstadoDeOrden, entregadas: int}
     */
    public function ejecutar(Orden $orden, bool $confirmoElSaldo): array
    {
        return DB::transaction(function () use ($orden, $confirmoElSaldo): array {
            // Bloquear la orden: un pago o un cambio de prenda al mismo tiempo no cambian el saldo ni lo que se entrega (RN-27)
            $bloqueada = Orden::whereKey($orden->id)->lockForUpdate()->firstOrFail();
            $this->exigirQueSePuedaEntregar($bloqueada);

            // El saldo de este momento, no el que se vio en la pantalla
            $aviso = $this->avisoDeSaldo($bloqueada, $this->detalleDeOrden->obtener($bloqueada)['saldo']);
            if ($aviso !== null && ! $confirmoElSaldo) {
                throw new ReglaIncumplida('RN-21', $aviso);
            }

            // RN-23: cada prenda guarda la hora en que salió; la entrega de la orden es la de la última
            $ahora = $this->reloj->ahora();
            $seEntregan = $this->repartir($bloqueada)['seEntregan'];
            foreach ($seEntregan as $prenda) {
                $prenda->update(['estado' => EstadoDePrenda::Entregada, 'entregada_en' => $ahora]);
            }

            return [
                'estado' => $this->sincronizarEstadoDeOrden->sincronizar($bloqueada),
                'entregadas' => count($seEntregan),
            ];
        });
    }
}
