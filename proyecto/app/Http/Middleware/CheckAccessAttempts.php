<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\AccessAttempt;
use Illuminate\Support\Facades\View;

class CheckAccessAttempts
{
    public function handle(Request $request, Closure $next)
    {
        $user = session('auth_user');
        
        // Solo verificar para administradores
        if ($user && $user['role'] === 'admin') {
            // Obtener intentos de acceso de las últimas 24 horas
            $recentAttempts = AccessAttempt::where('created_at', '>=', now()->subDay())
                ->orderBy('created_at', 'desc')
                ->get();

            // Compartir los intentos con todas las vistas
            View::share('recentAccessAttempts', $recentAttempts);
        }

        return $next($request);
    }
} 