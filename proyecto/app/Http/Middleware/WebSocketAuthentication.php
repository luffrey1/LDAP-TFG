<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WebSocketAuthentication
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Extraer ID de sesión de la URL
        $path = $request->getPathInfo();
        $sessionId = basename($path);
        
        // Verificar si la sesión SSH existe y está activa
        $sshSession = Cache::get('ssh_session_' . $sessionId);
        
        if (!$sshSession || !$sshSession['active']) {
            Log::warning("Intento de acceso a WebSocket SSH con sesión inválida: $sessionId");
            return response()->json(['error' => 'Sesión SSH no válida o expirada'], 403);
        }
        
        // Verificar si el usuario autenticado coincide con el de la sesión
        if (Auth::check() && Auth::id() !== $sshSession['user_id']) {
            Log::warning("Intento de acceso a WebSocket SSH con usuario incorrecto: $sessionId");
            return response()->json(['error' => 'No autorizado para esta sesión SSH'], 403);
        }
        
        // La solicitud es válida, continuamos
        Log::debug("Conexión WebSocket SSH autenticada: $sessionId");
        return $next($request);
    }
} 