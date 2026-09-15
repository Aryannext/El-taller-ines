<?php

namespace App\Http\Controladores;

use App\Aplicacion\Configuracion\CambiarContrasena;
use App\Http\Solicitudes\ContrasenaRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * PT-23 · Ajustes. Por ahora solo la contraseña (HU-02); el plazo (HU-35) y los tipos de prenda (HU-16) llegan después.
 */
class AjustesController
{
    public function mostrar(): View
    {
        return view('pantallas.pt-23-ajustes');
    }

    public function cambiarContrasena(ContrasenaRequest $solicitud, CambiarContrasena $cambiarContrasena): RedirectResponse
    {
        $cambiarContrasena->ejecutar($solicitud->user(), $solicitud->validated('contrasena_nueva'));

        return redirect()->route('ajustes')
            ->with('exito', 'Tu contraseña cambió. Se cerró la sesión en los demás dispositivos.');
    }
}
