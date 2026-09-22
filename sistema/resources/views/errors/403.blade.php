{{-- 403: la dirección existe, pero no es suya --}}
@extends('errors.marco', [
    'codigo' => 403,
    'titulo' => 'Eso no lo puede abrir',
    'texto' => 'Esa pantalla o ese dato no pertenecen a su taller. Si cree que sí debería verlo, avísele a quien le instaló el sistema.',
    'acciones' => [
        ['ruta' => route('panel'), 'texto' => 'Ir a Hoy', 'clase' => 'btn-primario'],
    ],
])
