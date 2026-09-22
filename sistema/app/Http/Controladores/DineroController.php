<?php

namespace App\Http\Controladores;

use App\Aplicacion\Consultas\DineroRecibido;
use App\Aplicacion\Consultas\QuienMeDebe;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * PT-22 · Dinero: lo recibido en un período (HU-27) y quién me debe (HU-26). Reglas y mensajes de
 * docs/04-especificacion-tecnica/03-validaciones-y-mensajes.md.
 */
class DineroController
{
    public function mostrar(Request $solicitud, DineroRecibido $dineroRecibido, QuienMeDebe $quienMeDebe): View
    {
        // Un período que no existe no muestra error: se ignora y se ve el mes, como el filtro de las órdenes
        $periodo = in_array($solicitud->query('periodo'), DineroRecibido::PERIODOS, true) ? $solicitud->query('periodo') : 'mes';
        $desde = $hasta = null;
        $errores = null;

        if ($periodo === 'fechas') {
            // Se valida aquí y no con un FormRequest: es una consulta, y sin fechas válidas la pantalla se muestra igual
            $validacion = Validator::make($solicitud->query(), [
                'desde' => ['required', 'date_format:Y-m-d'],
                'hasta' => ['required', 'date_format:Y-m-d', 'after_or_equal:desde'],
            ], [
                'desde.required' => 'Elige las dos fechas.',
                'hasta.required' => 'Elige las dos fechas.',
                'desde.date_format' => 'Elige las dos fechas.',
                'hasta.date_format' => 'Elige las dos fechas.',
                'hasta.after_or_equal' => 'La fecha final no puede ser antes de la inicial.',
            ]);

            if ($validacion->fails()) {
                $errores = $validacion->errors();
            } else {
                $desde = new DateTimeImmutable($solicitud->query('desde'));
                $hasta = new DateTimeImmutable($solicitud->query('hasta'));
            }
        }

        $rango = $dineroRecibido->periodo($periodo, $desde, $hasta);

        return view('pantallas.pt-22-dinero', [
            'periodo' => $periodo,
            'rango' => $rango,
            // Con fechas sin elegir o inválidas no hay qué sumar: se pide elegirlas
            'recibido' => $periodo === 'fechas' && $desde === null ? null : $dineroRecibido->entre($rango['desde'], $rango['hasta']),
            'erroresFechas' => $errores,
            ...$quienMeDebe->obtener(),
        ]);
    }
}
