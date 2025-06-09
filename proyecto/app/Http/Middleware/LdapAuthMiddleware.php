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

        try {
            // Intentar verificar la conexión LDAP
            $config = config('ldap.connections.default');
            $connection = new Connection([
                'hosts' => $config['hosts'],
                'port' => 636,
                'base_dn' => $config['base_dn'],
                'username' => $config['username'],
                'password' => $config['password'],
                'use_ssl' => true,
                'use_tls' => false,
                'timeout' => 5
            ]);

            // Forzar la conexión SSL
            $connection->getLdapConnection()->setOption(LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
            
            // Intentar conectar
            $connection->connect();

            // Verificar si el usuario pertenece al grupo alumnos
            $user = session('auth_user');
            $ldapConnection = $connection->getLdapConnection();
            
            // Buscar el grupo alumnos
            $search = ldap_search($ldapConnection, 'dc=tierno,dc=es', '(&(objectClass=posixGroup)(cn=alumnos))');
            $entries = ldap_get_entries($ldapConnection, $search);
            
            if ($entries['count'] > 0) {
                $alumnosGroup = $entries[0];
                // Verificar si el usuario está en el grupo
                if (isset($alumnosGroup['memberuid']) && in_array($user['uid'], $alumnosGroup['memberuid'])) {
                    // El usuario es un alumno, mostrar la pantalla de advertencia
                    return response()->view('auth.student-warning');
                }
            }
            
            // Si llegamos aquí, la conexión fue exitosa y el usuario no es alumno
            return $next($request);
            
        } catch (\Exception $e) {
            Log::error('LdapAuthMiddleware: Error de conexión LDAP - ' . $e->getMessage());
            
            // Si es un error de conexión, redirigir a una página de error
            if (str_contains($e->getMessage(), 'Can\'t contact LDAP server')) {
                return response()->view('errors.ldap', [
                    'message' => 'No se pudo conectar al servidor LDAP. Por favor, intente de nuevo en unos momentos.'
                ], 503);
            }
            
            // Para otros errores, redirigir al login
            return redirect()->route('login')->with('error', 'Error de autenticación. Por favor, vuelva a iniciar sesión.');
        }
    }
} 