<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Config;
use App\Models\User;
use LdapRecord\Connection;
use LdapRecord\Models\Entry;

class LdapUserController extends Controller
{
    /**
     * Conexión LDAP
     */
    protected $ldap;

    /**
     * Constructor
     */
    public function __construct()
    {
        // No inicializar la conexión en el constructor, para evitar errores
    }

    /**
     * Mostrar todos los usuarios
     */
    public function index()
    {
        try {
            // Usar el mismo patrón que AuthController
            // Mostrar la configuración que estamos usando para depuración
            $config = \LdapRecord\Container::getDefaultConnection()->getConfiguration();
            $hosts = $config->get('hosts');
            $basedn = $config->get('base_dn');
            $adminDn = $config->get('username');
            
            Log::debug("Usando configuración LdapRecord: hosts=" . json_encode($hosts) . 
                    ", base_dn={$basedn}, admin_dn={$adminDn}");
            
            // Crear conexión como admin para buscar usuarios, igual que en AuthController
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
            
            // Conectar explícitamente
            $adminConnection->connect();
            Log::debug("Conexión admin LdapRecord exitosa");
            
            // Buscar usuarios en LDAP en la unidad organizativa people
            $peopleDn = "ou=people," . $basedn;
            Log::debug("Buscando usuarios en: {$peopleDn}");
            
            // Verificar si la OU de personas existe
            $peopleOuExists = $adminConnection->query()
                ->in($basedn)
                ->where('dn', '=', $peopleDn)
                ->exists();
                
            Log::debug("¿Existe la OU de personas?: " . ($peopleOuExists ? 'Sí' : 'No'));
            
            if (!$peopleOuExists) {
                Log::warning("La OU de personas no existe: {$peopleDn}");
            }
            
            // Buscar usuarios que sean inetOrgPerson
            $query = $adminConnection->query();
            $query->in($peopleDn);
            $query->where('objectclass', '=', 'inetOrgPerson');
            
            $ldapUsers = $query->get();
            
            Log::debug("Usuarios LDAP encontrados: " . count($ldapUsers));
            
            $users = [];
            
            foreach ($ldapUsers as $ldapUser) {
                // Verificar si es una persona
                $objectClasses = [];
                if (is_array($ldapUser) && isset($ldapUser['objectclass'])) {
                    $objectClasses = (array)$ldapUser['objectclass'];
                } elseif (method_exists($ldapUser, 'getObjectClasses')) {
                    $objectClasses = $ldapUser->getObjectClasses();
                }
                
                if (!in_array('person', $objectClasses) && !in_array('inetOrgPerson', $objectClasses)) {
                    Log::debug("Omitiendo objeto que no es persona/inetOrgPerson");
                    continue;
                }
                
                $username = '';
                $userDn = '';
                
                if (is_array($ldapUser)) {
                    $username = isset($ldapUser['uid']) ? (is_array($ldapUser['uid']) ? $ldapUser['uid'][0] : $ldapUser['uid']) : '';
                    $userDn = $ldapUser['dn'];
                } else {
                    $username = $ldapUser->getFirstAttribute('uid');
                    $userDn = $ldapUser->getDn();
                }
                
                if (empty($username)) {
                    Log::warning("Usuario LDAP sin UID: " . $userDn);
                    continue;
                }
                
                Log::debug("Procesando usuario LDAP: {$username}");
                
                $groups = $this->getUserGroups($username, $adminConnection, $basedn);
                
                $users[] = [
                    'dn' => $userDn,
                    'username' => $username,
                    'name' => is_array($ldapUser) ? ($ldapUser['cn'][0] ?? $username) : $ldapUser->getFirstAttribute('cn', $username),
                    'email' => is_array($ldapUser) ? ($ldapUser['mail'][0] ?? '') : $ldapUser->getFirstAttribute('mail', ''),
                    'groups' => $groups,
                    'last_login' => null,
                    'enabled' => true
                ];
            }
            
            return view('admin.users.index', compact('users'));
        } catch (\Exception $e) {
            Log::error('Error al obtener usuarios LDAP: ' . $e->getMessage());
            Log::error('Traza: ' . $e->getTraceAsString());
            
            // Recopilar información de diagnóstico para mostrar en la vista
            $diagnostico = [
                'error' => $e->getMessage(),
                'traza' => $e->getTraceAsString(),
            ];
            
            // Si tenemos configuración, mostrar más detalles
            try {
                $config = \LdapRecord\Container::getDefaultConnection()->getConfiguration();
                $diagnostico['hosts'] = $config->get('hosts');
                $diagnostico['base_dn'] = $config->get('base_dn');
                $diagnostico['username'] = $config->get('username');
                $diagnostico['port'] = $config->get('port', 389);
            } catch (\Exception $configError) {
                $diagnostico['error_config'] = $configError->getMessage();
            }
            
            return view('admin.users.index', [
                'users' => [],
                'error' => $e->getMessage(),
                'diagnostico' => $diagnostico
            ]);
        }
    }

