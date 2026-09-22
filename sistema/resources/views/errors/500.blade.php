{{-- 500: falló algo del sistema. Nunca se muestran detalles técnicos: quedan en el registro del servidor (RNF-09, PM-07 A05) --}}
@extends('errors.marco', [
    'codigo' => 500,
    'titulo' => 'Algo falló de este lado',
    'texto' => 'No fue culpa suya y no se perdió nada de lo que ya había guardado. El error quedó anotado en el servidor. Vuelva a intentarlo y, si sigue pasando, avísele a quien mantiene el sistema.',
    'acciones' => [
        ['ruta' => route('panel'), 'texto' => 'Ir a Hoy', 'clase' => 'btn-primario'],
    ],
])
