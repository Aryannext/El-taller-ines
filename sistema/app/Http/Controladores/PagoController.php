<?php

namespace App\Http\Controladores;

use App\Aplicacion\Consultas\DetalleDeOrden;
use App\Aplicacion\Pagos\RegistrarPago;
use App\Dominio\Compartido\ReglaIncumplida;
use App\Dominio\Compartido\Reloj;
use App\Dominio\Pagos\Dinero;
use App\Http\Solicitudes\PagoRequest;
use App\Modelos\Orden;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

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
            return redirect()->route('ordenes.detalle', $orden)->withErrors(['orden' => $regla->mensajeParaUsuaria]);
        }

        $detalle = $detalleDeOrden->obtener($orden);
        if ($detalle['saldo']->valor() === 0) {
            return redirect()->route('ordenes.detalle', $orden)->withErrors(['orden' => 'Esta orden ya está pagada.']);
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
                : redirect()->route('ordenes.detalle', $orden)->withErrors(['orden' => $regla->mensajeParaUsuaria]);
        }

        return redirect()->route('ordenes.detalle', $orden)
            ->with('exito', 'El pago de '.Dinero::pesos($pago->valor)->formato().' quedó registrado.');
    }
}
