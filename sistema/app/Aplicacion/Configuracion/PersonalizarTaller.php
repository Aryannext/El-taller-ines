<?php

declare(strict_types=1);

namespace App\Aplicacion\Configuracion;

use App\Modelos\Negocio;
use App\Modelos\Usuario;

/**
 * HU-38 · El nombre del taller y el de la usuaria, que antes ponía el instalador con un comando.
 * El del taller se ve en el panel y llega a los clientes en cada aviso (RN-46); el de la usuaria, en el saludo (RN-47).
 */
class PersonalizarTaller
{
    public function ejecutar(Usuario $usuaria, string $nombreDelTaller, string $nombreDeLaUsuaria): void
    {
        $negocio = $usuaria->negocio;
        $negocio->update(['nombre' => trim($nombreDelTaller)]);

        $usuaria->update(['nombre' => trim($nombreDeLaUsuaria)]);
    }

    public function negocio(Usuario $usuaria): Negocio
    {
        return $usuaria->negocio;
    }
}
