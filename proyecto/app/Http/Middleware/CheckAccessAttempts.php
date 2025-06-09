<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\AccessAttempt;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Log;

class CheckAccessAttempts
{
    public function handle(Request $request, Closure $next)
    {
        $user = session('auth_user');
        Log::debug('CheckAccessAttempts: Iniciando middleware', [
            'user' => $user,
            'is_admin' => $user['is_admin'] ?? false,
            'groups' => $user['groups'] ?? []
        ]);
        
        // Solo verificar para administradores
        if ($user && ($user['is_admin'] || in_array('ldapadmins', $user['groups'] ?? []))) {
            Log::debug('CheckAccessAttempts: Usuario es admin, obteniendo intentos de acceso');
            
            // Obtener intentos de acceso de las últimas 24 horas
            $recentAttempts = AccessAttempt::where('created_at', '>=', now()->subDay())
                ->orderBy('created_at', 'desc')
                ->get();

            Log::debug('CheckAccessAttempts: Intentos encontrados', [
                'count' => $recentAttempts->count(),
                'attempts' => $recentAttempts->toArray()
            ]);

            // Compartir los intentos con todas las vistas
            View::share('recentAccessAttempts', $recentAttempts);
            
            // Si hay intentos y no se ha mostrado la alerta en esta sesión
            if ($recentAttempts->count() > 0 && !session('access_attempts_shown')) {
                Log::debug('CheckAccessAttempts: Mostrando alerta de intentos');
                // Incluir el partial en la vista
                View::share('show_access_attempts', true);
                // Marcar como mostrado en la sesión
                session(['access_attempts_shown' => true]);
            } else {
                Log::debug('CheckAccessAttempts: No se muestra alerta', [
                    'has_attempts' => $recentAttempts->count() > 0,
                    'already_shown' => session('access_attempts_shown')
                ]);
            }
        } else {
            Log::debug('CheckAccessAttempts: Usuario no es admin');
        }

        return $next($request);
    }
} 