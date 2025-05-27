<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        Log::debug('AdminMiddleware: Iniciando verificación de permisos');
        Log::debug('Ruta actual: ' . $request->path());
        Log::debug('Sesión auth_user: ' . json_encode(session('auth_user')));

        // Verificar si hay sesión de usuario
        if (!session()->has('auth_user')) {
            Log::warning('AdminMiddleware: No hay sesión de usuario');
            return redirect()->route('login');
        }

        $authUser = session('auth_user');
        $username = $authUser['username'] ?? '';
        $isAdmin = $authUser['is_admin'] ?? false;
        $groups = $authUser['groups'] ?? [];

        Log::debug('AdminMiddleware: Datos del usuario:');
        Log::debug('- Username: ' . $username);
        Log::debug('- isAdmin: ' . ($isAdmin ? 'true' : 'false'));
        Log::debug('- Grupos: ' . json_encode($groups));

        // Verificar permisos
        $isLdapAdmin = $username === 'ldap-admin';
        $isInLdapAdmins = in_array('ldapadmins', $groups);

        Log::debug('AdminMiddleware: Verificación de permisos:');
        Log::debug('- isLdapAdmin: ' . ($isLdapAdmin ? 'true' : 'false'));
        Log::debug('- isInLdapAdmins: ' . ($isInLdapAdmins ? 'true' : 'false'));

        // Si el usuario tiene alguno de los permisos necesarios, permitir acceso
        if ($isAdmin || $isLdapAdmin || $isInLdapAdmins) {
            Log::info('AdminMiddleware: Acceso concedido para usuario ' . $username);
            return $next($request);
        }

        // Si no tiene permisos, redirigir al dashboard
        Log::warning('AdminMiddleware: Acceso denegado para usuario ' . $username);
        return redirect('/dashboard')->with('error', 'No tienes permisos para acceder a esta sección.');
    }
} 