<?php

declare(strict_types=1);

namespace App\Aplicacion\Clientes;

use App\Dominio\Clientes\Celular;
use App\Modelos\Cliente;

/**
 * HU-03 · Registrar un cliente. El negocio lo asigna PerteneceANegocio desde la sesión (RN-01),
 * y el celular puede repetirse entre clientes (RN-04).
 */
class RegistrarCliente
{
    public function ejecutar(string $nombre, Celular $celular): Cliente
    {
        return Cliente::create([
            'nombre' => $nombre,
            'celular' => $celular->valor(),
        ]);
    }
}
