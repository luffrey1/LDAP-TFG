<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Canal privado para la terminal WebSocket
Broadcast::channel('terminal.{sessionId}', function ($user, $sessionId) {
    // Puedes personalizar la lógica de autorización aquí
    return true; // Permitir a cualquier usuario autenticado
}); 