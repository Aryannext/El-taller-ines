<?php

namespace App\Http\Controladores;

use App\Aplicacion\Configuracion\GestionarTiposDePrenda;
use App\Aplicacion\Consultas\BuscarClientes;
use App\Aplicacion\Consultas\DetalleDeOrden;
use App\Aplicacion\Ordenes\RegistrarOrden;
use App\Dominio\Compartido\Reloj;
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
     * PT-06 · Nueva orden. Desde la ficha llega con ?cliente= para dejarlo elegido.
     */
    public function nueva(Request $solicitud, BuscarClientes $buscarClientes, GestionarTiposDePrenda $tiposDePrenda, Reloj $reloj): View
    {
        $cliente = $solicitud->query('cliente');

        return view('pantallas.pt-06-nueva-orden', [
            'clientes' => $buscarClientes->listar(''),
            'clienteElegido' => is_string($cliente) && ctype_digit($cliente) ? (int) $cliente : null,
            'tipos' => $tiposDePrenda->activos(),
            'hoy' => $reloj->hoy(),
            // Identifica este envío: dos toques seguidos no registran dos órdenes (RNF-14)
            'token' => (string) Str::uuid(),
        ]);
    }

    public function guardar(OrdenRequest $solicitud, RegistrarOrden $registrarOrden): RedirectResponse
    {
        $orden = $registrarOrden->ejecutar(
            $solicitud->cliente(),
            new DateTimeImmutable($solicitud->validated('fecha_entrega_acordada')),
            $solicitud->prendas(),
            $solicitud->validated('token_formulario'),
        );

        return redirect()->route('ordenes.guardada', $orden);
    }

    /**
     * PT-07 · El número para escribir en la bolsa (HU-08).
     */
    public function guardada(Orden $orden, DetalleDeOrden $detalleDeOrden): View
    {
        return view('pantallas.pt-07-orden-guardada', $detalleDeOrden->obtener($orden));
    }
}
