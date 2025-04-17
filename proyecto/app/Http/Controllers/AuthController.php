<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use LdapRecord\Connection;
use Illuminate\Support\Facades\Config;

class AuthController extends Controller
{
    /**
     * Mostrar formulario de login
     */
    public function showLoginForm()
    {
        if (session()->has('auth_user')) {
            return redirect()->route('dashboard.index');
        }
        
        return view('auth.login');
    }
    
    /**
     * Procesar login
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);
        
        try {
            Log::info('Intento de login para usuario: ' . $credentials['username']);
            
            // Intentar autenticación exclusivamente con LDAP
            if ($this->attemptLdapAuth($credentials)) {
                Log::info('Usuario autenticado via LDAP: ' . $credentials['username']);
                return redirect()->route('dashboard.index');
            }
            
            // Si falla, devolver error
            return back()->withErrors([
                'username' => 'Las credenciales proporcionadas no son correctas o el usuario no existe en LDAP.',
            ])->withInput();
            
        } catch (\Exception $e) {
            Log::error('Error en autenticación: ' . $e->getMessage());
            
            return back()->withErrors([
                'error' => 'Error al procesar la autenticación. Por favor, inténtelo de nuevo.',
            ]);
        }
    }
    
    /**
     * Intentar autenticación LDAP
     */
    private function attemptLdapAuth($credentials)
    {
        try {
            // Verificar si LDAP está configurado
            if (!Config::get('ldap.connections.default.hosts')) {
                Log::warning('LDAP no está configurado correctamente: no hay hosts definidos');
                return false;
            }
            
            // Configurar conexión LDAP - Usar directamente valores del entorno para depuración
            $ldapConfig = [
                'hosts' => [env('LDAP_HOST', 'openldap-osixia')],
                'port' => env('LDAP_PORT', 389),
                'base_dn' => env('LDAP_BASE_DN', 'dc=test,dc=tierno,dc=es'),
                'username' => env('LDAP_USERNAME', 'cn=admin,dc=test,dc=tierno,dc=es'),
                'password' => env('LDAP_PASSWORD', 'admin'),
                'use_ssl' => false,
                'use_tls' => false,
                'timeout' => 5,
                'options' => [
                    LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_NEVER,
                    LDAP_OPT_REFERRALS => 0,
                ],
            ];
            
            Log::debug('Intento de conexión LDAP con: ' . json_encode($ldapConfig));
            
            $ldap = new Connection($ldapConfig);
            
            // Intentar conectar
            Log::debug('Intentando conectar al servidor LDAP: ' . $ldapConfig['hosts'][0]);
            $ldap->connect();
            Log::debug('Conexión LDAP establecida exitosamente');
            
            // Construir DN de usuario
            $baseDn = $ldapConfig['base_dn'];
            $userDn = "uid={$credentials['username']},ou=people," . $baseDn;
            
            Log::debug("Intentando autenticar con DN: {$userDn} y contraseña proporcionada");
            
            // Intentar bind con las credenciales
            if ($ldap->auth()->attempt($userDn, $credentials['password'])) {
                Log::debug("Bind exitoso para {$userDn}");
                
                // Buscar o crear usuario en la base de datos local
                Log::debug("Buscando usuario LDAP con uid={$credentials['username']}");
                
                // Tenemos que volver a conectar con el admin para buscar información del usuario
                $ldap->connect();
                $ldap->auth()->bind($ldapConfig['username'], $ldapConfig['password']);
                
                $ldapUser = $ldap->query()
                    ->where('uid', '=', $credentials['username'])
                    ->first();
                
                if ($ldapUser) {
                    // Verificar si es objeto o array para obtener información correctamente
                    $userInfo = is_object($ldapUser) && method_exists($ldapUser, 'getDn') 
                        ? "DN: " . $ldapUser->getDn() 
                        : "UID: " . $credentials['username'];
                    
                    Log::debug("Usuario LDAP encontrado: " . $userInfo);
                    return $this->processLdapUser($ldapUser, $credentials);
                } else {
                    Log::error("Usuario autenticado en LDAP pero no se pudo recuperar información: {$credentials['username']}");
                    return false;
                }
            } else {
                Log::warning("Fallo en autenticación LDAP para usuario: {$credentials['username']} - Credenciales incorrectas");
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Error LDAP: ' . $e->getMessage());
            Log::error('Traza de error: ' . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Procesar usuario LDAP y crear sesión
     */
    private function processLdapUser($ldapUser, $credentials)
    {
        try {
            // Verificar si $ldapUser es un array u objeto y obtener los datos adecuadamente
            $userData = is_object($ldapUser) && method_exists($ldapUser, 'toArray') 
                ? $ldapUser->toArray() 
                : (is_array($ldapUser) ? $ldapUser : []);
            
            Log::debug("Datos de usuario LDAP: " . json_encode(array_map(function($attr) {
                return is_array($attr) ? implode(', ', $attr) : $attr;
            }, $userData)));
            
            // Determinar rol basado en grupos LDAP
            $role = $this->determineRoleFromLdapGroups($ldapUser, $credentials['username']);
            Log::debug("Rol asignado al usuario: {$role}");
            
            // Obtener valores seguros de los atributos LDAP
            $cn = null;
            $mail = null;
            $uid = null;
            
            if (is_object($ldapUser)) {
                $cn = $ldapUser->getFirstAttribute('cn');
                $mail = $ldapUser->getFirstAttribute('mail');
                $uid = $ldapUser->getFirstAttribute('uid');
            } else if (is_array($ldapUser)) {
                $cn = $ldapUser['cn'][0] ?? null;
                $mail = $ldapUser['mail'][0] ?? null;
                $uid = $ldapUser['uid'][0] ?? null;
            }
            
            // Usar valores predeterminados si no se encuentran
            $cn = $cn ?: $credentials['username'];
            $mail = $mail ?: $credentials['username'] . '@test.tierno.es';
            $uid = $uid ?: $credentials['username'];
            
            Log::debug("Valores extraídos: CN=$cn, Mail=$mail, UID=$uid");
            
            // Buscar usuario por email o crear uno nuevo
            $user = User::where('email', $mail)->first();
            
            if (!$user) {
                // Si no existe, crear nuevo usuario
                $user = new User();
                $user->name = $cn;
                $user->email = $mail;
                $user->password = Hash::make(Str::random(16));
                $user->guid = $uid;
                $user->domain = env('LDAP_BASE_DN', 'dc=test,dc=tierno,dc=es');
                $user->save();
                
                Log::debug("Usuario creado en base de datos: " . $user->id);
            } else {
                // Actualizar usuario existente
                $user->name = $cn;
                $user->guid = $uid;
                $user->domain = env('LDAP_BASE_DN', 'dc=test,dc=tierno,dc=es');
                $user->save();
                
                Log::debug("Usuario actualizado en base de datos: " . $user->id);
            }
            
            // Autenticar usuario local
            Auth::login($user);
            Log::debug("Usuario autenticado en el sistema");
            
            // Guardar datos en sesión
            session([
                'auth_user' => [
                    'id' => $user->id,
                    'username' => $credentials['username'],
                    'nombre' => $user->name,
                    'email' => $user->email,
                    'role' => $role,
                    'is_admin' => ($role === 'admin'),
                    'auth_type' => 'ldap'
                ]
            ]);
            
            Log::info("Autenticación LDAP exitosa para usuario: {$credentials['username']} con rol: {$role}");
            return true;
        } catch (\Exception $e) {
            Log::error("Error procesando usuario LDAP: " . $e->getMessage());
            Log::error("Traza: " . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Determinar rol desde grupos LDAP
     */
    private function determineRoleFromLdapGroups($ldapUser, $username)
    {
        // Para el caso especial de ldap-admin o LDAPAdmin, otorgar rol de administrador inmediatamente
        if ($username === 'ldap-admin' || $username === 'LDAPAdmin' || strtolower($username) === 'ldapadmin') {
            Log::debug("Usuario {$username} detectado, asignando rol de administrador directamente");
            return 'admin';
        }
        
        // Verificar formato de $ldapUser y obtener uid de forma segura
        $uid = null;
        
        if (is_object($ldapUser)) {
            $uid = $ldapUser->getFirstAttribute('uid');
        } elseif (is_array($ldapUser) && isset($ldapUser['uid'])) {
            $uid = is_array($ldapUser['uid']) ? $ldapUser['uid'][0] : $ldapUser['uid'];
        } elseif (is_array($ldapUser) && isset($ldapUser['attributes']['uid'])) {
            $uid = is_array($ldapUser['attributes']['uid']) ? $ldapUser['attributes']['uid'][0] : $ldapUser['attributes']['uid'];
        }
        
        // Si no podemos obtener el UID, usamos el nombre de usuario
        if (!$uid) {
            $uid = $username;
            Log::warning("No se pudo extraer UID del objeto LDAP, usando username: $username");
        }
        
        try {
            // Si el UID parece ser LDAPAdmin, asignar rol admin sin más comprobaciones
            if ($uid === 'LDAPAdmin' || strtolower($uid) === 'ldapadmin') {
                Log::debug("Usuario con UID={$uid} detectado, asignando rol de administrador directamente");
                return 'admin';
            }
            
            // Configurar conexión LDAP usando directamente variables de entorno para depuración
            $ldapConfig = [
                'hosts' => [env('LDAP_HOST', 'openldap-osixia')],
                'port' => env('LDAP_PORT', 389),
                'base_dn' => env('LDAP_BASE_DN', 'dc=test,dc=tierno,dc=es'),
                'username' => env('LDAP_USERNAME', 'cn=admin,dc=test,dc=tierno,dc=es'),
                'password' => env('LDAP_PASSWORD', 'admin'),
                'use_ssl' => false,
                'use_tls' => false,
                'timeout' => 5,
                'options' => [
                    LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_NEVER,
                    LDAP_OPT_REFERRALS => 0,
                ],
            ];
            
            $ldap = new Connection($ldapConfig);
            $ldap->connect();
            
            $baseDn = $ldapConfig['base_dn'];
            
            Log::debug("Buscando grupos para el usuario con UID=$uid");
            
            // Buscar grupos a los que pertenece el usuario
            $groups = $ldap->query()
                ->where('objectClass', '=', 'posixGroup')
                ->where('memberUid', '=', $uid)
                ->get();
            
            $groupNames = [];
            foreach ($groups as $group) {
                $groupCn = null;
                if (is_object($group)) {
                    $groupCn = $group->getFirstAttribute('cn');
                } elseif (is_array($group) && isset($group['cn'])) {
                    $groupCn = is_array($group['cn']) ? $group['cn'][0] : $group['cn'];
                }
                
                if ($groupCn) {
                    $groupNames[] = $groupCn;
                }
            }
            
            Log::debug("Grupos encontrados para {$uid}: " . json_encode($groupNames));
            
            // Asignar rol basado en grupos
            if (in_array('ldapadmins', $groupNames)) {
                Log::debug("Usuario {$uid} es admin por pertenecer al grupo ldapadmins");
                return 'admin';
            }
            
            if (in_array('profesores', $groupNames)) {
                Log::debug("Usuario {$uid} es profesor por pertenecer al grupo profesores");
                return 'profesor';
            }
            
            if (in_array('alumnos', $groupNames)) {
                Log::debug("Usuario {$uid} es alumno por pertenecer al grupo alumnos");
                return 'alumno';
            }
            
            // Por defecto, si pertenece a everybody, es usuario normal
            if (in_array('everybody', $groupNames)) {
                Log::debug("Usuario {$uid} asignado como usuario básico (pertenece a everybody)");
                return 'usuario';
            }
            
            Log::warning("Usuario {$uid} no pertenece a ningún grupo conocido, asignando rol por defecto");
            return 'usuario';
            
        } catch (\Exception $e) {
            Log::error("Error buscando grupos LDAP: " . $e->getMessage());
            Log::error("Traza: " . $e->getTraceAsString());
            
            // En caso de error, asignar un rol por defecto según el nombre de usuario
            if (strpos($username, 'admin') !== false || $username === 'LDAPAdmin') {
                return 'admin';
            } elseif (strpos($username, 'profesor') !== false) {
                return 'profesor';
            } elseif (strpos($username, 'alumno') !== false) {
                return 'alumno';
            }
            
            return 'usuario';
        }
    }
    
    /**
     * Cerrar sesión
     */
    public function logout(Request $request)
    {
        Auth::logout();
        session()->forget('auth_user');
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login');
    }
} 