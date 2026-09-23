<?php

declare(strict_types=1);

namespace App\Aplicacion\Ordenes;

use App\Dominio\Compartido\ReglaIncumplida;
use App\Dominio\Fotos\AlmacenDeFotos;
use App\Dominio\Ordenes\TransicionesDePrenda;
use App\Dominio\Pagos\CalculadoraDeSaldo;
use App\Dominio\Pagos\Dinero;
use App\Dominio\Pagos\ReglasDeValor;
use App\Modelos\Orden;
use App\Modelos\Pago;
use App\Modelos\Prenda;
use Illuminate\Support\Facades\DB;

/**
 * HU-13 · Eliminar una prenda que se registró por error, para que la orden refleje lo que el cliente dejó.
 * No se elimina la única prenda de la orden (RN-06), ni una ya entregada o devuelta (RN-15), ni si el valor
 * quedaría por debajo de lo pagado (RN-16). Sus fotos se borran con ella (05-avisos-fotos-y-reloj).
 */
class EliminarPrenda
{
    public function __construct(
        private readonly TransicionesDePrenda $transiciones,
        private readonly AlmacenDeFotos $almacenDeFotos,
        private readonly CalculadoraDeSaldo $calculadora,
        private readonly ReglasDeValor $reglasDeValor,
        private readonly SincronizarEstadoDeOrden $sincronizarEstadoDeOrden,
    ) {}

    /**
     * Lanza la regla que impide eliminar la prenda, si la hay. La usan PT-13 y el envío.
     */
    public function exigirQueSePuedaEliminar(Prenda $prenda, Orden $orden): void
    {
        if ($orden->cancelada_en !== null) {
            throw new ReglaIncumplida('RN-24', 'Esta orden está cancelada y no admite cambios.');
        }

        // RN-15: lo que ya salió del taller no se borra, porque es historia del negocio
        if (! $this->transiciones->puedeModificarse($prenda->estado)) {
            throw new ReglaIncumplida('RN-15', 'Esta prenda ya fue '.mb_strtolower($prenda->estado->etiqueta()).' y no se puede modificar.');
        }

        // RN-06: una orden sin prendas no existe; si el cliente ya no quiere el arreglo, lo que cabe es cancelarla (RN-24)
        if ($orden->prendas->count() <= 1) {
            throw new ReglaIncumplida(
                'RN-06',
                'No puedes eliminar la única prenda de la orden. Si el cliente ya no quiere el arreglo, cancela la orden.',
            );
        }

        $this->reglasDeValor->exigirValorNoMenorQuePagado($this->valorSinLaPrenda($prenda, $orden), $this->pagado($orden));
    }

    /**
     * El valor que tendría la orden sin esta prenda: lo que muestra PT-13 antes de confirmar.
     */
    public function valorSinLaPrenda(Prenda $prenda, Orden $orden): Dinero
    {
        return $this->calculadora->valor(
            $orden->prendas
                ->reject(fn (Prenda $otra) => $otra->is($prenda))
                ->map(fn (Prenda $otra) => [$otra->precio, $otra->estado])
        );
    }

    public function ejecutar(Prenda $prenda, Orden $orden): void
    {
        $rutasDeFotos = DB::transaction(function () use ($prenda, $orden): array {
            // Bloquear la orden: un pago o una prenda nueva al mismo tiempo no se cuelan entre la revisión y el borrado
            $orden = Orden::whereKey($orden->id)->lockForUpdate()->firstOrFail();
            $prenda->refresh();
            $orden->load('prendas', 'pagos');
            $this->exigirQueSePuedaEliminar($prenda, $orden);

            // Las fotos se borran de la base con la prenda; sus archivos, después de confirmar la transacción
            $rutas = $prenda->fotos()->pluck('ruta')->all();
            $prenda->delete();

            $orden->load('prendas');
            $this->sincronizarEstadoDeOrden->sincronizar($orden);

            return $rutas;
        });

        foreach ($rutasDeFotos as $ruta) {
            $this->almacenDeFotos->eliminar($ruta);
        }
    }

    private function pagado(Orden $orden): Dinero
    {
        return $this->calculadora->pagado($orden->pagos->map(fn (Pago $pago) => [$pago->valor, $pago->anulado_en !== null]));
    }
}
