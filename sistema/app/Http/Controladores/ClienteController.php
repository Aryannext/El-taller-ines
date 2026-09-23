<?php

namespace App\Http\Controladores;

use App\Aplicacion\Clientes\CorregirCliente;
use App\Aplicacion\Clientes\RegistrarCliente;
use App\Aplicacion\Consultas\BuscarClientes;
use App\Aplicacion\Consultas\FichaDeCliente;
use App\Http\Solicitudes\ClienteRequest;
use App\Modelos\Cliente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClienteController
{
    /** Lo escrito en la orden mientras se registra el cliente nuevo (HU-10). */
    public const ORDEN_EN_CURSO = 'orden_en_curso';

    public function buscar(Request $solicitud, BuscarClientes $buscarClientes): View
    {
        $busqueda = is_string($solicitud->query('q')) ? trim($solicitud->query('q')) : '';

        return view('pantallas.pt-03-clientes', [
            'busqueda' => $busqueda,
            'clientes' => $buscarClientes->listar($busqueda),
        ]);
    }

    public function nuevo(Request $solicitud): View
    {
        return view('pantallas.pt-04-registrar-cliente', [
            'cliente' => null,
            // HU-10: se llegó desde una orden a medio llenar, y lo escrito espera en la sesión
            'desdeLaOrden' => $solicitud->session()->has(self::ORDEN_EN_CURSO),
        ]);
    }

    /**
     * HU-10 · «El cliente es nuevo» manda aquí lo que ya se escribió en la orden. Se guarda tal cual, sin validar
     * —todavía está a medias—, y de ahí se sigue a PT-04 (02-rutas). Las fotos no se conservan: un archivo no cabe
     * en la sesión, y PT-06 lo advierte.
     */
    public function desdeOrden(Request $solicitud): RedirectResponse
    {
        $solicitud->session()->put(
            self::ORDEN_EN_CURSO,
            $solicitud->except(['_token', 'cliente_id']),
        );

        return redirect()->route('clientes.nuevo');
    }

    public function guardar(ClienteRequest $solicitud, RegistrarCliente $registrarCliente): RedirectResponse
    {
        $cliente = $registrarCliente->ejecutar($solicitud->validated('nombre'), $solicitud->celular());

        // HU-10: se venía de una orden a medio llenar, así que se vuelve a ella con el cliente ya elegido (CA-10.1)
        if ($solicitud->input('desde') === 'orden' && $solicitud->session()->has(self::ORDEN_EN_CURSO)) {
            return redirect()->route('ordenes.nueva', ['cliente' => $cliente->id]);
        }

        return redirect()->route('clientes.ficha', $cliente);
    }

    /**
     * PT-05 · Los datos del cliente, sus órdenes y cuánto debe (HU-05).
     */
    public function ficha(Cliente $cliente, FichaDeCliente $fichaDeCliente): View
    {
        return view('pantallas.pt-05-ficha-cliente', $fichaDeCliente->obtener($cliente));
    }

    public function editar(Cliente $cliente): View
    {
        return view('pantallas.pt-04-registrar-cliente', ['cliente' => $cliente, 'desdeLaOrden' => false]);
    }

    public function corregir(ClienteRequest $solicitud, Cliente $cliente, CorregirCliente $corregirCliente): RedirectResponse
    {
        $corregirCliente->ejecutar($cliente, $solicitud->validated('nombre'), $solicitud->celular());

        return redirect()->route('clientes.ficha', $cliente)
            ->with('exito', "Los datos de {$cliente->nombre} quedaron actualizados.");
    }
}
