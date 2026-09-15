<?php

declare(strict_types=1);

namespace App\Dominio\Ordenes;

use App\Dominio\Compartido\ReglaIncumplida;

/**
 * Qué cambios de estado se permiten a una prenda (diagrama 4 de docs/03-diseno/diagramas). HU-36 agrega la devolución (RN-44).
 */
final class TransicionesDePrenda
{
    /**
     * Los estados a los que la dueña puede pasar una prenda desde su estado actual.
     * Entregada no está: se llega entregando la orden (RN-13, RN-20). Devuelta tampoco: es otra acción (RN-44).
     *
     * @return list<EstadoDePrenda>
     */
    public function permitidas(EstadoDePrenda $desde): array
    {
        return match ($desde) {
            EstadoDePrenda::Pendiente => [EstadoDePrenda::EnProceso, EstadoDePrenda::Terminada],
            EstadoDePrenda::EnProceso => [EstadoDePrenda::Terminada],
            // RN-14: un retoque al medírsela la devuelve a En proceso
            EstadoDePrenda::Terminada => [EstadoDePrenda::EnProceso],
            // RN-15
            EstadoDePrenda::Entregada, EstadoDePrenda::Devuelta => [],
        };
    }

    /**
     * Lanza la regla que impide pasar de un estado al otro, si la hay.
     */
    public function exigir(EstadoDePrenda $desde, EstadoDePrenda $hacia): void
    {
        if (! $this->puedeModificarse($desde)) {
            throw new ReglaIncumplida('RN-15', 'Esta prenda ya fue '.mb_strtolower($desde->etiqueta()).' y no se puede modificar.');
        }

        if ($hacia === EstadoDePrenda::Entregada) {
            if ($desde !== EstadoDePrenda::Terminada) {
                throw new ReglaIncumplida('RN-13', 'Una prenda solo se entrega cuando está Terminada.', 'estado');
            }

            return;
        }

        if (! in_array($hacia, $this->permitidas($desde), true)) {
            throw new ReglaIncumplida('RN-12', 'Elige uno de los estados que se muestran.', 'estado');
        }
    }

    /**
     * RN-15: una prenda Entregada o Devuelta no se edita, no se elimina y no cambia de estado.
     */
    public function puedeModificarse(EstadoDePrenda $estado): bool
    {
        return ! in_array($estado, [EstadoDePrenda::Entregada, EstadoDePrenda::Devuelta], true);
    }
}
