<?php

declare(strict_types=1);

namespace App\Aplicacion\Clientes;

use App\Dominio\Clientes\Celular;
use App\Modelos\Cliente;

/**
 * HU-06 · Corregir los datos de un cliente. El celular corregido es el destino de los avisos siguientes (M-03).
 */
class CorregirCliente
{
    public function ejecutar(Cliente $cliente, string $nombre, Celular $celular): Cliente
    {
        $cliente->update([
            'nombre' => $nombre,
            'celular' => $celular->valor(),
        ]);

        return $cliente;
    }
}
