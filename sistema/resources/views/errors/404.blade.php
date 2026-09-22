{{-- 404: la dirección no corresponde a nada del sistema. También es la que ve quien pide una orden de otro taller (RNF-23, A01) --}}
@extends('errors.marco', [
    'codigo' => 404,
    'titulo' => 'Esa página no existe',
    'texto' => 'La dirección que abrió no corresponde a ninguna pantalla del sistema. Puede que el enlace esté mal escrito, o que esa orden ya no esté.',
    'acciones' => [
        ['ruta' => route('panel'), 'texto' => 'Ir a Hoy', 'clase' => 'btn-primario'],
    ],
    'sugerencias' => true,
])
