<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use LdapRecord\Connection;
use LdapRecord\Container;
use LdapRecord\Models\OpenLDAP\User as LdapUser;
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
            
            // Intentar autenticación exclusivamente con LDAP usando LdapRecord
            if ($this->attemptLdapAuth($credentials)) {
               // Log::info('Usuario autenticado via LDAP: ' . $credentials['username']);
                return redirect()->route('dashboard.index');
            }
            
            // Si falla, devolver error
            return back()->withErrors([
                'username' => 'Las credenciales proporcionadas no son correctas o el usuario no existe en LDAP.',
            ])->withInput();
            
        } catch (\Exception $e) {
         //   Log::error('Error en autenticación: ' . $e->getMessage());
          //  Log::error('Traza: ' . $e->getTraceAsString());
            
            return back()->withErrors([
                'error' => 'Error al procesar la autenticación. Por favor, inténtelo de nuevo.',
            ]);
        }
    }
    
    /**
     * Intentar autenticación LDAP usando LdapRecord
     */
    private function attemptLdapAuth($credentials)
    {
        try {
            // Mostrar la configuración que estamos usando para depuración
            $config = Container::getDefaultConnection()->getConfiguration();
            $hosts = $config->get('hosts');
            $basedn = $config->get('base_dn');
            $adminDn = $config->get('username');
            
           Log::debug("Usando configuración LdapRecord: hosts=" . json_encode($hosts) . ", base_dn={$basedn}, admin_dn={$adminDn}");
            
            // Construir DN de usuario
            $userDn = "uid={$credentials['username']},ou=people," . $basedn;
            Log::debug("Intentando autenticar usuario con DN: {$userDn}");
            
            // Crear conexión manual para el usuario
            $connection = new Connection([
                'hosts' => $hosts,
                'base_dn' => $basedn,
                'username' => $userDn,
                'password' => $credentials['password'],
                'port' => $config->get('port', 389),
                'use_ssl' => $config->get('use_ssl', false),
                'use_tls' => $config->get('use_tls', false),
                'timeout' => $config->get('timeout', 5),
                'options' => [
                    LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_NEVER,
                    LDAP_OPT_REFERRALS => 0,
                ],
            ]);
            
            // Intentar conexión con credenciales de usuario
            try {
                $connection->connect();
                Log::debug("Conexión LdapRecord exitosa para usuario: {$credentials['username']}");
                
                // Usuario autenticado, buscar información adicional usando admin
                try {
                    // Crear conexión como admin para buscar información del usuario
                    $adminConnection = new Connection([
                        'hosts' => $hosts,
                        'base_dn' => $basedn,
                        'username' => $adminDn,
                        'password' => $config->get('password'),
                        'port' => $config->get('port', 389),
                        'use_ssl' => $config->get('use_ssl', false),
                        'use_tls' => $config->get('use_tls', false),
                        'timeout' => $config->get('timeout', 5),
                        'options' => [
                            LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_NEVER,
                            LDAP_OPT_REFERRALS => 0,
                        ],
                    ]);
                    
                    $adminConnection->connect();
                    Log::debug("Conexión admin LdapRecord exitosa");
                    
                    // Buscar usuario con la conexión admin
                    $query = $adminConnection->query();
                    $results = $query->where('objectClass', 'inetOrgPerson')
                                    ->where('uid', $credentials['username'])
                                    ->first();
                    
                    if ($results) {
                        Log::debug("Información de usuario encontrada: " . json_encode(array_keys($results)));
                        $processResult = $this->processLdapUser($results, $credentials);
                        Log::debug("Resultado del procesamiento: " . ($processResult ? 'Exitoso' : 'Fallido'));
                        return $processResult;
                    } else {
                        Log::warning("Usuario autenticado pero no encontrado en búsqueda LDAP: {$credentials['username']}");
                        return false;
                    }
                } catch (\Exception $e) {
                    Log::warning("Error al buscar información con admin: " . $e->getMessage());
                    return false;
                }
            } catch (\Exception $e) {
                Log::warning("Fallo en autenticación de usuario: " . $e->getMessage());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Error LdapRecord: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Procesar usuario LDAP y crear sesión
     */
    private function processLdapUser($ldapUser, $credentials)
    {
        try {
            // Los datos del usuario vienen de ldap_get_entries, que utiliza un formato específico
            Log::debug("Procesando datos de usuario LDAP: " . json_encode(array_keys($ldapUser)));
            
            // Determinar rol basado en grupos LDAP
            $role = $this->determineRoleFromLdapGroups($ldapUser, $credentials['username']);
            Log::debug("Rol asignado al usuario: {$role}");
            
            // Obtener valores de los atributos LDAP en formato nativo
            $cn = isset($ldapUser['cn'][0]) ? $ldapUser['cn'][0] : $credentials['username'];
            $mail = isset($ldapUser['mail'][0]) ? $ldapUser['mail'][0] : $credentials['username'] . '@test.tierno.es';
            $uid = isset($ldapUser['uid'][0]) ? $ldapUser['uid'][0] : $credentials['username'];
            
            Log::debug("Valores extraídos: CN=$cn, Mail=$mail, UID=$uid");
            
            try {
                // Buscar usuario por email o username
                $user = User::where('email', $mail)->orWhere('username', $uid)->first();
                
                if (!$user) {
                    // Si no existe, crear nuevo usuario
                    Log::debug("Usuario no encontrado en la base de datos, creando nuevo usuario");
                    $user = new User();
                    $user->name = $cn;
                    $user->email = $mail;
                    $user->username = $uid; // Asegurarnos de guardar el username
                    $user->password = Hash::make(Str::random(16));
                    $user->guid = $uid;
                    $user->domain = env('LDAP_BASE_DN', 'dc=tierno,dc=es');
                    $user->role = $role; // Establecer el rol correcto
                    $user->save();
                    
                    Log::debug("Usuario creado en base de datos: " . $user->id);
                } else {
                    // Actualizar usuario existente
                    Log::debug("Usuario encontrado en la base de datos, actualizando");
                    $user->name = $cn;
                    $user->email = $mail;
                    $user->username = $uid; // Asegurarnos de tener username
                    $user->guid = $uid;
                    $user->domain = env('LDAP_BASE_DN', 'dc=tierno,dc=es');
                    $user->role = $role; // Actualizar el rol
                    $user->save();
                    
                    Log::debug("Usuario actualizado en base de datos: " . $user->id);
                }
                
                try {
                    // Autenticar usuario local
                    Auth::login($user);
                    Log::debug("Usuario autenticado en el sistema con Auth::login()");
                    
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
                    
                    Log::debug("Datos guardados en sesión");
                    Log::info("Autenticación LDAP exitosa para usuario: {$credentials['username']} con rol: {$role}");
                    return true;
                } catch (\Exception $e) {
                    Log::error("Error en Auth::login o session(): " . $e->getMessage());
                    return false;
                }
            } catch (\Exception $e) {
                Log::error("Error al crear/actualizar usuario en base de datos: " . $e->getMessage());
                return false;
            }
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
            Log::debug("Usuario {$username} detectado directamente, asignando rol de administrador por nombre");
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
        
        Log::debug("UID extraído del objeto LDAP: $uid");
        
        try {
            // Si el UID parece ser LDAPAdmin, asignar rol admin sin más comprobaciones
            if ($uid === 'LDAPAdmin' || strtolower($uid) === 'ldapadmin') {
                Log::debug("Usuario con UID={$uid} detectado, asignando rol de administrador directamente");
                return 'admin';
            }
            
            // Para usuario ldap-admin, asignar rol admin directamente
            if ($uid === 'ldap-admin') {
                Log::debug("Usuario ldap-admin detectado por UID, asignando rol de administrador directamente");
                return 'admin';
            }
            
            // Configurar conexión LDAP usando variables del entorno
            // Obtener los mismos valores que en attemptLdapAuth para consistencia
            $ldapHost = env('LDAP_HOST', '172.19.0.4');
            $ldapPort = (int)env('LDAP_PORT', 389);
            $baseDn = env('LDAP_BASE_DN', 'dc=tierno,dc=es');
            $adminDn = env('LDAP_USERNAME', 'cn=admin,dc=tierno,dc=es');
            $adminPassword = env('LDAP_PASSWORD', 'admin');
            
            $ldapConfig = [
                'hosts' => [$ldapHost],
                'port' => $ldapPort,
                'base_dn' => $baseDn,
                'username' => $adminDn,
                'password' => $adminPassword,
                'use_ssl' => false,
                'use_tls' => false,
                'timeout' => 5,
                'options' => [
                    LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_NEVER,
                    LDAP_OPT_REFERRALS => 0,
                ],
            ];
            
            Log::debug("Usando configuración LDAP para buscar grupos: host={$ldapHost}, port={$ldapPort}");
            
            $ldap = new Connection($ldapConfig);
            $ldap->connect();
            
            $baseDn = $ldapConfig['base_dn'];
            
            Log::debug("Buscando grupos para el usuario con UID=$uid");
            
            // Buscar grupos a los que pertenece el usuario por memberUid (posixGroup)
            $groups = $ldap->query()
                ->where('objectClass', '=', 'posixGroup')
                ->where('memberUid', '=', $uid)
                ->get();
            
            Log::debug("Grupos encontrados por memberUid: " . count($groups));
            
            // También buscar grupos por uniqueMember (groupOfUniqueNames)
            $userDn = "uid={$uid},ou=people,{$baseDn}";
            Log::debug("Buscando grupos con uniqueMember={$userDn}");
            
            $uniqueGroups = $ldap->query()
                ->where('uniqueMember', '=', $userDn)
                ->get();
            
            Log::debug("Grupos encontrados por uniqueMember: " . count($uniqueGroups));
            
            // Combinar los resultados
            $groupNames = [];
            
            // Extraer nombres de grupos posixGroup
            foreach ($groups as $group) {
                $groupCn = null;
                if (is_object($group)) {
                    $groupCn = $group->getFirstAttribute('cn');
                } elseif (is_array($group) && isset($group['cn'])) {
                    $groupCn = is_array($group['cn']) ? $group['cn'][0] : $group['cn'];
                }
                
                if ($groupCn) {
                    $groupNames[] = $groupCn;
                    Log::debug("Grupo encontrado por memberUid: $groupCn");
                }
            }
            
            // Extraer nombres de grupos groupOfUniqueNames
            foreach ($uniqueGroups as $group) {
                $groupCn = null;
                if (is_object($group)) {
                    $groupCn = $group->getFirstAttribute('cn');
                } elseif (is_array($group) && isset($group['cn'])) {
                    $groupCn = is_array($group['cn']) ? $group['cn'][0] : $group['cn'];
                }
                
                if ($groupCn && !in_array($groupCn, $groupNames)) {
                    $groupNames[] = $groupCn;
                    Log::debug("Grupo encontrado por uniqueMember: $groupCn");
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
            
            // Si el usuario es ldap-admin pero no está en ningún grupo, asignar admin por defecto
            if ($uid === 'ldap-admin') {
                Log::debug("Usuario ldap-admin no pertenece a ningún grupo conocido, asignando rol admin por defecto");
                return 'admin';
            }
            
            Log::warning("Usuario {$uid} no pertenece a ningún grupo conocido, asignando rol por defecto");
            return 'usuario';
            
        } catch (\Exception $e) {
            Log::error("Error buscando grupos LDAP: " . $e->getMessage());
            Log::error("Traza: " . $e->getTraceAsString());
            
            // En caso de error, asignar un rol por defecto según el nombre de usuario
            if (strpos($username, 'admin') !== false || $username === 'LDAPAdmin') {
                Log::debug("Asignando rol admin por contener 'admin' en el nombre de usuario");
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