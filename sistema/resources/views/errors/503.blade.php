{{-- 503: el sistema está en mantenimiento, casi siempre mientras se actualiza (despliegue/aplicar.sh) --}}
@extends('errors.marco', [
    'codigo' => 503,
    'titulo' => 'El sistema está en mantenimiento',
    'texto' => 'Se está actualizando y vuelve en unos minutos. Sus órdenes y sus clientes están intactos; esto no borra nada.',
    'acciones' => [
        ['ruta' => url()->current(), 'texto' => 'Volver a intentar', 'clase' => 'btn-primario'],
    ],
])