    /**
     * Mostrar formulario para crear un nuevo usuario
     */
    public function create()
    {
        // Obtener grupos de LDAP
        $ldapGroups = $this->getLdapGroups();
        
        $roles = [
            'admin' => 'Administrador',
            'profesor' => 'Profesor',
            'editor' => 'Editor de contenido',
            'usuario' => 'Usuario estándar'
        ];
        
        return view('admin.users.create', compact('roles', 'ldapGroups'));
    }

    /**
     * Almacenar un nuevo usuario
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:50|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|string|in:admin,profesor,editor,usuario',
            'groups' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Si LDAP está disponible, crear usuario allí primero
            if ($this->ldap && $this->ldap->isConnected()) {
                $baseDn = Config::get('ldap.connections.default.base_dn');
                $userDn = "cn={$request->username}," . $baseDn;
                
                // Crear nuevo usuario en LDAP
                $entry = new Entry();
                $entry->setDn($userDn);
                $entry->setAttribute('cn', $request->username);
                $entry->setAttribute('objectclass', ['top', 'person', 'organizationalPerson', 'inetOrgPerson']);
                $entry->setAttribute('sn', $request->name);
                $entry->setAttribute('displayname', $request->name);
                $entry->setAttribute('mail', $request->email);
                $entry->setAttribute('userpassword', $this->ldapEncodedPassword($request->password));
                
                $this->ldap->add($entry);
                
                // Añadir a grupos si se especificaron
                if ($request->has('groups') && !empty($request->groups)) {
                    foreach ($request->groups as $groupDn) {
                        $group = $this->ldap->read($groupDn);
                        if ($group) {
                            $members = $group->getAttribute('member', []);
                            $members[] = $userDn;
                            $this->ldap->modify($groupDn, ['member' => $members]);
                        }
                    }
                }
                
                $ldapGuid = $this->ldap->read($userDn)->getAttribute('objectguid')[0] ?? null;
                
                // Crear usuario local vinculado a LDAP
                $user = User::create([
                    'name' => $request->name,
                    'username' => $request->username,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'role' => $request->role,
                    'guid' => $ldapGuid,
                    'domain' => $baseDn
                ]);
                
                Log::info('Usuario LDAP creado: ' . $request->username);
            } else {
                // Crear solo usuario local
                $user = User::create([
                    'name' => $request->name,
                    'username' => $request->username,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'role' => $request->role
                ]);
                
                Log::info('Usuario local creado: ' . $user->username);
            }
            
            // Registrar la acción en logs
            Log::channel('activity')->info('Usuario LDAP creado', [
                'action' => 'Crear Usuario',
                'username' => $request->username
            ]);
            
            return redirect()->route('ldap.users.index')
                ->with('success', 'Usuario creado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al crear usuario: ' . $e->getMessage());
            Log::channel('activity')->error('Error al crear usuario LDAP: ' . $e->getMessage(), [
                'action' => 'Error',
                'username' => $request->username
            ]);
            return redirect()->back()
                ->with('error', 'Error al crear el usuario: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Mostrar un usuario específico
     */
    public function show($dn)
    {
        try {
            if ($this->ldap && $this->ldap->isConnected()) {
                // Decodificar DN para URL
                $dn = urldecode($dn);
                
                // Buscar usuario en LDAP
                $ldapUser = $this->ldap->read($dn);
                
                if ($ldapUser) {
                    $user = [
                        'dn' => $ldapUser['dn'],
                        'username' => $ldapUser['cn'][0] ?? '',
                        'name' => $ldapUser['displayname'][0] ?? $ldapUser['cn'][0] ?? '',
                        'email' => $ldapUser['mail'][0] ?? '',
                        'groups' => $this->extractGroupNames($ldapUser['memberof'] ?? []),
                        'created_at' => null,
                        'last_login' => null,
                        'enabled' => true
                    ];
                    
                    return view('admin.users.show', compact('user'));
                }
            }
            
            // Si no encontramos en LDAP, buscar en DB local por DN/GUID
            $localUser = User::where('guid', $dn)->first();
            
            if ($localUser) {
                $user = [
                    'dn' => $localUser->guid,
                    'username' => $localUser->username,
                    'name' => $localUser->name,
                    'email' => $localUser->email,
                    'groups' => [],
                    'created_at' => $localUser->created_at->format('Y-m-d H:i:s'),
                    'last_login' => $localUser->updated_at->format('Y-m-d H:i:s'),
                    'enabled' => true
                ];
                
                return view('admin.users.show', compact('user'));
            }
            
            return redirect()->route('ldap.users.index')
                ->with('error', 'Usuario no encontrado.');
                
        } catch (\Exception $e) {
            Log::error('Error al obtener usuario LDAP: ' . $e->getMessage());
            return redirect()->route('ldap.users.index')
                ->with('error', 'Error al obtener el usuario: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar formulario para editar un usuario
     */
    public function edit($dn)
    {
        try {
            $user = null;
            $ldapGroups = $this->getLdapGroups();
            $userGroups = [];
            
            if ($this->ldap && $this->ldap->isConnected()) {
                // Decodificar DN para URL
                $dn = urldecode($dn);
                
                // Buscar usuario en LDAP
                $ldapUser = $this->ldap->read($dn);
                
                if ($ldapUser) {
                    $user = [
                        'dn' => $ldapUser['dn'],
                        'username' => $ldapUser['cn'][0] ?? '',
                        'name' => $ldapUser['displayname'][0] ?? $ldapUser['cn'][0] ?? '',
                        'email' => $ldapUser['mail'][0] ?? '',
                        'groups' => $ldapUser['memberof'] ?? []
                    ];
                    
                    $userGroups = $user['groups'];
                }
            }
            
            // Si no encontramos en LDAP, buscar en DB local por DN/GUID
            if (!$user) {
                $localUser = User::where('guid', $dn)->first();
                
                if ($localUser) {
                    $user = [
                        'dn' => $localUser->guid,
                        'username' => $localUser->username,
                        'name' => $localUser->name,
                        'email' => $localUser->email,
                        'groups' => []
                    ];
                }
            }
            
            if (!$user) {
                return redirect()->route('ldap.users.index')
                    ->with('error', 'Usuario no encontrado.');
            }
            
            $roles = [
                'admin' => 'Administrador',
                'profesor' => 'Profesor',
                'editor' => 'Editor de contenido',
                'usuario' => 'Usuario estándar'
            ];
            
            // Buscar rol actual en la base de datos local
            $localUser = User::where('username', $user['username'])->first();
            $currentRole = $localUser->role ?? 'usuario';
            
            return view('admin.users.edit', compact('user', 'roles', 'ldapGroups', 'userGroups', 'currentRole'));
            
        } catch (\Exception $e) {
            Log::error('Error al editar usuario LDAP: ' . $e->getMessage());
            return redirect()->route('ldap.users.index')
                ->with('error', 'Error al editar el usuario: ' . $e->getMessage());
        }
    }

    /**
     * Actualizar un usuario
     */
    public function update(Request $request, $dn)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'nullable|string|min:6|confirmed',
            'role' => 'required|string|in:admin,profesor,editor,usuario',
            'groups' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            // Decodificar DN para URL
            $dn = urldecode($dn);
            
            if ($this->ldap && $this->ldap->isConnected()) {
                // Verificar si el usuario existe en LDAP
                $ldapUser = $this->ldap->read($dn);
                
                if ($ldapUser) {
                    $username = $ldapUser['cn'][0] ?? '';
                    
                    // Actualizar atributos en LDAP
                    $modifyAttributes = [
                        'displayname' => $request->name,
                        'mail' => $request->email
                    ];
                    
                    if ($request->filled('password')) {
                        $modifyAttributes['userpassword'] = $this->ldapEncodedPassword($request->password);
                    }
                    
                    $this->ldap->modify($dn, $modifyAttributes);
                    
                    // Actualizar pertenencia a grupos si se especificaron
                    if ($request->has('groups')) {
                        // Obtener los grupos actuales
                        $currentGroups = $ldapUser['memberof'] ?? [];
                        
                        // Grupos a agregar
                        $groupsToAdd = array_diff($request->groups, $currentGroups);
                        
                        // Grupos a quitar
                        $groupsToRemove = array_diff($currentGroups, $request->groups);
                        
                        // Agregar a nuevos grupos
                        foreach ($groupsToAdd as $groupDn) {
                            $group = $this->ldap->read($groupDn);
                            if ($group) {
                                $members = $group->getAttribute('member', []);
                                $members[] = $dn;
                                $this->ldap->modify($groupDn, ['member' => $members]);
                            }
                        }
                        
                        // Quitar de grupos
                        foreach ($groupsToRemove as $groupDn) {
                            $group = $this->ldap->read($groupDn);
                            if ($group) {
                                $members = $group->getAttribute('member', []);
                                $members = array_filter($members, function($member) use ($dn) {
                                    return $member !== $dn;
                                });
                                $this->ldap->modify($groupDn, ['member' => $members]);
                            }
                        }
                    }
                    
                    // Actualizar o crear usuario local vinculado a LDAP
                    $localUser = User::where('username', $username)->first();
                    
                    if ($localUser) {
                        $userData = [
                            'name' => $request->name,
                            'email' => $request->email,
                            'role' => $request->role
                        ];
                        
                        if ($request->filled('password')) {
                            $userData['password'] = Hash::make($request->password);
                        }
                        
                        $localUser->update($userData);
                    } else {
                        // Crear usuario local si no existe
                        User::create([
                            'name' => $request->name,
                            'username' => $username,
                            'email' => $request->email,
                            'password' => Hash::make($request->password ?? str_random(16)),
                            'role' => $request->role,
                            'guid' => $ldapUser['objectguid'][0] ?? null,
                            'domain' => Config::get('ldap.connections.default.base_dn')
                        ]);
                    }
                    
                    Log::info('Usuario LDAP actualizado: ' . $username);
                } else {
                    // Actualizar solo usuario local
                    $localUser = User::where('guid', $dn)->first();
                    
                    if (!$localUser) {
                        return redirect()->route('ldap.users.index')
                            ->with('error', 'Usuario no encontrado en LDAP ni en la base de datos local.');
                    }
                    
                    $userData = [
                        'name' => $request->name,
                        'email' => $request->email,
                        'role' => $request->role
                    ];
                    
                    if ($request->filled('password')) {
                        $userData['password'] = Hash::make($request->password);
                    }
                    
                    $localUser->update($userData);
                    Log::info('Usuario local actualizado: ' . $localUser->username);
                }
            } else {
                // Actualizar solo usuario local
                $localUser = User::where('guid', $dn)->first();
                
                if (!$localUser) {
                    return redirect()->route('ldap.users.index')
                        ->with('error', 'Usuario no encontrado en la base de datos local y LDAP no está disponible.');
                }
                
                $userData = [
                    'name' => $request->name,
                    'email' => $request->email,
                    'role' => $request->role
                ];
                
                if ($request->filled('password')) {
                    $userData['password'] = Hash::make($request->password);
                }
                
                $localUser->update($userData);
                Log::info('Usuario local actualizado: ' . $localUser->username);
            }
            
            // Registrar la acción en logs
            Log::channel('activity')->info('Usuario LDAP actualizado', [
                'action' => 'Actualizar Usuario',
                'username' => $request->username
            ]);
            
            return redirect()->route('ldap.users.index')
                ->with('success', 'Usuario actualizado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al actualizar usuario: ' . $e->getMessage());
            Log::channel('activity')->error('Error al actualizar usuario LDAP: ' . $e->getMessage(), [
                'action' => 'Error',
                'username' => $request->username
            ]);
            return redirect()->back()
                ->with('error', 'Error al actualizar el usuario: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Eliminar un usuario
     */
    public function destroy($dn)
    {
        try {
            // Decodificar DN para URL
            $dn = urldecode($dn);
            
            if ($this->ldap && $this->ldap->isConnected()) {
                // Verificar si el usuario existe en LDAP
                $ldapUser = $this->ldap->read($dn);
                
                if ($ldapUser) {
                    // Obtener nombre de usuario para buscar en la base de datos local
                    $username = $ldapUser['cn'][0] ?? '';
                    
                    // Eliminar de LDAP
                    $this->ldap->delete($dn);
                    
                    // Eliminar usuario local vinculado a LDAP
                    $localUser = User::where('username', $username)->first();
                    
                    if ($localUser) {
                        $localUser->delete();
                    }
                    
                    Log::info('Usuario LDAP eliminado: ' . $dn);
                    
                    // Registrar la acción en logs
                    Log::channel('activity')->info('Usuario LDAP eliminado', [
                        'action' => 'Eliminar Usuario',
                        'username' => $username
                    ]);
                    
                    return redirect()->route('ldap.users.index')
                        ->with('success', 'Usuario eliminado correctamente.');
                }
            }
            
            // Si no encontramos en LDAP o LDAP no está disponible, eliminar solo de la base de datos local
            $localUser = User::where('guid', $dn)->first();
            
            if ($localUser) {
                $username = $localUser->username;
                $localUser->delete();
                
                Log::info('Usuario local eliminado: ' . $username);
                
                // Registrar la acción en logs
                Log::channel('activity')->info('Usuario local eliminado', [
                    'action' => 'Eliminar Usuario',
                    'username' => $username
                ]);
                
                return redirect()->route('ldap.users.index')
                    ->with('success', 'Usuario eliminado correctamente de la base de datos local.');
            }
            
            return redirect()->route('ldap.users.index')
                ->with('error', 'Usuario no encontrado en LDAP ni en la base de datos local.');
                
        } catch (\Exception $e) {
            Log::error('Error al eliminar usuario: ' . $e->getMessage());
            Log::channel('activity')->error('Error al eliminar usuario LDAP: ' . $e->getMessage(), [
                'action' => 'Error',
                'username' => $username
            ]);
            return redirect()->back()
                ->with('error', 'Error al eliminar el usuario: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar logs de actividad
     */
    public function logs()
    {
        // En un sistema real, obtendríamos los logs de la base de datos
        $logs = [
            [
                'id' => 1,
                'usuario' => 'admin',
                'accion' => 'Creación de usuario',
                'descripcion' => 'Se creó el usuario profesor1',
                'fecha' => now()->subDays(1)->format('Y-m-d H:i:s'),
                'ip' => '192.168.1.100'
            ],
            [
                'id' => 2,
                'usuario' => 'admin',
                'accion' => 'Actualización de grupo',
                'descripcion' => 'Se añadió el usuario profesor2 al grupo Profesores',
                'fecha' => now()->subDays(2)->format('Y-m-d H:i:s'),
                'ip' => '192.168.1.100'
            ],
            [
                'id' => 3,
                'usuario' => 'admin',
                'accion' => 'Eliminación de usuario',
                'descripcion' => 'Se eliminó el usuario alumno1',
                'fecha' => now()->subDays(3)->format('Y-m-d H:i:s'),
                'ip' => '192.168.1.100'
            ]
        ];
        
        return view('admin.logs', compact('logs'));
    }
    
    /**
     * Obtener grupos desde LDAP
     */
    private function getLdapGroups()
    {
        try {
            if (!$this->ldap || !$this->ldap->isConnected()) {
                return [];
            }
            
            $ldapGroups = $this->ldap->query()
                ->where('objectclass', 'group')
                ->get();
            
            $groups = [];
            
            foreach ($ldapGroups as $group) {
                $groups[] = [
                    'dn' => $group['dn'],
                    'name' => $group['cn'][0] ?? '',
                    'description' => $group['description'][0] ?? '',
                    'members_count' => isset($group['member']) ? count($group['member']) : 0
                ];
            }
            
            return $groups;
        } catch (\Exception $e) {
            Log::error('Error al obtener grupos LDAP: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Extraer nombres de grupos desde DNs
     */
    private function extractGroupNames(array $groupDNs)
    {
        $groupNames = [];
        
        foreach ($groupDNs as $dn) {
            // Extraer el nombre CN del DN
            if (preg_match('/CN=([^,]+)/i', $dn, $matches)) {
                $groupNames[] = $matches[1];
            }
        }
        
        return $groupNames;
    }
    
    /**
     * Codificar contraseña para LDAP
     */
    private function ldapEncodedPassword($password)
    {
        // La función depende del tipo de servidor LDAP
        // Para Active Directory
        if (strpos(Config::get('ldap.connections.default.hosts')[0], 'ad.') !== false) {
            return iconv('UTF-8', 'UTF-16LE', '"' . $password . '"');
        }
        
        // Para OpenLDAP
        return '{SSHA}' . base64_encode(sha1($password . openssl_random_pseudo_bytes(4), true) . openssl_random_pseudo_bytes(4));
    }

    /**
     * Obtener los grupos a los que pertenece un usuario
     */
    private function getUserGroups($username, $adminConnection, $basedn)
    {
        try {
            $groupsDn = "ou=groups," . $basedn;
            $userDn = "uid=$username,ou=people,$basedn";
            
            $groups = $adminConnection->query()
                ->in($groupsDn)
                ->whereHas('objectclass')
                ->where('uniqueMember', $userDn)
                ->get();
                
            $groupNames = [];
            foreach ($groups as $group) {
                $groupNames[] = $group['cn'][0] ?? '';
            }
            
            return $groupNames;
        } catch (\Exception $e) {
            Log::error('Error al obtener grupos del usuario: ' . $e->getMessage());
            return [];
        }
    }
} 