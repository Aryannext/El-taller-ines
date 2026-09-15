<?php

namespace App\Http\Controladores;

use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * PT-02 · Panel del día. Por ahora solo recibe a la usuaria; las cifras llegan con HU-32 y la consulta PanelDelDia.
 */
class PanelController
{
    public function mostrar(Request $request): View
    {
        $usuaria = $request->user();

        return view('pantallas.pt-02-panel-del-dia', [
            'usuaria' => $usuaria->nombre,
            'negocio' => $usuaria->negocio->nombre,
        ]);
    }
}
