<?php

declare(strict_types=1);

namespace App\Aplicacion\Pagos;

use App\Dominio\Compartido\ReglaIncumplida;
use App\Dominio\Compartido\Reloj;
use App\Dominio\Pagos\CalculadoraDeSaldo;
use App\Dominio\Pagos\Dinero;
use App\Modelos\MetodoPago;
use App\Modelos\Orden;
use App\Modelos\Pago;
use App\Modelos\Prenda;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * HU-23 · Registrar un pago o abono. El saldo no se guarda: se recalcula con los pagos (RN-27).
 */
class RegistrarPago
{
    public function __construct(
        private readonly CalculadoraDeSaldo $calculadora,
        private readonly Reloj $reloj,
    ) {}

    /**
     * Los métodos que se ofrecen en PT-14: los activos del negocio, en el orden en que se agregaron (RN-25).
     *
     * @return Collection<int, MetodoPago>
     */
    public function metodosDePago(): Collection
    {
        return MetodoPago::where('activo', true)->orderBy('id')->get();
    }

    /**
     * RN-30: una orden entregada sigue recibiendo pagos; una cancelada, no.
     */
    public function exigirQueRecibaPagos(Orden $orden): void
    {
        if ($orden->cancelada_en !== null) {
            throw new ReglaIncumplida('RN-30', 'No se pueden registrar pagos en una orden cancelada.');
        }
    }

    public function ejecutar(Orden $orden, int $valor, int $metodoPagoId, string $token): Pago
    {
        // RNF-14: el mismo formulario enviado dos veces responde con el pago que ya se guardó
        $yaGuardado = Pago::where('token_formulario', $token)->first();
        if ($yaGuardado !== null) {
            return $yaGuardado;
        }

        if ($valor < 1) {
            throw new ReglaIncumplida('RN-25', 'El valor debe ser mayor que cero.', 'valor');
        }
        $pago = Dinero::pesos($valor);

        try {
            return DB::transaction(function () use ($orden, $pago, $metodoPagoId, $token): Pago {
                // RN-28: se bloquea la orden y se compara con el saldo al guardar, no con el que mostraba la pantalla
                $bloqueada = Orden::whereKey($orden->id)->lockForUpdate()->firstOrFail();
                $this->exigirQueRecibaPagos($bloqueada);

                // RN-01 y RN-25: un método activo de este negocio; el filtro global deja fuera los de otros
                if (! MetodoPago::whereKey($metodoPagoId)->where('activo', true)->exists()) {
                    throw new ReglaIncumplida('RN-25', 'Elige cómo pagó.', 'metodo_pago_id');
                }

                $saldo = $this->saldo($bloqueada);
                if ($pago->esMayorQue($saldo)) {
                    throw new ReglaIncumplida('RN-28', "El pago no puede superar el saldo pendiente de {$saldo->formato()}", 'valor');
                }

                return $bloqueada->pagos()->create([
                    'metodo_pago_id' => $metodoPagoId,
                    'valor' => $pago->valor(),
                    // La fecha y hora del pago son las del registro, en Colombia (RN-09, RF-26)
                    'pagado_en' => $this->reloj->ahora(),
                    'token_formulario' => $token,
                ]);
            });
        } catch (UniqueConstraintViolationException $error) {
            // Dos envíos llegaron al mismo tiempo: la clave única del token es la segunda barrera (RNF-14)
            return Pago::where('token_formulario', $token)->firstOr(fn () => throw $error);
        }
    }

    private function saldo(Orden $orden): Dinero
    {
        $valor = $this->calculadora->valor($orden->prendas()->get()->map(fn (Prenda $prenda) => [$prenda->precio, $prenda->estado]));

        return $this->calculadora->saldo($valor, $orden->pagos()->get()->map(fn (Pago $pago) => [$pago->valor, $pago->anulado_en !== null]));
    }
}
