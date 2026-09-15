<?php

declare(strict_types=1);

namespace App\Dominio\Ordenes;

use DateTimeImmutable;

/**
 * Evento: la orden pasó a Lista para entregar (RN-22). Lo escuchará GenerarAviso para avisar al cliente (HU-28, RN-37).
 * Es del dominio: no implementa interfaces de Laravel. SincronizarEstadoDeOrden lo emite después de confirmar la transacción.
 */
final class OrdenQuedoLista
{
    public function __construct(
        public readonly int $ordenId,
        public readonly DateTimeImmutable $listaEn,
    ) {}
}
