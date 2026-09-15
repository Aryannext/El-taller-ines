<?php

declare(strict_types=1);

namespace App\Aplicacion\Configuracion;

use App\Modelos\Usuario;

/**
 * HU-02 · Cambiar mi contraseña.
 *
 * Basta con guardar el nuevo hash: el middleware auth.session lo compara en cada solicitud, así que cierra
 * las sesiones de los demás dispositivos y deja abierta la actual, a la que le guarda el hash nuevo.
 */
class CambiarContrasena
{
    public function ejecutar(Usuario $usuaria, string $nueva): void
    {
        // El cast «hashed» del modelo la guarda con bcrypt, nunca en texto plano (RNF-19)
        $usuaria->contrasena = $nueva;
        $usuaria->save();
    }
}
