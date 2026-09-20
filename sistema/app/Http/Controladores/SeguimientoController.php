<?php

namespace App\Http\Controladores;

use App\Aplicacion\Consultas\OrdenesAtrasadas;
use Illuminate\View\View;

/**
 * PT-20 · La lista de seguimiento que extiende el panel del día (CU-34).
 */
class SeguimientoController
{
    public function atrasadas(OrdenesAtrasadas $atrasadas): View
    {
        return view('pantallas.pt-20-atrasadas', ['ordenes' => $atrasadas->listar()]);
    }
}
