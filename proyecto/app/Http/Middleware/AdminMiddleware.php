<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Depuración de la sesión
        Log::debug("Contenido de la sesión auth_user: " . json_encode(session('auth_user')));
        
        // Verificar si el usuario está autenticado (Facade Auth o sesión)
        if (!Auth::check() && !session()->has('auth_user')) {
            Log::warning("No hay sesión de usuario o Auth::check() es falso");
            return redirect()->route('login')
                ->with('error', 'Debes iniciar sesión para acceder a esta sección.');
        }
        
        // Obtener username y rol de usuario (desde Auth o sesión)
        $username = Auth::check() ? Auth::user()->username : session('auth_user.username');
        $role = Auth::check() ? Auth::user()->role : session('auth_user.role');
        $isAdmin = session('auth_user.is_admin') ?? false;
        
        Log::debug("Verificando acceso de administrador para usuario: $username con rol: $role, is_admin: " . ($isAdmin ? 'true' : 'false'));
        
        // Para el usuario ldap-admin, siempre permitir acceso
        if ($username === 'ldap-admin') {
            Log::info("Acceso de administrador concedido a usuario ldap-admin");
            return $next($request);
        }
        
        // Verificar si tiene rol de administrador o el flag is_admin está activo
        if ($role !== 'admin' && !$isAdmin) {
            Log::warning("Acceso denegado a usuario $username (rol: $role, is_admin: " . ($isAdmin ? 'true' : 'false') . ") - se requiere rol admin");
            return redirect()->route('dashboard.index')
                ->with('error', 'No tienes permisos para acceder a esta sección. Se requiere rol de administrador.');
        }
        
        Log::info("Acceso de administrador concedido a usuario $username con rol $role");
        return $next($request);
    }
} 