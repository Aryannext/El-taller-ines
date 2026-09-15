<?php

declare(strict_types=1);

namespace App\Aplicacion\Ordenes;

use App\Dominio\Compartido\ReglaIncumplida;
use App\Dominio\Ordenes\TransicionesDePrenda;
use App\Dominio\Pagos\CalculadoraDeSaldo;
use App\Dominio\Pagos\Dinero;
use App\Dominio\Pagos\ReglasDeValor;
use App\Modelos\Orden;
use App\Modelos\Pago;
use App\Modelos\Prenda;
use Illuminate\Support\Facades\DB;

/**
 * HU-12 · Corregir la descripción o el precio de una prenda. El valor y el saldo de la orden no se guardan: se recalculan solos (RN-26, RN-27).
 */
class CorregirPrenda
{
    public function __construct(
        private readonly TransicionesDePrenda $transiciones,
        private readonly ReglasDeValor $reglasDeValor,
        private readonly CalculadoraDeSaldo $calculadora,
    ) {}

    /**
     * Lanza la regla que impide corregir la prenda, si la hay. PT-13 lo pregunta antes de mostrar el formulario (CA-12.3).
     */
    public function exigirQueSePuedaCorregir(Prenda $prenda, Orden $orden): void
    {
        if ($orden->cancelada_en !== null) {
            throw new ReglaIncumplida('RN-24', 'Esta orden está cancelada y no admite cambios.');
        }

        if (! $this->transiciones->puedeModificarse($prenda->estado)) {
            throw new ReglaIncumplida('RN-15', 'Esta prenda ya fue '.mb_strtolower($prenda->estado->etiqueta()).' y no se puede modificar.');
        }
    }

    public function ejecutar(Prenda $prenda, string $descripcion, int $precio): Prenda
    {
        $nuevoPrecio = Dinero::precio($precio);

        return DB::transaction(function () use ($prenda, $descripcion, $nuevoPrecio): Prenda {
            // Bloquear la orden: un pago o un cambio de estado al mismo tiempo no se cuela entre la revisión y el guardado (RN-16)
            $orden = Orden::whereKey($prenda->orden_id)->lockForUpdate()->firstOrFail();
            $prenda->refresh();
            $this->exigirQueSePuedaCorregir($prenda, $orden);

            $nuevoValor = $this->calculadora->valor($orden->prendas()->get()->map(fn (Prenda $otra) => [
                $otra->is($prenda) ? $nuevoPrecio->valor() : $otra->precio,
                $otra->estado,
            ]));
            $pagado = $this->calculadora->pagado($orden->pagos()->get()->map(fn (Pago $pago) => [$pago->valor, $pago->anulado_en !== null]));
            $this->reglasDeValor->exigirValorNoMenorQuePagado($nuevoValor, $pagado);

            $prenda->update(['descripcion_arreglo' => $descripcion, 'precio' => $nuevoPrecio->valor()]);

            return $prenda;
        });
    }
}
