<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Puerto del servidor WebSocket
    |--------------------------------------------------------------------------
    |
    | El puerto en el que escuchará el servidor WebSocket.
    |
    */
    'port' => env('WEBSOCKET_PORT', 8080),

    /*
    |--------------------------------------------------------------------------
    | Host del servidor WebSocket
    |--------------------------------------------------------------------------
    |
    | El host en el que se enlazará el servidor WebSocket.
    | Por defecto, '0.0.0.0' para permitir conexiones desde cualquier IP.
    |
    */
    'host' => env('WEBSOCKET_HOST', '0.0.0.0'),

    /*
    |--------------------------------------------------------------------------
    | Configuración SSL
    |--------------------------------------------------------------------------
    |
    | Determina si el servidor WebSocket debe usar SSL.
    |
    */
    'ssl' => [
        'enable' => env('WEBSOCKET_SSL', false),
        'cert_path' => env('WEBSOCKET_SSL_CERT', storage_path('ssl/certificate.crt')),
        'key_path' => env('WEBSOCKET_SSL_KEY', storage_path('ssl/private.key')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Tiempo de vida de la sesión
    |--------------------------------------------------------------------------
    |
    | Tiempo en minutos que una sesión de terminal permanecerá válida.
    |
    */
    'session_lifetime' => env('WEBSOCKET_SESSION_LIFETIME', 30),
]; 