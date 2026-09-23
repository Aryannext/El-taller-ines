<?php

namespace App\Http\Controladores;

use App\Aplicacion\Consultas\PanelDelDia;
use App\Dominio\Compartido\Reloj;
use App\Dominio\Compartido\SaludoDelDia;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * PT-02 · Panel del día: lo primero que ve la dueña al entrar (HU-32, CU-33).
 */
class PanelController
{
    public function mostrar(Request $request, PanelDelDia $panel, SaludoDelDia $saludo, Reloj $reloj): View
    {
        $usuaria = $request->user();

        return view('pantallas.pt-02-panel-del-dia', [
            // RN-47: el saludo depende de la hora del taller
            'saludo' => $saludo->para($reloj->ahora()),
            'usuaria' => $usuaria->nombre,
            'negocio' => $usuaria->negocio->nombre,
            ...$panel->obtener($usuaria->negocio),
        ]);
    }
}
