<?php

declare(strict_types=1);

namespace App\Aplicacion\Pagos;

use App\Dominio\Compartido\ReglaIncumplida;
use App\Dominio\Compartido\Reloj;
use App\Dominio\Pagos\Dinero;
use App\Modelos\Orden;
use App\Modelos\Pago;
use Illuminate\Support\Facades\DB;

/**
 * HU-25 · Anular un pago mal registrado. Un pago no se borra: se anula con la fecha y el motivo y deja de contar en el saldo (RN-31).
 * No existe un caso de uso para borrar pagos.
 */
class AnularPago
{
    public function __construct(private readonly Reloj $reloj) {}

    /**
     * Un pago ya anulado no se anula otra vez (CA-25.3).
     */
    public function exigirQueSePuedaAnular(Pago $pago): void
    {
        if ($pago->anulado_en !== null) {
            throw new ReglaIncumplida('RN-31', 'Este pago ya está anulado.');
        }
    }

    /**
     * Lo que PT-15 muestra antes de confirmar: el pago deja de restar.
     */
    public function saldoDespues(Dinero $saldo, Pago $pago): Dinero
    {
        return $saldo->sumar(Dinero::pesos($pago->valor));
    }

    public function ejecutar(Pago $pago, string $motivo): Pago
    {
        $motivo = trim($motivo);
        if ($motivo === '') {
            throw new ReglaIncumplida('RN-31', 'Escribe el motivo de la anulación.', 'motivo_anulacion');
        }

        return DB::transaction(function () use ($pago, $motivo): Pago {
            // Bloquear la orden, como al registrar pagos: una anulación y un pago al mismo tiempo no descuadran el saldo (RN-27)
            Orden::whereKey($pago->orden_id)->lockForUpdate()->firstOrFail();
            $pago->refresh();
            $this->exigirQueSePuedaAnular($pago);

            $pago->update(['anulado_en' => $this->reloj->ahora(), 'motivo_anulacion' => $motivo]);

            return $pago;
        });
    }
}
