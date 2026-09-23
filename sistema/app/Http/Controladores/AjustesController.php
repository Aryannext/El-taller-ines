<?php

namespace App\Http\Controladores;

use App\Aplicacion\Configuracion\CambiarContrasena;
use App\Aplicacion\Configuracion\CambiarPlazoSinReclamar;
use App\Aplicacion\Configuracion\GestionarTiposDePrenda;
use App\Http\Solicitudes\AjustesRequest;
use App\Http\Solicitudes\ContrasenaRequest;
use App\Modelos\TipoPrenda;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * PT-23 · Ajustes: la contraseña (HU-02), el plazo sin reclamar (HU-35) y los tipos de prenda (HU-16).
 */
class AjustesController
{
    public function mostrar(Request $solicitud, GestionarTiposDePrenda $tiposDePrenda): View
    {
        return view('pantallas.pt-23-ajustes', [
            'plazo' => $solicitud->user()->negocio->dias_sin_reclamar,
            'tipos' => $tiposDePrenda->todos(),
        ]);
    }

    /**
     * HU-35 · Cambiar después de cuántos días una orden lista se considera sin reclamar (RN-35).
     */
    public function cambiarPlazo(AjustesRequest $solicitud, CambiarPlazoSinReclamar $cambiarPlazo): RedirectResponse
    {
        $cambiarPlazo->ejecutar($solicitud->user()->negocio, (int) $solicitud->validated('dias_sin_reclamar'));

        return redirect()->route('ajustes')
            ->with('exito', 'El plazo quedó en '.$solicitud->validated('dias_sin_reclamar').' días.');
    }

    /**
     * HU-16 · Renombrar un tipo de prenda: las prendas que ya lo usan muestran el nombre nuevo (CA-16.1).
     */
    public function renombrarTipo(AjustesRequest $solicitud, TipoPrenda $tipo, GestionarTiposDePrenda $tiposDePrenda): RedirectResponse
    {
        $tiposDePrenda->renombrar($tipo, $solicitud->validated('nombre'));

        return redirect()->route('ajustes')->with('exito', "El tipo de prenda ahora se llama «{$tipo->nombre}».");
    }

    /**
     * HU-16 · Dejar de usar un tipo, o volver a usarlo. Las prendas que ya lo tienen lo conservan (CA-16.2).
     */
    public function cambiarActivoTipo(Request $solicitud, TipoPrenda $tipo, GestionarTiposDePrenda $tiposDePrenda): RedirectResponse
    {
        $activo = $solicitud->boolean('activo');
        $tiposDePrenda->cambiarActivo($tipo, $activo);

        return redirect()->route('ajustes')->with(
            'exito',
            $activo
                ? "«{$tipo->nombre}» vuelve a aparecer al registrar prendas."
                : "«{$tipo->nombre}» ya no aparece al registrar prendas nuevas."
        );
    }

    public function cambiarContrasena(ContrasenaRequest $solicitud, CambiarContrasena $cambiarContrasena): RedirectResponse
    {
        $cambiarContrasena->ejecutar($solicitud->user(), $solicitud->validated('contrasena_nueva'));

        return redirect()->route('ajustes')
            ->with('exito', 'Tu contraseña cambió. Se cerró la sesión en los demás dispositivos.');
    }
}
