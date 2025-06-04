<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use LdapRecord\Connection;
use Illuminate\Support\Facades\Log;

class LdapAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Log::debug('LdapAuthMiddleware: Iniciando verificación de autenticación LDAP');
        
        // Si la ruta actual es login, permitir el acceso
        if ($request->routeIs('login') || $request->routeIs('auth.login')) {
            return $next($request);
        }
        
        if (!session()->has('auth_user')) {
            Log::warning('LdapAuthMiddleware: No hay sesión de usuario');
            return redirect()->route('login');
        }

        // Si hay sesión, permitir el acceso
        return $next($request);
    }
} 