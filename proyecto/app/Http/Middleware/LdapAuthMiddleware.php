<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use LdapRecord\Connection;
use Illuminate\Support\Facades\Log;
use LdapRecord\Ldap;

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
        
        if (!session()->has('ldap_auth')) {
            return redirect()->route('login');
        }

        // Verificar si el usuario pertenece al grupo alumnos
        $user = session('ldap_auth');
        $ldap = new Ldap();
        $connection = $ldap->connect();
        
        if ($connection) {
            try {
                // Buscar el grupo alumnos
                $search = ldap_search($connection, 'dc=tierno,dc=es', '(&(objectClass=posixGroup)(cn=alumnos))');
                $entries = ldap_get_entries($connection, $search);
                
                if ($entries['count'] > 0) {
                    $alumnosGroup = $entries[0];
                    // Verificar si el usuario está en el grupo
                    if (isset($alumnosGroup['memberuid']) && in_array($user['uid'], $alumnosGroup['memberuid'])) {
                        // El usuario es un alumno, mostrar la pantalla de advertencia
                        return response()->view('auth.student-warning');
                    }
                }
            } catch (\Exception $e) {
                \Log::error('Error al verificar grupo de alumnos: ' . $e->getMessage());
            }
        }

        return $next($request);
    }
} 