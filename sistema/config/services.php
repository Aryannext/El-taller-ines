<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    // Aviso de orden lista por la API oficial (docs/04-especificacion-tecnica/05-avisos-fotos-y-reloj.md). Sin token, envío asistido (RN-40)
    'whatsapp' => [
        'token' => env('WHATSAPP_TOKEN'),
        'id_numero' => env('WHATSAPP_ID_NUMERO'),
        'version_api' => env('WHATSAPP_VERSION_API'),
        'plantilla' => env('WHATSAPP_PLANTILLA', 'orden_lista'),
        'idioma' => env('WHATSAPP_IDIOMA', 'es'),
    ],

    // ADR-007: aviso automático por Evolution API. Si está configurada, se usa en vez de la API oficial
    'evolution' => [
        'url' => env('EVOLUTION_URL'),
        'clave_api' => env('EVOLUTION_API_KEY'),
        'instancia' => env('EVOLUTION_INSTANCIA'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
