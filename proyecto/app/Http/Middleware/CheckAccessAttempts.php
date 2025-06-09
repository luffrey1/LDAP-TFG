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
        
        // Solo verificar para administradores
        if ($user && ($user['is_admin'] || in_array('ldapadmins', $user['groups'] ?? []))) {
            Log::debug('CheckAccessAttempts: Usuario es admin, obteniendo intentos de acceso');
            
            // Obtener intentos de acceso de las últimas 24 horas
            $recentAttempts = AccessAttempt::where('created_at', '>=', now()->subDay())
                ->orderBy('created_at', 'desc')
                ->get();

            // Compartir los intentos con todas las vistas
            View::share('recentAccessAttempts', $recentAttempts);
            
            // Si hay intentos y no se ha mostrado la alerta en esta sesión
            if ($recentAttempts->count() > 0 && !session('access_attempts_shown')) {
                // Incluir el partial en la vista
                View::share('show_access_attempts', true);
                // Marcar como mostrado en la sesión
                session(['access_attempts_shown' => true]);
            }
            
            Log::debug('CheckAccessAttempts: Intentos de acceso compartidos con la vista', [
                'count' => $recentAttempts->count()
            ]);
        }

        return $next($request);
    }
} 