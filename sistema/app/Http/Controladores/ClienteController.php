<?php

namespace App\Http\Controladores;

use App\Aplicacion\Clientes\RegistrarCliente;
use App\Http\Solicitudes\ClienteRequest;
use App\Modelos\Cliente;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ClienteController
{
    public function nuevo(): View
    {
        return view('pantallas.pt-04-registrar-cliente');
    }

    public function guardar(ClienteRequest $solicitud, RegistrarCliente $registrarCliente): RedirectResponse
    {
        $cliente = $registrarCliente->ejecutar($solicitud->validated('nombre'), $solicitud->celular());

        return redirect()->route('clientes.ficha', $cliente);
    }

    /**
     * PT-05 · Por ahora los datos del cliente; sus órdenes y lo que debe llegan con HU-05.
     */
    public function ficha(Cliente $cliente): View
    {
        return view('pantallas.pt-05-ficha-cliente', ['cliente' => $cliente]);
    }
}
