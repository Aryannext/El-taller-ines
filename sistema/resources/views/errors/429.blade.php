{{-- 429: demasiados intentos seguidos. Es el que ve quien se equivoca varias veces de contraseña (RN-01) --}}
@extends('errors.marco', [
    'codigo' => 429,
    'titulo' => 'Espere un momento',
    'texto' => 'Se hicieron muchos intentos seguidos. Espere un minuto y vuelva a probar; es una protección para que nadie adivine la contraseña.',
    'acciones' => [
        ['ruta' => route('sesion.formulario'), 'texto' => 'Volver a entrar', 'clase' => 'btn-primario'],
    ],
])
