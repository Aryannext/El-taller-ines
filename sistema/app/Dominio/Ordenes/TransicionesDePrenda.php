<?php

declare(strict_types=1);

namespace App\Dominio\Ordenes;

/**
 * Qué se permite hacer con una prenda según su estado. Por ahora RN-15; HU-20 agrega los cambios de estado (RN-13) y HU-36 la devolución.
 */
final class TransicionesDePrenda
{
    /**
     * RN-15: una prenda Entregada o Devuelta no se edita, no se elimina y no cambia de estado.
     */
    public function puedeModificarse(EstadoDePrenda $estado): bool
    {
        return ! in_array($estado, [EstadoDePrenda::Entregada, EstadoDePrenda::Devuelta], true);
    }
}
