<?php

declare(strict_types=1);

namespace App\Aplicacion\Ordenes;

use App\Dominio\Compartido\ReglaIncumplida;
use App\Dominio\Compartido\Reloj;
use App\Dominio\Ordenes\EstadoDePrenda;
use App\Dominio\Pagos\CalculadoraDeSaldo;
use App\Dominio\Pagos\Dinero;
use App\Dominio\Pagos\ReglasDeValor;
use App\Modelos\Orden;
use App\Modelos\Pago;
use App\Modelos\Prenda;
use Illuminate\Support\Facades\DB;

/**
 * HU-36 · El cliente se lleva una prenda sin arreglar. Queda Devuelta, se registra la fecha y su precio deja de
 * contar en el valor de la orden (RN-44, RN-26). El estado de la orden se recalcula solo (RN-18, RN-19).
 */
class DevolverPrendaSinArreglar
{
    public function __construct(
        private readonly Reloj $reloj,
        private readonly CalculadoraDeSaldo $calculadora,
        private readonly ReglasDeValor $reglasDeValor,
        private readonly SincronizarEstadoDeOrden $sincronizarEstadoDeOrden,
    ) {}

    /**
     * CA-36.3: solo las prendas Pendiente o En proceso se pueden devolver sin arreglar.
     */
    public function sePuedeDevolver(Prenda $prenda): bool
    {
        return in_array($prenda->estado, [EstadoDePrenda::Pendiente, EstadoDePrenda::EnProceso], true);
    }

    /**
     * Lanza la regla que impide la devolución, si la hay. La usan la pantalla de confirmación y el envío.
     */
    public function exigirQueSePuedaDevolver(Prenda $prenda, Orden $orden): void
    {
        if ($orden->cancelada_en !== null) {
            throw new ReglaIncumplida('RN-24', 'Esta orden está cancelada y no admite cambios.');
        }

        if (! $this->sePuedeDevolver($prenda)) {
            throw new ReglaIncumplida(
                'RN-44',
                'Solo se devuelve sin arreglar una prenda que todavía está pendiente o en proceso.',
            );
        }

        // RN-44: si esta fuera la última por resolver, la orden entera quedaría devuelta, y eso es cancelarla (RN-24)
        if ($this->esLaUnicaPorResolver($prenda, $orden)) {
            throw new ReglaIncumplida(
                'RN-44',
                'Es la única prenda que queda por resolver: lo que corresponde es cancelar la orden.',
            );
        }

        // RN-16: la misma regla que al corregir el precio, con su mensaje (03-validaciones-y-mensajes)
        $this->reglasDeValor->exigirValorNoMenorQuePagado(
            $this->valorSinLaPrenda($prenda, $orden),
            $this->calculadora->pagado($orden->pagos->map(fn (Pago $pago) => [$pago->valor, $pago->anulado_en !== null])),
        );
    }

    /**
     * El valor que tendría la orden después de devolver esta prenda (lo que muestra PT-12).
     */
    public function valorSinLaPrenda(Prenda $prenda, Orden $orden): Dinero
    {
        return $this->calculadora->valor(
            $orden->prendas
                ->map(fn (Prenda $otra) => [
                    $otra->precio,
                    $otra->is($prenda) ? EstadoDePrenda::Devuelta : $otra->estado,
                ])
        );
    }

    public function ejecutar(Prenda $prenda, Orden $orden): void
    {
        DB::transaction(function () use ($prenda, $orden): void {
            // Bloquear la orden: sin esto, dos devoluciones a la vez podrían dejar la orden entera devuelta (RN-44)
            $orden = Orden::whereKey($orden->id)->lockForUpdate()->firstOrFail();
            $prenda->refresh();
            $orden->load('prendas', 'pagos');
            $this->exigirQueSePuedaDevolver($prenda, $orden);

            $prenda->update(['estado' => EstadoDePrenda::Devuelta, 'devuelta_en' => $this->reloj->ahora()]);

            $this->sincronizarEstadoDeOrden->sincronizar($orden);
        });
    }

    /**
     * Quedan por resolver las prendas que no están Devueltas: si esta es la única, la orden entera quedaría devuelta.
     */
    private function esLaUnicaPorResolver(Prenda $prenda, Orden $orden): bool
    {
        return $orden->prendas
            ->reject(fn (Prenda $otra) => $otra->is($prenda) || $otra->estado === EstadoDePrenda::Devuelta)
            ->isEmpty();
    }
}
