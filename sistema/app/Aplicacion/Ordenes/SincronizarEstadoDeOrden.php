<?php

declare(strict_types=1);

namespace App\Aplicacion\Ordenes;

use App\Dominio\Compartido\Reloj;
use App\Dominio\Ordenes\EstadoDeOrden;
use App\Dominio\Ordenes\OrdenQuedoLista;
use App\Modelos\Orden;
use App\Modelos\Prenda;
use Illuminate\Support\Facades\DB;

/**
 * RN-18 y RN-22: después de cualquier cambio en las prendas, recalcula el estado de la orden y registra o borra la fecha en que quedó lista.
 * La llaman los casos de uso que cambian prendas, dentro de su transacción. HU-30 agrega descartar los avisos pendientes cuando la orden
 * deja de estar lista (RN-39); hoy todavía no se generan avisos.
 */
class SincronizarEstadoDeOrden
{
    public function __construct(private readonly Reloj $reloj) {}

    public function sincronizar(Orden $orden): EstadoDeOrden
    {
        $estado = EstadoDeOrden::calcular($orden->prendas()->get()->map(fn (Prenda $prenda) => $prenda->estado), $orden->cancelada_en !== null);

        if ($estado === EstadoDeOrden::ListaParaEntregar && $orden->lista_en === null) {
            $listaEn = $this->reloj->ahora();
            $orden->update(['lista_en' => $listaEn]);
            // El evento sale solo si la transacción se confirma (docs/04-especificacion-tecnica/05-avisos-fotos-y-reloj.md)
            DB::afterCommit(fn () => event(new OrdenQuedoLista($orden->id, $listaEn)));
        } elseif ($estado === EstadoDeOrden::EnProceso && $orden->lista_en !== null) {
            // Si vuelve a En proceso, la fecha se borra; si se entrega, se conserva
            $orden->update(['lista_en' => null]);
        }

        return $estado;
    }
}
