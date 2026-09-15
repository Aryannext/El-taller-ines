<?php

declare(strict_types=1);

namespace App\Aplicacion\Ordenes;

use App\Dominio\Compartido\ReglaIncumplida;
use App\Dominio\Compartido\Reloj;
use App\Dominio\Ordenes\EstadoDeOrden;
use App\Modelos\Orden;
use App\Modelos\Prenda;
use Illuminate\Support\Facades\DB;

/**
 * HU-22 · Cancelar la orden de un cliente que desiste. Conserva sus pagos y no se reabre (RN-24); su número no se reutiliza (RN-08).
 */
class CancelarOrden
{
    public function __construct(private readonly Reloj $reloj) {}

    /**
     * Lanza la regla que impide cancelar la orden, si la hay (CA-22.2).
     */
    public function exigirQueSePuedaCancelar(Orden $orden): void
    {
        $estado = EstadoDeOrden::calcular($orden->prendas->map(fn (Prenda $prenda) => $prenda->estado), $orden->cancelada_en !== null);

        if ($estado === EstadoDeOrden::Cancelada) {
            throw new ReglaIncumplida('RN-24', 'Esta orden está cancelada y no admite cambios.');
        }

        if ($estado === EstadoDeOrden::Entregada) {
            throw new ReglaIncumplida('RN-24', 'Una orden entregada no se puede cancelar.');
        }
    }

    public function ejecutar(Orden $orden): void
    {
        DB::transaction(function () use ($orden): void {
            // Bloquear la orden: una entrega o un pago al mismo tiempo no la dejan cancelada y entregada a la vez
            $bloqueada = Orden::whereKey($orden->id)->lockForUpdate()->firstOrFail();
            $this->exigirQueSePuedaCancelar($bloqueada);

            $bloqueada->update(['cancelada_en' => $this->reloj->ahora()]);
        });
    }
}
