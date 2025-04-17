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
        try {
            // Inicializar conexión LDAP con el administrador del directorio
            $this->ldap = new Connection([
                'hosts' => [env('LDAP_HOST', 'openldap-osixia')],
                'port' => env('LDAP_PORT', 389),
                'base_dn' => env('LDAP_BASE_DN', 'dc=test,dc=tierno,dc=es'),
                'username' => env('LDAP_USERNAME', 'cn=admin,dc=test,dc=tierno,dc=es'), 
                'password' => env('LDAP_PASSWORD', 'admin'),
            ]);
            $this->ldap->connect();
            Log::info('Conexión LDAP establecida correctamente con el administrador');
        } catch (\Exception $e) {
            Log::error('Error conectando a LDAP: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar todos los usuarios
     */
    public function index()
    {
        try {
            // Verificar la conexión LDAP
            if (!$this->ldap || !$this->ldap->isConnected()) {
                throw new \Exception('No se pudo establecer conexión con el servidor LDAP');
            }
            
            // Buscar usuarios en LDAP en la unidad organizativa people
            $baseDn = env('LDAP_BASE_DN', 'dc=test,dc=tierno,dc=es');
            $peopleDn = "ou=people," . $baseDn;
            
            $ldapUsers = $this->ldap->query()
                ->in($peopleDn)
                ->whereHas('objectclass')
                ->get();
            
            Log::debug("Usuarios LDAP encontrados: " . count($ldapUsers));
            
            $users = [];
            
            foreach ($ldapUsers as $ldapUser) {
                // Verificar si es una persona
                if (!in_array('person', $ldapUser['objectclass'])) {
                    continue;
                }
                
                $username = $ldapUser['uid'][0] ?? '';
                $groups = $this->getUserGroups($username);
                
                $users[] = [
                    'dn' => $ldapUser['dn'],
                    'username' => $username,
                    'name' => $ldapUser['cn'][0] ?? $username,
                    'email' => $ldapUser['mail'][0] ?? '',
                    'groups' => $groups,
                    'last_login' => null,
                    'enabled' => true
                ];
            }
            
            return view('admin.users.index', compact('users'));
        } catch (\Exception $e) {
            Log::error('Error al obtener usuarios LDAP: ' . $e->getMessage());
            return view('admin.users.index', ['users' => [], 'error' => $e->getMessage()]);
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
            
            return redirect()->route('ldap.users.index')
                ->with('success', 'Usuario creado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al crear usuario: ' . $e->getMessage());
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
            
            return redirect()->route('ldap.users.index')
                ->with('success', 'Usuario actualizado correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al actualizar usuario: ' . $e->getMessage());
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
                
                return redirect()->route('ldap.users.index')
                    ->with('success', 'Usuario eliminado correctamente de la base de datos local.');
            }
            
            return redirect()->route('ldap.users.index')
                ->with('error', 'Usuario no encontrado en LDAP ni en la base de datos local.');
                
        } catch (\Exception $e) {
            Log::error('Error al eliminar usuario: ' . $e->getMessage());
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
    private function getUserGroups($username)
    {
        try {
            $baseDn = env('LDAP_BASE_DN', 'dc=test,dc=tierno,dc=es');
            $groupsDn = "ou=groups," . $baseDn;
            $userDn = "uid=$username,ou=people,$baseDn";
            
            $groups = $this->ldap->query()
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