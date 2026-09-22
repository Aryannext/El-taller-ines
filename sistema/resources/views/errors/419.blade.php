{{-- 419: la sesión se venció (RNF-21: se cierra sola a las 8 horas) o el formulario quedó abierto demasiado tiempo --}}
@extends('errors.marco', [
    'codigo' => 419,
    'titulo' => 'La sesión se venció',
    'texto' => 'Por seguridad, la sesión se cierra sola después de un rato sin usarla. Entre otra vez y siga donde iba; lo que ya había guardado está guardado.',
    'acciones' => [
        ['ruta' => route('sesion.formulario'), 'texto' => 'Entrar otra vez', 'clase' => 'btn-primario'],
    ],
])
