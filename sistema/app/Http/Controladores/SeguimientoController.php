<?php

namespace App\Http\Controladores;

use App\Aplicacion\Consultas\OrdenesAtrasadas;
use App\Aplicacion\Consultas\OrdenesSinReclamar;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * PT-20 y PT-21 · Las dos listas de seguimiento, que extienden el panel del día (CU-34, CU-35).
 * Las dos muestran el contador de la otra: son pestañas de la misma pantalla.
 */
class SeguimientoController
{
    public function atrasadas(Request $solicitud, OrdenesAtrasadas $atrasadas, OrdenesSinReclamar $sinReclamar): View
    {
        return view('pantallas.pt-20-atrasadas', [
            'ordenes' => $atrasadas->listar(),
            'sinReclamar' => $sinReclamar->contar($solicitud->user()->negocio)['ordenes'],
        ]);
    }

    public function sinReclamar(Request $solicitud, OrdenesAtrasadas $atrasadas, OrdenesSinReclamar $sinReclamar): View
    {
        $negocio = $solicitud->user()->negocio;
        $ordenes = $sinReclamar->listar($negocio);

        return view('pantallas.pt-21-sin-reclamar', [
            'ordenes' => $ordenes,
            'atrasadas' => $atrasadas->contar(),
            'plazo' => $negocio->dias_sin_reclamar,
            // RN-35: el total que espera en el taller, como en el mockup
            'prendas' => array_sum(array_column($ordenes, 'prendas')),
        ]);
    }
}
