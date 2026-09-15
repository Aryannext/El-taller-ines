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
    public function buscar(Request $solicitud, BuscarClientes $buscarClientes): View
    {
        $busqueda = is_string($solicitud->query('q')) ? trim($solicitud->query('q')) : '';

        return view('pantallas.pt-03-clientes', [
            'busqueda' => $busqueda,
            'clientes' => $buscarClientes->listar($busqueda),
        ]);
    }

    public function nuevo(): View
    {
        return view('pantallas.pt-04-registrar-cliente', ['cliente' => null]);
    }

    public function guardar(ClienteRequest $solicitud, RegistrarCliente $registrarCliente): RedirectResponse
    {
        $cliente = $registrarCliente->ejecutar($solicitud->validated('nombre'), $solicitud->celular());

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
        return view('pantallas.pt-04-registrar-cliente', ['cliente' => $cliente]);
    }

    public function corregir(ClienteRequest $solicitud, Cliente $cliente, CorregirCliente $corregirCliente): RedirectResponse
    {
        $corregirCliente->ejecutar($cliente, $solicitud->validated('nombre'), $solicitud->celular());

        return redirect()->route('clientes.ficha', $cliente)
            ->with('exito', "Los datos de {$cliente->nombre} quedaron actualizados.");
    }
}
