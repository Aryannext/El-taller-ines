<?php

// Datos que usa NegocioInicialSeeder al instalar (RNF-33). La contraseña se borra del .env después de instalar.

return [
    'usuaria' => [
        'usuario' => env('USUARIA_INICIAL_USUARIO'),
        'contrasena' => env('USUARIA_INICIAL_CONTRASENA'),
    ],
];
