<?php

// Datos que usa NegocioInicialSeeder al instalar (RNF-33). La contraseña se borra del .env después de instalar.

return [
    'usuaria' => [
        'usuario' => env('USUARIA_INICIAL_USUARIO'),
        'contrasena' => env('USUARIA_INICIAL_CONTRASENA'),
        // HU-37: con este correo entra por Google, sin escribir contraseña (RN-45)
        'correo' => env('USUARIA_INICIAL_CORREO'),
    ],
];
