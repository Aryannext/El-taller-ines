<?php

declare(strict_types=1);

namespace App\Aplicacion\Ordenes;

use App\Dominio\Compartido\ReglaIncumplida;
use App\Dominio\Ordenes\EstadoDeOrden;
use App\Dominio\Ordenes\EstadoDePrenda;
use App\Dominio\Ordenes\TransicionesDePrenda;
use App\Modelos\Orden;
use App\Modelos\Prenda;
use Illuminate\Support\Facades\DB;

/**
 * HU-20 · Marcar en qué va cada prenda. La orden no se cambia a mano: se recalcula desde sus prendas (RN-18, RN-19).
 */
class CambiarEstadoDePrenda
{
    public function __construct(
        private readonly TransicionesDePrenda $transiciones,
        private readonly SincronizarEstadoDeOrden $sincronizarEstadoDeOrden,
    ) {}

    /**
     * Los estados que ofrece PT-11 (CA-20.2). Ninguno si la orden está cancelada (RN-24).
     *
     * @return list<EstadoDePrenda>
     */
    public function estadosPosibles(Prenda $prenda, Orden $orden): array
    {
        return $orden->cancelada_en !== null ? [] : $this->transiciones->permitidas($prenda->estado);
    }

    /**
     * Lanza la regla que impide cambiar la prenda, si la hay (CA-20.6, RN-15).
     */
    public function exigirQueSePuedaCambiar(Prenda $prenda, Orden $orden): void
    {
        if ($orden->cancelada_en !== null) {
            throw new ReglaIncumplida('RN-24', 'Esta orden está cancelada y no admite cambios.');
        }

        if (! $this->transiciones->puedeModificarse($prenda->estado)) {
            throw new ReglaIncumplida('RN-15', 'Esta prenda ya fue '.mb_strtolower($prenda->estado->etiqueta()).' y no se puede modificar.');
        }
    }

    /**
     * Devuelve el estado de la orden después del cambio.
     */
    public function ejecutar(Prenda $prenda, EstadoDePrenda $hacia): EstadoDeOrden
    {
        return DB::transaction(function () use ($prenda, $hacia): EstadoDeOrden {
            // Bloquear la orden: dos cambios al mismo tiempo no dejan la fecha en que quedó lista a medias (RN-22)
            $orden = Orden::whereKey($prenda->orden_id)->lockForUpdate()->firstOrFail();
            $prenda->refresh();
            $this->exigirQueSePuedaCambiar($prenda, $orden);

            $this->transiciones->exigir($prenda->estado, $hacia);
            if ($hacia === EstadoDePrenda::Entregada) {
                // RN-20: a Entregada se llega entregando la orden, no desde la prenda sola
                throw new ReglaIncumplida('RN-20', 'Las prendas terminadas se entregan con el botón Entregar de la orden.', 'estado');
            }

            $prenda->update(['estado' => $hacia]);

            return $this->sincronizarEstadoDeOrden->sincronizar($orden);
        });
    }
}
