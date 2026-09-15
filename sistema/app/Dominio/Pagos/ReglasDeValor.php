<?php

declare(strict_types=1);

namespace App\Dominio\Pagos;

use App\Dominio\Compartido\ReglaIncumplida;

/**
 * Lo pagado nunca supera el valor de la orden. En la versión 1, bajar un precio dejaba el saldo negativo (F-02).
 */
final class ReglasDeValor
{
    /**
     * RN-16: corregir, eliminar o devolver una prenda no puede dejar la orden valiendo menos de lo que ya se pagó.
     */
    public function exigirValorNoMenorQuePagado(Dinero $nuevoValor, Dinero $pagado): void
    {
        if ($pagado->esMayorQue($nuevoValor)) {
            throw new ReglaIncumplida(
                'RN-16',
                "La orden quedaría valiendo {$nuevoValor->formato()} y ya tiene {$pagado->formato()} pagados. Primero anula el pago que sobra.",
                'precio',
            );
        }
    }
}
