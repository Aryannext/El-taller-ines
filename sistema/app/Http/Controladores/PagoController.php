<?php

namespace App\Http\Controladores;

use App\Aplicacion\Consultas\DetalleDeOrden;
use App\Aplicacion\Pagos\AnularPago;
use App\Aplicacion\Pagos\RegistrarPago;
use App\Dominio\Compartido\ReglaIncumplida;
use App\Dominio\Compartido\Reloj;
use App\Dominio\Pagos\Dinero;
use App\Http\Solicitudes\AnulacionRequest;
use App\Http\Solicitudes\PagoRequest;
use App\Modelos\Orden;
use App\Modelos\Pago;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Pagos de una orden. No hay ninguna acción para borrar un pago: se anula (RN-31).
 */
class PagoController
{
    /**
     * PT-14 · Registrar un pago o abono (HU-23): una hoja sobre la orden, con el saldo de ese momento.
     */
    public function nuevo(Orden $orden, RegistrarPago $registrarPago, DetalleDeOrden $detalleDeOrden, Reloj $reloj): View|RedirectResponse
    {
        try {
            $registrarPago->exigirQueRecibaPagos($orden);
        } catch (ReglaIncumplida $regla) {
            return $this->alDetalleConElMotivo($orden, $regla->mensajeParaUsuaria);
        }

        $detalle = $detalleDeOrden->obtener($orden);
        if ($detalle['saldo']->valor() === 0) {
            return $this->alDetalleConElMotivo($orden, 'Esta orden ya está pagada.');
        }

        return view('pantallas.pt-14-registrar-pago', [
            ...$detalle,
            'metodos' => $registrarPago->metodosDePago(),
            'hoy' => $reloj->hoy(),
            // Identifica este envío: dos toques seguidos no registran dos pagos (RNF-14)
            'token' => (string) Str::uuid(),
        ]);
    }

    public function guardar(PagoRequest $solicitud, Orden $orden, RegistrarPago $registrarPago): RedirectResponse
    {
        try {
            $pago = $registrarPago->ejecutar(
                $orden,
                (int) $solicitud->validated('valor'),
                (int) $solicitud->validated('metodo_pago_id'),
                $solicitud->validated('token_formulario'),
            );
        } catch (ReglaIncumplida $regla) {
            // RN-28 se muestra junto al valor; RN-30 vuelve al detalle de la orden
            return $regla->campo !== null
                ? back()->withInput()->withErrors([$regla->campo => $regla->mensajeParaUsuaria])
                : $this->alDetalleConElMotivo($orden, $regla->mensajeParaUsuaria);
        }

        return redirect()->route('ordenes.detalle', $orden)
            ->with('exito', 'El pago de '.Dinero::pesos($pago->valor)->formato().' quedó registrado.');
    }

    /**
     * PT-15 · ¿Anular este pago? (HU-25). Muestra cómo queda el saldo y pide el motivo.
     */
    public function confirmarAnulacion(Orden $orden, Pago $pago, AnularPago $anularPago, DetalleDeOrden $detalleDeOrden): View|RedirectResponse
    {
        try {
            $anularPago->exigirQueSePuedaAnular($pago);
        } catch (ReglaIncumplida $regla) {
            return $this->alDetalleConElMotivo($orden, $regla->mensajeParaUsuaria);
        }

        $detalle = $detalleDeOrden->obtener($orden);

        return view('pantallas.pt-15-anular-pago', [
            ...$detalle,
            'pago' => $pago,
            'saldoDespues' => $anularPago->saldoDespues($detalle['saldo'], $pago),
        ]);
    }

    public function anular(AnulacionRequest $solicitud, Orden $orden, Pago $pago, AnularPago $anularPago): RedirectResponse
    {
        // RNF-10: sin la confirmación nada cambia y se vuelve a preguntar
        if ($solicitud->input('confirmacion') !== 'si') {
            return redirect()->route('pagos.confirmar-anulacion', [$orden, $pago])->withInput();
        }

        try {
            $anularPago->ejecutar($pago, $solicitud->validated('motivo_anulacion'));
        } catch (ReglaIncumplida $regla) {
            return $regla->campo !== null
                ? back()->withInput()->withErrors([$regla->campo => $regla->mensajeParaUsuaria])
                : $this->alDetalleConElMotivo($orden, $regla->mensajeParaUsuaria);
        }

        return redirect()->route('ordenes.detalle', $orden)
            ->with('exito', 'El pago de '.Dinero::pesos($pago->valor)->formato().' quedó anulado.');
    }

    private function alDetalleConElMotivo(Orden $orden, string $motivo): RedirectResponse
    {
        return redirect()->route('ordenes.detalle', $orden)->withErrors(['orden' => $motivo]);
    }
}
