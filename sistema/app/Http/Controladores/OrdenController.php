<?php

namespace App\Http\Controladores;

use App\Aplicacion\Configuracion\GestionarTiposDePrenda;
use App\Aplicacion\Consultas\BuscarClientes;
use App\Aplicacion\Consultas\DetalleDeOrden;
use App\Aplicacion\Consultas\ListarOrdenes;
use App\Aplicacion\Ordenes\CancelarOrden;
use App\Aplicacion\Ordenes\EntregarOrden;
use App\Aplicacion\Ordenes\RegistrarOrden;
use App\Aplicacion\Pagos\RegistrarPago;
use App\Dominio\Compartido\ReglaIncumplida;
use App\Dominio\Compartido\Reloj;
use App\Dominio\Ordenes\EstadoDeOrden;
use App\Dominio\Ordenes\NumeroDeOrden;
use App\Http\Solicitudes\OrdenRequest;
use App\Modelos\Orden;
use DateTimeImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OrdenController
{
    /**
     * PT-08 · Órdenes por estado de avance (?estado=). Con ?numero= abre la orden de esa bolsa (HU-15).
     */
    public function listar(Request $solicitud, ListarOrdenes $listarOrdenes): View|RedirectResponse
    {
        $busqueda = is_string($solicitud->query('numero')) ? trim($solicitud->query('numero')) : '';
        $aviso = null;

        if ($busqueda !== '') {
            $numero = NumeroDeOrden::leer($busqueda);
            $orden = $numero === null ? null : $listarOrdenes->buscarPorNumero($numero);

            if ($orden !== null) {
                return redirect()->route('ordenes.detalle', $orden);
            }

            $aviso = $numero === null
                ? 'Escribe solo el número de la bolsa, por ejemplo 42.'
                : "No hay una orden con el número {$numero->formato()}.";
        }

        $estado = is_string($solicitud->query('estado')) ? EstadoDeOrden::tryFrom($solicitud->query('estado')) : null;
        $estado ??= EstadoDeOrden::EnProceso;
        $pagina = is_string($solicitud->query('pagina')) && ctype_digit($solicitud->query('pagina')) ? max(1, (int) $solicitud->query('pagina')) : 1;

        return view('pantallas.pt-08-ordenes', [
            'estado' => $estado,
            'busqueda' => $busqueda,
            'aviso' => $aviso,
            'ordenes' => $listarOrdenes->listar($estado, $pagina),
            'enProceso' => $listarOrdenes->contar(EstadoDeOrden::EnProceso),
            'listas' => $listarOrdenes->contar(EstadoDeOrden::ListaParaEntregar),
        ]);
    }

    /**
     * PT-06 · Nueva orden. Desde la ficha llega con ?cliente= para dejarlo elegido.
     */
    public function nueva(Request $solicitud, BuscarClientes $buscarClientes, GestionarTiposDePrenda $tiposDePrenda, RegistrarPago $registrarPago, Reloj $reloj): View
    {
        $cliente = $solicitud->query('cliente');

        // HU-10: se volvió de registrar al cliente nuevo; lo que estaba escrito vuelve al formulario (CA-10.1)
        $borrador = $solicitud->session()->get(ClienteController::ORDEN_EN_CURSO);
        if (is_array($borrador) && $solicitud->old() === []) {
            $solicitud->session()->flashInput($borrador);
        }

        return view('pantallas.pt-06-nueva-orden', [
            'clientes' => $buscarClientes->listar(''),
            'clienteElegido' => is_string($cliente) && ctype_digit($cliente) ? (int) $cliente : null,
            'tipos' => $tiposDePrenda->activos(),
            // HU-24: el abono al dejar la ropa se paga con uno de los métodos del negocio (RN-25)
            'metodos' => $registrarPago->metodosDePago(),
            'hoy' => $reloj->hoy(),
            // Identifica este envío: dos toques seguidos no registran dos órdenes (RNF-14)
            'token' => (string) Str::uuid(),
            'vengoDeRegistrarCliente' => is_array($borrador),
        ]);
    }

    public function guardar(OrdenRequest $solicitud, RegistrarOrden $registrarOrden): RedirectResponse
    {
        try {
            $orden = $registrarOrden->ejecutar(
                $solicitud->cliente(),
                new DateTimeImmutable($solicitud->validated('fecha_entrega_acordada')),
                $solicitud->prendas(),
                $solicitud->validated('token_formulario'),
                $solicitud->abono(),
            );
        } catch (ReglaIncumplida $regla) {
            // RN-28: el abono supera el valor de la orden. No queda ni la orden ni el pago (CA-24.2)
            return back()->withInput()->withErrors([$regla->campo ?? 'abono' => $regla->mensajeParaUsuaria]);
        }

        // Lo escrito ya está guardado en la orden: la sesión no tiene por qué seguir guardándolo (HU-10)
        $solicitud->session()->forget(ClienteController::ORDEN_EN_CURSO);

        return redirect()->route('ordenes.guardada', $orden);
    }

    /**
     * PT-07 · El número para escribir en la bolsa (HU-08).
     */
    public function guardada(Orden $orden, DetalleDeOrden $detalleDeOrden): View
    {
        return view('pantallas.pt-07-orden-guardada', $detalleDeOrden->obtener($orden));
    }

    /**
     * PT-09 · Detalle de la orden, con el número siempre arriba (CA-08.3). HU-14 agrega pagos y avisos.
     */
    public function detalle(Orden $orden, DetalleDeOrden $detalleDeOrden): View
    {
        return view('pantallas.pt-09-detalle-orden', $detalleDeOrden->obtener($orden));
    }

    /**
     * PT-16 · Entregar la orden (HU-21): qué sale del taller, qué se queda y, si hay saldo, cuánto se debe (RN-21).
     */
    public function confirmarEntrega(Orden $orden, EntregarOrden $entregarOrden, DetalleDeOrden $detalleDeOrden): View|RedirectResponse
    {
        // Primero el detalle: carga las prendas en orden para la hoja y para el fondo
        $detalle = $detalleDeOrden->obtener($orden);

        try {
            $entregarOrden->exigirQueSePuedaEntregar($orden);
        } catch (ReglaIncumplida $regla) {
            return redirect()->route('ordenes.detalle', $orden)->withErrors(['orden' => $regla->mensajeParaUsuaria]);
        }

        return view('pantallas.pt-16-entregar-orden', [
            ...$detalle,
            ...$entregarOrden->repartir($orden),
            'aviso' => $entregarOrden->avisoDeSaldo($orden, $detalle['saldo']),
        ]);
    }

    public function entregar(Request $solicitud, Orden $orden, EntregarOrden $entregarOrden): RedirectResponse
    {
        try {
            $resultado = $entregarOrden->ejecutar($orden, $solicitud->input('confirmacion') === 'si');
        } catch (ReglaIncumplida $regla) {
            // CA-21.5: con saldo y sin confirmar, nada cambia y se vuelve a mostrar cuánto se debe
            return $regla->regla === 'RN-21'
                ? redirect()->route('ordenes.confirmar-entrega', $orden)
                : redirect()->route('ordenes.detalle', $orden)->withErrors(['orden' => $regla->mensajeParaUsuaria]);
        }

        $mensaje = match (true) {
            $resultado['estado'] === EstadoDeOrden::Entregada => 'La orden quedó entregada.',
            $resultado['entregadas'] === 1 => 'Se entregó 1 prenda. La orden sigue en proceso.',
            default => "Se entregaron {$resultado['entregadas']} prendas. La orden sigue en proceso.",
        };

        return redirect()->route('ordenes.detalle', $orden)->with('exito', $mensaje);
    }

    /**
     * PT-17 · ¿Cancelar la orden? (HU-22): qué implica y que no se puede reabrir.
     */
    public function confirmarCancelacion(Orden $orden, CancelarOrden $cancelarOrden, DetalleDeOrden $detalleDeOrden): View|RedirectResponse
    {
        $detalle = $detalleDeOrden->obtener($orden);

        try {
            $cancelarOrden->exigirQueSePuedaCancelar($orden);
        } catch (ReglaIncumplida $regla) {
            return redirect()->route('ordenes.detalle', $orden)->withErrors(['orden' => $regla->mensajeParaUsuaria]);
        }

        return view('pantallas.pt-17-cancelar-orden', $detalle);
    }

    public function cancelar(Request $solicitud, Orden $orden, CancelarOrden $cancelarOrden): RedirectResponse
    {
        // RNF-10: sin la confirmación nada cambia y se vuelve a preguntar
        if ($solicitud->input('confirmacion') !== 'si') {
            return redirect()->route('ordenes.confirmar-cancelacion', $orden);
        }

        try {
            $cancelarOrden->ejecutar($orden);
        } catch (ReglaIncumplida $regla) {
            return redirect()->route('ordenes.detalle', $orden)->withErrors(['orden' => $regla->mensajeParaUsuaria]);
        }

        return redirect()->route('ordenes.detalle', $orden)
            ->with('exito', 'La orden '.NumeroDeOrden::desde($orden->numero)->formato().' quedó cancelada.');
    }
}
