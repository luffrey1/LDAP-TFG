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
            
            Log::debug('CheckAccessAttempts: Intentos de acceso compartidos con la vista', [
                'count' => $recentAttempts->count()
            ]);
        }

        return $next($request);
    }
} 