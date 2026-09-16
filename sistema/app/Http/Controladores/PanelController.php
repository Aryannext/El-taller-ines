<?php

namespace App\Http\Controladores;

use App\Aplicacion\Consultas\AvisosPorEnviar;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * PT-02 · Panel del día. Por ahora solo recibe a la usuaria; las cifras llegan con HU-32 y la consulta PanelDelDia.
 */
class PanelController
{
    public function mostrar(Request $request, AvisosPorEnviar $avisosPorEnviar): View
    {
        $usuaria = $request->user();

        return view('pantallas.pt-02-panel-del-dia', [
            'usuaria' => $usuaria->nombre,
            'negocio' => $usuaria->negocio->nombre,
            // HU-29: los avisos que esperan salir desde el WhatsApp de la dueña
            'avisosPorEnviar' => $avisosPorEnviar->contar(),
        ]);
    }
}
