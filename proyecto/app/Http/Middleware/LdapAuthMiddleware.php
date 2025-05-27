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
        
        if (!session()->has('auth_user')) {
            Log::warning('LdapAuthMiddleware: No hay sesión de usuario');
            return redirect()->route('login');
        }

        try {
            $config = config('ldap.connections.default');
            $connection = new Connection([
                'hosts' => $config['hosts'],
                'port' => $config['port'],
                'base_dn' => $config['base_dn'],
                'username' => $config['username'],
                'password' => $config['password'],
                'use_ssl' => $config['use_ssl'],
                'use_tls' => $config['use_tls'],
                'timeout' => $config['timeout'],
                'options' => [
                    LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_NEVER,
                    LDAP_OPT_REFERRALS => 0,
                ],
            ]);

            $connection->connect();

            $username = session('auth_user')['username'];
            Log::debug('LdapAuthMiddleware: Buscando usuario LDAP: ' . $username);
            
            $user = $connection->query()
                ->in('ou=people,dc=tierno,dc=es')
                ->where('uid', '=', $username)
                ->first();

            if (!$user) {
                Log::error('LdapAuthMiddleware: Usuario LDAP no encontrado: ' . $username);
                return redirect()->route('login');
            }

            // Obtener grupos del usuario
            $groups = [];
            if (isset($user['memberof'])) {
                Log::debug('LdapAuthMiddleware: Grupos encontrados: ' . json_encode($user['memberof']));
                foreach ($user['memberof'] as $group) {
                    if (preg_match('/cn=([^,]+),/', $group, $matches)) {
                        $groups[] = $matches[1];
                    }
                }
            }
            
            Log::debug('LdapAuthMiddleware: Grupos procesados: ' . json_encode($groups));

            // Actualizar la sesión con los grupos
            $authUser = session('auth_user');
            $authUser['groups'] = $groups;
            session(['auth_user' => $authUser]);
            
            Log::debug('LdapAuthMiddleware: Sesión actualizada con grupos');

            return $next($request);
        } catch (\Exception $e) {
            Log::error('LdapAuthMiddleware: Error: ' . $e->getMessage());
            return redirect()->route('login');
        }
    }
} 