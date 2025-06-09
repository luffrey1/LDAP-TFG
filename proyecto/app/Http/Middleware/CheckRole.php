<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!$request->session()->has('auth_user')) {
            Log::warning('Intento de acceso sin sesión de usuario');
            return redirect()->route('login');
        }

        $user = $request->session()->get('auth_user');
        
        // Si el usuario es admin, tiene acceso a todo
        if ($user['is_admin']) {
            return $next($request);
        }

        // Verificar si el usuario tiene alguno de los roles requeridos
        foreach ($roles as $role) {
            if ($role === 'profesor' && $user['is_profesor']) {
                return $next($request);
            }
        }

        Log::warning('Intento de acceso no autorizado', [
            'user' => $user['username'],
            'roles_requeridos' => $roles
        ]);

        return response()->view('errors.403', [], 403);
    }
} 