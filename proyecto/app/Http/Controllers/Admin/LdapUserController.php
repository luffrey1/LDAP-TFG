<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use LdapRecord\Models\ActiveDirectory\User;
use LdapRecord\Models\ActiveDirectory\Group;
use LdapRecord\Connection;
use Exception;

class LdapUserController extends Controller
{
    protected $connection;
    protected $baseDn = 'dc=test,dc=tierno,dc=es';
    protected $peopleOu = 'ou=people,dc=test,dc=tierno,dc=es';
    protected $groupsOu = 'ou=groups,dc=test,dc=tierno,dc=es';
    protected $adminGroupDn = 'cn=ldapadmins,ou=groups,dc=test,dc=tierno,dc=es';
    protected $profesoresGroupDn = 'cn=profesores,ou=groups,dc=test,dc=tierno,dc=es';
    protected $alumnosGroupDn = 'cn=alumnos,ou=groups,dc=test,dc=tierno,dc=es';
    
    public function __construct()
    {
        // Usar la configuración del archivo config/ldap.php
        $config = config('ldap.connections.default');
        
        $this->connection = new Connection([
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
        
    }

    /**
     * Display a listing of the users.
     */
    public function index(Request $request)
    {
        try {
            // Intentar conectar con LDAP
            try {
                $this->connection->connect();
            } catch (\Exception $connectException) {
                Log::error("Error al conectar con el servidor LDAP: " . $connectException->getMessage());
                
                // Devolver vista con mensaje de error pero sin datos
                return view('admin.users.index', [
                    'users' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 10),
                    'userGroups' => [],
                    'adminUsers' => [],
                    'search' => $request->input('search', ''),
                    'selectedGroup' => $request->input('group', ''),
                    'connectionError' => true,
                    'errorMessage' => 'No se pudo conectar al servidor LDAP. Por favor, verifique la conexión e inténtelo de nuevo.',
                    'diagnostico' => [
                        'error' => $connectException->getMessage(),
                        'hosts' => config('ldap.connections.default.hosts'),
                        'port' => config('ldap.connections.default.port'),
                        'base_dn' => config('ldap.connections.default.base_dn'),
                        'username' => config('ldap.connections.default.username'),
                    ]
                ]);
            }
            
            $search = $request->input('search', '');
            $filter = $request->input('group', '');
            $query = $this->connection->query();
            
            // Verificar si la OU de personas existe
            $peopleOuExists = $this->connection->query()
                ->in($this->baseDn)
                ->where('dn', '=', $this->peopleOu)
                ->exists();
                
            if (!$peopleOuExists) {
                Log::warning("La OU de personas no existe, buscando en toda la base");
                $query->in($this->baseDn);
            } else {
                $query->in($this->peopleOu);
            }
            
            // Buscar inetOrgPerson
            $query->where('objectclass', '=', 'inetOrgPerson');
            
            // Aplicar búsqueda si existe
            if (!empty($search)) {
                $query->whereContains('cn', $search)
                    ->orWhereContains('uid', $search)
                    ->orWhereContains('mail', $search);
            }
            
            // Ejecutar la consulta
            $users = $query->get();
            
            // Obtener usuarios admin para marcarlos
            $adminGroup = $this->connection->query()
                ->in($this->adminGroupDn)
                ->first();
            
            // Buscar con una consulta más genérica si no se encuentra el grupo de admins específico
            $adminUsers = [];
            if ($adminGroup && isset($adminGroup['uniquemember'])) {
                $adminUsers = is_array($adminGroup['uniquemember']) 
                    ? $adminGroup['uniquemember'] 
                    : [$adminGroup['uniquemember']];
            } else {
                // Buscar cualquier grupo de admins en toda la base
                $possibleAdminGroups = $this->connection->query()
                    ->in($this->baseDn)
                    ->whereContains('cn', 'admin')
                    ->get();
                    
                foreach ($possibleAdminGroups as $group) {
                    if (isset($group['uniquemember'])) {
                        $members = is_array($group['uniquemember']) 
                            ? $group['uniquemember'] 
                            : [$group['uniquemember']];
                        $adminUsers = array_merge($adminUsers, $members);
                    }
                }
            }
            
            // Obtener todos los grupos disponibles para verificación
            $allGroups = $this->connection->query()
                ->in($this->baseDn)
                ->rawFilter('(|(objectclass=groupOfUniqueNames)(objectclass=posixGroup))')
                ->get();
                
            // Obtener grupos para cada usuario
            $userGroups = [];
            $filteredUsers = collect();
            
            foreach ($users as $user) {
                // Verificar si $user es un objeto o un array
                if (is_array($user)) {
                    // Si es un array, extraer uid directamente
                    $uid = $user['uid'][0] ?? '';
                    $userDn = $user['dn'] ?? '';
                } else {
                    // Si es un objeto, usar los métodos
                    $uid = $user->getFirstAttribute('uid');
                    $userDn = $user->getDn();
                }
                
                if (empty($uid) || empty($userDn)) continue;
                
                $userGroups[$uid] = [];
                $groups = $this->getUserGroups($userDn);
                $groupNames = [];
                
                foreach ($groups as $group) {
                    // Verificar si $group es un objeto o un array
                    if (is_array($group)) {
                        $cn = $group['cn'][0] ?? '';
                    } else {
                        $cn = $group->getFirstAttribute('cn');
                    }
                    
                    if ($cn) {
                        $userGroups[$uid][] = $cn;
                        $groupNames[] = $cn;
                    }
                }
                
                // Aplicar filtro por grupo si existe
                if (!empty($filter) && !in_array($filter, $groupNames)) {
                    continue;
                }
                
                // Codificar el DN para usar en las URLs
                if (is_array($user)) {
                    $user['encoded_dn'] = base64_encode($userDn);
                } else {
                    $user->encoded_dn = base64_encode($userDn);
                }
                
                $filteredUsers->push($user);
            }
            
            // Paginar los resultados manualmente
            $perPage = 10;
            $page = $request->input('page', 1);
            $total = $filteredUsers->count();
            
            // Crear paginador personalizado
            $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
                $filteredUsers->forPage($page, $perPage),
                $total,
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
            
            // También enviar la lista de grupos para el selector
            $groupList = collect($allGroups)->map(function($group) {
                return is_array($group) ? ($group['cn'][0] ?? '') : $group->getFirstAttribute('cn');
            })->filter()->unique()->values()->all();
            
            return view('admin.users.index', [
                'users' => $paginator,
                'userGroups' => $userGroups,
                'adminUsers' => $adminUsers,
                'search' => $search,
                'selectedGroup' => $filter,
                'groupList' => $groupList,
                'total' => $total,
                'perPage' => $perPage
            ]);
            
        } catch (Exception $e) {
            Log::error('Error al obtener usuarios LDAP: ' . $e->getMessage());
            Log::error('Traza: ' . $e->getTraceAsString());
            return back()->with('error', 'Error al obtener los usuarios: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        try {
            $this->connection->connect();
            
            // Buscar todos los grupos con depuración mejorada
            Log::debug("Iniciando método create - Buscando grupos para selector");
            
            // Verificar si la OU de grupos existe
            $groupsOuExists = $this->connection->query()
                ->in($this->baseDn)
                ->where('dn', '=', $this->groupsOu)
                ->exists();
                
            Log::debug("¿Existe la OU de grupos?: " . ($groupsOuExists ? 'Sí' : 'No'));
            
            $groups = [];
            
            try {
                // Buscar grupos en toda la base LDAP, no solo en la OU específica
                // y buscar tanto posixGroup como groupOfUniqueNames
                $groups = $this->connection->query()
                    ->in($this->baseDn) // Cambiar para buscar en toda la base
                    ->rawFilter('(|(objectclass=groupOfUniqueNames)(objectclass=posixGroup))')
                    ->get();
                
                Log::debug("Método create - Grupos encontrados: " . count($groups));
                
                // Verificar si se encontraron grupos
                if (empty($groups)) {
                    Log::error("Método create - No se encontraron grupos en el sistema");
                    
                    // Intento alternativo: buscar cualquier objeto que podría ser un grupo
                    $potentialGroups = $this->connection->query()
                        ->in($this->baseDn)
                        ->whereHas('cn')
                        ->get();
                        
                    Log::debug("Objetos potenciales con atributo CN: " . count($potentialGroups));
                    
                    foreach ($potentialGroups as $index => $obj) {
                        $dn = is_array($obj) ? $obj['dn'] : $obj->getDn();
                        $classes = is_array($obj) ? ($obj['objectclass'] ?? []) : $obj->getAttributes()['objectclass'] ?? [];
                        
                        if (is_array($classes)) {
                            $classesStr = implode(', ', $classes);
                        } else {
                            $classesStr = (string)$classes;
                        }
                        
                        Log::debug("Objeto #{$index}: DN = {$dn}, Classes = {$classesStr}");
                    }
                } else {
                    // Verificar que los grupos tengan los atributos esperados
                    $groupDebug = [];
                    foreach ($groups as $key => $group) {
                        if (is_array($group)) {
                            $groupDebug[$key] = [
                                'es_array' => true,
                                'tiene_cn' => isset($group['cn']),
                                'cn_valor' => isset($group['cn']) ? json_encode($group['cn']) : 'no hay',
                                'keys' => array_keys($group),
                                'dn' => $group['dn']
                            ];
                        } else {
                            $groupDebug[$key] = [
                                'es_array' => false,
                                'clase' => get_class($group),
                                'tiene_cn' => method_exists($group, 'getFirstAttribute') && $group->getFirstAttribute('cn') !== null,
                                'cn_valor' => method_exists($group, 'getFirstAttribute') ? $group->getFirstAttribute('cn') : 'método no existe',
                                'dn' => $group->getDn()
                            ];
                        }
                    }
                    Log::debug("Método create - Debug de grupos: " . json_encode($groupDebug));
                }
            } catch (Exception $e) {
                Log::error('Método create - Error al obtener grupos: ' . $e->getMessage());
                Log::error('Traza: ' . $e->getTraceAsString());
            }
            
            return view('admin.users.create', [
                'groups' => $groups
            ]);
            
        } catch (Exception $e) {
            Log::error('Error al cargar el formulario de creación: ' . $e->getMessage());
            Log::error('Traza: ' . $e->getTraceAsString());
            return back()->with('error', 'Error al cargar el formulario: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created user in LDAP.
     */
    public function store(Request $request)
    {
        $request->validate([
            'uid' => 'required|string|max:50',
            'nombre' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'email' => 'required|email|max:100',
            'password' => 'required|string|min:6',
            'grupos' => 'required|array|min:1'
        ]);
        
        try {
            $this->connection->connect();
            
            // Comprobar si ya existe el usuario
            $existingUser = $this->connection->query()
                ->in($this->peopleOu)
                ->where('uid', '=', $request->uid)
                ->first();
                
            if ($existingUser) {
                return back()->withInput()->with('error', 'El usuario ya existe en el sistema');
            }
            
            // Preparar datos del usuario
            $userDn = 'uid=' . $request->uid . ',' . $this->peopleOu;
            $userData = [
                'objectclass' => ['top', 'person', 'organizationalPerson', 'inetOrgPerson', 'posixAccount'],
                'cn' => $request->nombre . ' ' . $request->apellidos,
                'sn' => $request->apellidos,
                'givenname' => $request->nombre,
                'uid' => $request->uid,
                'mail' => $request->email,
                'userpassword' => $this->hashPassword($request->password),
                'homedirectory' => '/home/' . $request->uid,
                'gidnumber' => '9000',  // GID por defecto
                'uidnumber' => $this->getNextUidNumber()
            ];
            
            // Crear el usuario utilizando el modelo de LdapRecord
            $user = new User();
            $user->setDn($userDn);
            $user->cn = $request->nombre . ' ' . $request->apellidos;
            $user->sn = $request->apellidos;
            $user->givenname = $request->nombre;
            $user->uid = $request->uid;
            $user->mail = $request->email;
            $user->userpassword = $this->hashPassword($request->password);
            $user->homedirectory = '/home/' . $request->uid;
            $user->gidnumber = '9000';
            $user->uidnumber = $this->getNextUidNumber();
            $user->objectclass = ['top', 'person', 'organizationalPerson', 'inetOrgPerson', 'posixAccount'];

            // Guardar el usuario en LDAP
            $user->save();
            
            // Añadir usuario a los grupos seleccionados
            foreach ($request->grupos as $groupName) {
                Log::debug("Procesando grupo seleccionado: " . $groupName);
                
                $groupDn = null;
                
                // Usar DNs conocidos para grupos comunes
                if ($groupName === 'profesores') {
                    $groupDn = $this->profesoresGroupDn;
                    Log::debug("Usando DN conocido para profesores: " . $groupDn);
                } else if ($groupName === 'alumnos') {
                    $groupDn = $this->alumnosGroupDn;
                    Log::debug("Usando DN conocido para alumnos: " . $groupDn);
                } else if ($groupName === 'ldapadmins') {
                    $groupDn = $this->adminGroupDn;
                    Log::debug("Usando DN conocido para ldapadmins: " . $groupDn);
                } else {
                    // Buscar el grupo por nombre para otros casos
                    Log::debug("Buscando grupo por nombre: " . $groupName);
                    $group = $this->connection->query()
                        ->in($this->groupsOu)
                        ->where('cn', '=', $groupName)
                        ->first();
                        
                    if ($group) {
                        $groupDn = $group['dn'];
                        Log::debug("Grupo encontrado por nombre: " . $groupName . ", DN: " . $groupDn);
                    } else {
                        Log::error("Grupo no encontrado por nombre: " . $groupName);
                    }
                }
                
                // Si tenemos un DN, intentar añadir el usuario al grupo
                if ($groupDn) {
                    try {
                        $this->addUserToGroup($userDn, $groupDn);
                        Log::debug("Usuario añadido exitosamente al grupo: " . $groupName);
                    } catch (Exception $e) {
                        Log::error("Error al añadir usuario al grupo " . $groupName . ": " . $e->getMessage());
                        throw $e;
                    }
                } else {
                    Log::error("No se pudo encontrar el DN para el grupo: " . $groupName);
                    throw new Exception("Grupo no encontrado: " . $groupName);
                }
            }
            
            // Registrar la acción en logs
            $adminUser = $this->getCurrentUsername();
            Log::info("Usuario LDAP creado: {$request->uid} por {$adminUser}. Grupos: " . json_encode($request->grupos));
            
            // Cambiamos la redirección para usar nombre de ruta en lugar de URL directa
            return redirect()->route('admin.users.index')
                ->with('success', 'Usuario creado correctamente');
                
        } catch (Exception $e) {
            Log::error('Error al crear usuario LDAP: ' . $e->getMessage());
            return back()->withInput()->with('error', 'Error al crear el usuario: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified user.
     */
    public function show($dn)
    {
        try {
            $this->connection->connect();
            
            $user = $this->connection->query()
                ->in($this->baseDn)
                ->where('dn', '=', base64_decode($dn))
                ->first();
                
            if (!$user) {
                return back()->with('error', 'Usuario no encontrado');
            }
            
            // Obtener grupos del usuario
            $groups = $this->getUserGroups(base64_decode($dn));
            
            return view('admin.users.show', [
                'user' => $user,
                'groups' => $groups,
                'isAdmin' => in_array(base64_decode($dn), $this->getAdminUsers())
            ]);
            
        } catch (Exception $e) {
            Log::error('Error al mostrar usuario LDAP: ' . $e->getMessage());
            return back()->with('error', 'Error al cargar el usuario: ' . $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit($dn)
    {
        try {
            $this->connection->connect();
            
            // Añadir logs para depuración
            Log::debug("Intentando editar usuario con DN codificado: " . $dn);
            
            // Decodificar el DN y validar
            $decodedDn = base64_decode($dn);
            if (!$decodedDn) {
                Log::error("Error al decodificar DN: " . $dn);
                return back()->with('error', 'Error: DN inválido');
            }
            
            Log::debug("DN decodificado: " . $decodedDn);
            
            // Obtener uid del usuario
            $uid = '';
            if (preg_match('/uid=([^,]+)/', $decodedDn, $matches)) {
                $uid = $matches[1];
                Log::debug("UID extraído: " . $uid);
            }
            
            // Buscar el usuario directamente por uid
            $user = null;
            if ($uid) {
                $user = $this->connection->query()
                    ->in($this->peopleOu)
                    ->where('uid', '=', $uid)
                    ->first();
                
                if ($user) {
                    Log::debug("Usuario encontrado por UID: " . $uid);
                }
            }
            
            // Si no lo encuentra por uid, intentar por DN
            if (!$user) {
                Log::debug("Usuario no encontrado por UID, buscando por DN");
                // Buscar el usuario de manera más específica
                $query = $this->connection->query();
                
                // Intentar buscar primero en ou=people
                $user = $query->in($this->peopleOu)
                    ->where('dn', '=', $decodedDn)
                    ->first();
                    
                // Si no lo encuentra, intentar en toda la base
                if (!$user) {
                    Log::debug("Usuario no encontrado en ou=people, buscando en toda la base");
                    $user = $query->newInstance()
                        ->in($this->baseDn)
                        ->where('dn', '=', $decodedDn)
                        ->first();
                }
            }
                
            if (!$user) {
                Log::error("Usuario no encontrado para DN: " . $decodedDn);
                return back()->with('error', 'Usuario no encontrado. Por favor, inténtelo de nuevo desde la lista de usuarios.');
            }
            
            Log::debug("Usuario encontrado: " . json_encode(is_array($user) ? $user : $user->toArray()));
            
            // Obtener grupos del usuario - MEJORADO
            $userGroups = [];
            
            try {
                // Buscar todos los grupos
                $allGroups = $this->connection->query()
                    ->in($this->baseDn)
                    ->rawFilter('(|(objectclass=groupOfUniqueNames)(objectclass=posixGroup))')
                    ->get();
                
                Log::debug("Grupos encontrados: " . count($allGroups));
                
                // Para cada grupo, verificar si el usuario es miembro
                foreach ($allGroups as $group) {
                    if (isset($group['uniquemember'])) {
                        $members = is_array($group['uniquemember']) 
                            ? $group['uniquemember'] 
                            : [$group['uniquemember']];
                            
                        if (in_array($decodedDn, $members)) {
                            $userGroups[] = $group;
                            Log::debug("Usuario pertenece al grupo: " . ($group['cn'][0] ?? 'Sin nombre'));
                        }
                    }
                }
                
                // Si no se encontraron grupos para el usuario, registrar aviso
                if (empty($userGroups)) {
                    Log::warning("No se encontraron grupos para el usuario con DN: " . $decodedDn);
                }
            } catch (Exception $e) {
                Log::error('Error al obtener grupos del usuario: ' . $e->getMessage());
            }
            
            // Verificar si allGroups tiene contenido
            if (empty($allGroups)) {
                Log::error("No se encontraron grupos en el sistema");
            } else {
                // Verificar que los grupos tengan los atributos esperados
                $groupDebug = [];
                foreach ($allGroups as $key => $group) {
                    if (is_array($group)) {
                        $groupDebug[$key] = [
                            'es_array' => true,
                            'tiene_cn' => isset($group['cn']),
                            'cn_valor' => isset($group['cn']) ? json_encode($group['cn']) : 'no hay',
                            'keys' => array_keys($group)
                        ];
                    } else {
                        $groupDebug[$key] = [
                            'es_array' => false,
                            'clase' => get_class($group),
                            'tiene_cn' => method_exists($group, 'getFirstAttribute') && $group->getFirstAttribute('cn') !== null,
                            'cn_valor' => method_exists($group, 'getFirstAttribute') ? $group->getFirstAttribute('cn') : 'método no existe'
                        ];
                    }
                }
                Log::debug("Debug de grupos: " . json_encode($groupDebug));
            }
            
            return view('admin.users.edit', [
                'user' => $user,
                'allGroups' => $allGroups,
                'userGroups' => $userGroups,
                'encoded_dn' => $dn // Pasar el DN codificado para el formulario
            ]);
            
        } catch (Exception $e) {
            Log::error('Error al editar usuario LDAP: ' . $e->getMessage());
            Log::error('Traza: ' . $e->getTraceAsString());
            return back()->with('error', 'Error al cargar el formulario: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified user in LDAP.
     */
    public function update(Request $request, $dn)
    {
        $request->validate([
            'nombre' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'email' => 'required|email|max:100',
            'grupos' => 'sometimes|array'
        ]);
        
        try {
            $this->connection->connect();
            
            // Añadir logs para depuración
            Log::debug("Intentando actualizar usuario con DN codificado: " . $dn);
            
            // Decodificar el DN y validar
            $userDn = base64_decode($dn);
            if (!$userDn) {
                Log::error("Error al decodificar DN: " . $dn);
                return back()->with('error', 'Error: DN inválido');
            }
            
            Log::debug("DN decodificado: " . $userDn);
            
            // Extraer uid del DN decodificado
            $uid = '';
            if (preg_match('/uid=([^,]+)/', $userDn, $matches)) {
                $uid = $matches[1];
                Log::debug("UID extraído del DN: " . $uid);
            }
            
            // Buscar el usuario de manera más específica
            $user = null;
            $query = $this->connection->query();
            
            // Primero intentar buscar por UID en ou=people, que es más fiable
            if (!empty($uid)) {
                $user = $query->in($this->peopleOu)
                    ->where('uid', '=', $uid)
                    ->first();
                
                if ($user) {
                    Log::debug("Usuario encontrado por UID: " . $uid);
                    // Si encontramos por UID, actualizar el userDn para usarlo en las operaciones
                    if (is_array($user)) {
                        $userDn = $user['dn'];
                    } else {
                        $userDn = $user->getDn();
                    }
                }
            }
            
            // Si no se encontró por UID, intentar por DN directamente
            if (!$user) {
                // Intentar buscar primero en ou=people
                $user = $query->in($this->peopleOu)
                    ->where('dn', '=', $userDn)
                    ->first();
                    
                // Si no lo encuentra, intentar en toda la base
                if (!$user) {
                    Log::debug("Usuario no encontrado en ou=people, buscando en toda la base");
                    $user = $query->newInstance()
                        ->in($this->baseDn)
                        ->where('dn', '=', $userDn)
                        ->first();
                }
            }
                
            if (!$user) {
                Log::error("Usuario no encontrado para actualizar. UID: " . $uid . ", DN: " . $userDn);
                return back()->with('error', 'Usuario no encontrado. Por favor, inténtelo de nuevo desde la lista de usuarios.');
            }
            
            // Obtener el uid del usuario para los logs
            if (empty($uid)) {
                if (is_array($user)) {
                    $uid = $user['uid'][0] ?? '';
                } else {
                    $uid = $user->getFirstAttribute('uid');
                }
            }
            
            // Actualizar datos básicos
            $updateData = [
                'cn' => $request->nombre . ' ' . $request->apellidos,
                'sn' => $request->apellidos,
                'givenname' => $request->nombre,
                'mail' => $request->email
            ];
            
            // Si hay contraseña, actualizarla
            if (!empty($request->password)) {
                $updateData['userpassword'] = $this->hashPassword($request->password);
            }
            
            // Modificar atributos
            $this->connection->run(function ($ldap) use ($userDn, $updateData) {
                $batchMods = [];
                foreach ($updateData as $attribute => $value) {
                    $batchMods[] = [
                        'attrib' => $attribute,
                        'modtype' => LDAP_MODIFY_BATCH_REPLACE,
                        'values' => [$value]
                    ];
                }
                $ldap->modifyBatch($userDn, $batchMods);
            });
            
            // Actualizar grupos del usuario
            if ($request->has('grupos')) {
                $this->updateUserGroups($userDn, $request->grupos);
            }
            
            // Registrar la acción en logs
            $adminUser = $this->getCurrentUsername();
            Log::info("Usuario LDAP actualizado: {$uid} por {$adminUser}. Grupos: " . json_encode($request->grupos ?? []));
            
            // Cambiamos la redirección para usar nombre de ruta
            return redirect()->route('admin.users.index')
                ->with('success', 'Usuario actualizado correctamente');
                
        } catch (Exception $e) {
            Log::error('Error al actualizar usuario LDAP: ' . $e->getMessage());
            Log::error('Traza: ' . $e->getTraceAsString());
            return back()->with('error', 'Error al actualizar el usuario: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified user from LDAP.
     */
    public function destroy($dn)
    {
        try {
            $this->connection->connect();
            
            // Añadir logs para depuración
            Log::debug("Intentando eliminar usuario con DN codificado: " . $dn);
            
            // Decodificar el DN y validar
            $userDn = base64_decode($dn);
            if (!$userDn) {
                Log::error("Error al decodificar DN: " . $dn);
                return back()->with('error', 'Error: DN inválido');
            }
            
            Log::debug("DN decodificado: " . $userDn);
            
            // Buscar el usuario de manera más específica
            $query = $this->connection->query();
            
            // Intentar buscar primero en ou=people
            $user = $query->in($this->peopleOu)
                ->where('dn', '=', $userDn)
                ->first();
                
            // Si no lo encuentra, intentar en toda la base
            if (!$user) {
                Log::debug("Usuario no encontrado en ou=people, buscando en toda la base");
                $user = $query->newInstance()
                    ->in($this->baseDn)
                    ->where('dn', '=', $userDn)
                    ->first();
            }
                
            if (!$user) {
                Log::error("Usuario no encontrado para eliminar con DN: " . $userDn);
                return back()->with('error', 'Usuario no encontrado');
            }
            
            // Obtener el uid del usuario para los logs
            $uid = '';
            if (is_array($user)) {
                $uid = $user['uid'][0] ?? '';
            } else {
                $uid = $user->getFirstAttribute('uid');
            }
            
            // Primero eliminar el usuario de todos los grupos
            $groups = $this->connection->query()
                ->in($this->groupsOu)
                ->where('objectclass', '=', 'groupOfUniqueNames')
                ->get();
            
            foreach ($groups as $group) {
                $this->removeUserFromGroup($userDn, $group['dn']);
            }
            
            // Luego eliminar el usuario
            $this->connection->run(function ($ldap) use ($userDn) {
                $ldap->delete($userDn);
            });
            
            // Registrar la acción en logs
            $adminUser = $this->getCurrentUsername();
            Log::info("Usuario LDAP eliminado: {$uid} por {$adminUser}");
            
            // Cambiamos la redirección para usar nombre de ruta
            return redirect()->route('admin.users.index')
                ->with('success', 'Usuario eliminado correctamente');
                
        } catch (Exception $e) {
            Log::error('Error al eliminar usuario LDAP: ' . $e->getMessage());
            Log::error('Traza: ' . $e->getTraceAsString());
            return back()->with('error', 'Error al eliminar el usuario: ' . $e->getMessage());
        }
    }

    /**
     * Export users to Excel
     */
    public function exportExcel()
    {
        // Se implementará más adelante si es necesario
        return back()->with('info', 'Función en desarrollo');
    }

    /**
     * Toggle admin status for a user
     */
    public function toggleAdmin($dn)
    {
        try {
            $this->connection->connect();
            
            // Decodificar el DN y validar
            $userDn = base64_decode($dn);
            if (!$userDn) {
                Log::error("Error al decodificar DN: " . $dn);
                return back()->with('error', 'Error: DN inválido');
            }
            
            Log::debug("DN decodificado: " . $userDn);
            
            // Extraer UID del DN para búsquedas alternativas
            $uid = '';
            if (preg_match('/uid=([^,]+)/', $userDn, $matches)) {
                $uid = $matches[1];
            }
            
            // Si no tenemos UID, no podemos continuar
            if (empty($uid)) {
                return back()->with('error', 'Error: No se pudo obtener el UID del usuario');
            }
            
            // Buscar el usuario por su UID para confirmar que existe
            $user = $this->connection->query()
                ->in($this->peopleOu)
                ->where('uid', '=', $uid)
                ->first();
                
            if (!$user) {
                // Intentar buscar en toda la base
                $user = $this->connection->query()
                    ->in($this->baseDn)
                    ->where('uid', '=', $uid)
                    ->first();
            }
            
            if (!$user) {
                return back()->with('error', 'Usuario no encontrado');
            }
            $adminGroup = $this->connection->query()
                ->in($this->baseDn)
                ->where('cn', '=', 'ldapadmins')
                ->first();
                
            if (!$adminGroup) {
                return back()->with('error', 'Grupo de administradores no encontrado');
            }
                        
            // Verificar si el usuario ya es miembro del grupo admin
            $isAdmin = false;
            if (isset($adminGroup['memberuid'])) {
                $memberUids = is_array($adminGroup['memberuid']) 
                    ? $adminGroup['memberuid'] 
                    : [$adminGroup['memberuid']];
                
                $isAdmin = in_array($uid, $memberUids);
            }
            
            // Usar la conexión LDAP directamente
            $ldapConnection = ldap_connect(config('ldap.connections.default.hosts')[0], config('ldap.connections.default.port'));
            
            if (!$ldapConnection) {
                return back()->with('error', 'Error al conectar con el servidor LDAP');
            }
            
            // Configurar opciones de LDAP
            ldap_set_option($ldapConnection, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldapConnection, LDAP_OPT_REFERRALS, 0);
            
            // Autenticar con el servidor LDAP
            $ldapBind = ldap_bind(
                $ldapConnection, 
                config('ldap.connections.default.username'), 
                config('ldap.connections.default.password')
            );
            
            if (!$ldapBind) {
                return back()->with('error', 'Error al autenticar con el servidor LDAP');
            }
            
            $groupDn = $adminGroup['dn'];
            
            // Cambiar estado de admin
            if ($isAdmin) {
                // Eliminar el usuario del grupo
                $result = ldap_read($ldapConnection, $groupDn, "(objectclass=*)", ["memberUid"]);
                
                if (!$result) {
                    return back()->with('error', 'Error al leer grupo admin');
                }
                
                $entries = ldap_get_entries($ldapConnection, $result);
                $memberUids = [];
                
                if (isset($entries[0]['memberuid'])) {
                    for ($i = 0; $i < $entries[0]['memberuid']['count']; $i++) {
                        if ($entries[0]['memberuid'][$i] != $uid) {
                            $memberUids[] = $entries[0]['memberuid'][$i];
                        }
                    }
                }
                
                // Actualizar el grupo sin el usuario
                $entry = ["memberUid" => $memberUids];
                $result = ldap_modify($ldapConnection, $groupDn, $entry);
                
                if (!$result) {
                    return back()->with('error', 'Error al eliminar usuario del grupo admin');
                }
                
                $message = 'Usuario removido del grupo de administradores';
            } else {
                // Añadir el usuario al grupo
                $result = ldap_read($ldapConnection, $groupDn, "(objectclass=*)", ["memberUid"]);
                
                if (!$result) {
                    return back()->with('error', 'Error al leer grupo admin');
                }
                
                $entries = ldap_get_entries($ldapConnection, $result);
                $memberUids = [];
                
                if (isset($entries[0]['memberuid'])) {
                    for ($i = 0; $i < $entries[0]['memberuid']['count']; $i++) {
                        $memberUids[] = $entries[0]['memberuid'][$i];
                    }
                }
                
                // Añadir el usuario solo si no existe
                if (!in_array($uid, $memberUids)) {
                    $memberUids[] = $uid;
                }
                
                // Actualizar el grupo con el nuevo usuario
                $entry = ["memberUid" => $memberUids];
                $result = ldap_modify($ldapConnection, $groupDn, $entry);
                
                if (!$result) {
                    return back()->with('error', 'Error al añadir usuario al grupo admin');
                }
                
                $message = 'Usuario añadido al grupo de administradores';
            }
            
            ldap_close($ldapConnection);
            
            return redirect()->route('admin.users.index')
                ->with('success', $message);
                
        } catch (Exception $e) {
            return back()->with('error', 'Error al cambiar permisos: ' . $e->getMessage());
        }
    }

    /**
     * Reset user password
     */
    public function resetPassword(Request $request, $dn)
    {
        $request->validate([
            'password' => 'required|string|min:6|confirmed'
        ]);
        
        try {
            $this->connection->connect();
            
            $userDn = base64_decode($dn);
            
            // Obtener el usuario para los logs
            $user = $this->connection->query()
                ->in($this->baseDn)
                ->where('dn', '=', $userDn)
                ->first();
                
            if (!$user) {
                return back()->with('error', 'Usuario no encontrado');
            }
            
            // Obtener el uid del usuario para los logs
            $uid = '';
            if (is_array($user)) {
                $uid = $user['uid'][0] ?? '';
            } else {
                $uid = $user->getFirstAttribute('uid');
            }
            
            // Actualizar contraseña
            $this->connection->run(function ($ldap) use ($userDn, $request) {
                $ldap->modifyBatch($userDn, [
                    [
                        'attrib' => 'userpassword',
                        'modtype' => LDAP_MODIFY_BATCH_REPLACE,
                        'values' => [$this->hashPassword($request->password)],
                    ],
                ]);
            });
            
            // Registrar la acción en logs
            $adminUser = $this->getCurrentUsername();
            
            return back()->with('success', 'Contraseña actualizada correctamente');
            
        } catch (Exception $e) {
            return back()->with('error', 'Error al actualizar contraseña: ' . $e->getMessage());
        }
    }

    /**
     * Añadir usuario a un grupo
     */
    protected function addUserToGroup($userDn, $groupDn)
    {
        try {
            Log::debug("Intentando añadir usuario {$userDn} al grupo {$groupDn}");
            
            // Extraer uid del userDn
            $userUid = '';
            if (preg_match('/uid=([^,]+)/', $userDn, $matches)) {
                $userUid = $matches[1];
            } else {
                throw new Exception("No se pudo extraer el UID del DN del usuario");
            }
            
            // Extraer nombre del grupo del DN para logs
            $groupName = '';
            if (preg_match('/cn=([^,]+)/', $groupDn, $matches)) {
                $groupName = $matches[1];
            }
            
            Log::debug("Buscando grupo: {$groupDn}");
            
            // Buscar el grupo utilizando múltiples métodos
            $group = $this->findGroupByMultipleMethods($groupDn, $groupName);
            
            if (!$group) {
                throw new Exception("Grupo no encontrado: $groupDn");
            }
            
            // Verificar las clases de objeto del grupo
            $objectClasses = isset($group['objectclass']) ? $group['objectclass'] : [];
            $isPosixGroup = false;
            $isGroupOfUniqueNames = false;
            $isGroupOfNames = false;
            
            foreach ($objectClasses as $class) {
                $classLower = strtolower($class);
                if ($classLower === 'posixgroup') {
                    $isPosixGroup = true;
                } else if ($classLower === 'groupofuniquenames') {
                    $isGroupOfUniqueNames = true;
                } else if ($classLower === 'groupofnames') {
                    $isGroupOfNames = true;
                }
            }
            
            Log::debug("Clases de objeto del grupo {$groupName}: " . json_encode($objectClasses));
            
            // Procesar según el tipo de grupo
            if ($isPosixGroup) {
                $this->addUserToPosixGroup($userUid, $groupDn, $groupName, $group);
            }
            
            if ($isGroupOfUniqueNames) {
                $this->addUserToGroupOfUniqueNames($userDn, $groupDn, $groupName, $group);
            }
            
            if ($isGroupOfNames) {
                $this->addUserToGroupOfNames($userDn, $groupDn, $groupName, $group);
            }
            
            Log::info("Usuario añadido con éxito al grupo {$groupName}");
            return true;
        } catch (Exception $e) {
            Log::error("Error al añadir usuario al grupo: " . $e->getMessage());
            throw new Exception("Error al añadir usuario al grupo: " . $e->getMessage());
        }
    }
    
    /**
     * Buscar grupo por múltiples métodos
     */
    protected function findGroupByMultipleMethods($groupDn, $groupName)
    {
        $group = null;
        $errorMsg = '';
        
        // Método 1: Búsqueda exacta por DN
        try {
            $group = $this->connection->query()
                ->in($this->baseDn)
                ->where('dn', '=', $groupDn)
                ->first();
                
            if ($group) {
                Log::debug("Grupo encontrado por DN exacto: {$groupDn}");
                return $group;
            }
        } catch (\Exception $e) {
            $errorMsg .= "Error búsqueda 1: " . $e->getMessage() . "; ";
            Log::debug("Error en búsqueda 1: " . $e->getMessage());
        }
        
        // Método 2: Búsqueda por componentes
        try {
            // Extraer cn y ou del DN
            $cn = '';
            $ou = '';
            if (preg_match('/cn=([^,]+),ou=([^,]+)/', $groupDn, $matches)) {
                $cn = $matches[1];
                $ou = $matches[2];
                
                // Buscar por CN
                $group = $this->connection->query()
                    ->in("ou={$ou},{$this->baseDn}")
                    ->where('cn', '=', $cn)
                    ->first();
                    
                if ($group) {
                    Log::debug("Grupo encontrado por CN en OU: {$cn} en ou={$ou}");
                    return $group;
                }
            }
        } catch (\Exception $e) {
            $errorMsg .= "Error búsqueda 2: " . $e->getMessage() . "; ";
            Log::debug("Error en búsqueda 2: " . $e->getMessage());
        }
        
        // Método 3: Búsqueda directa por filtro rawFilter
        try {
            // Construir un filtro LDAP directo para el grupo
            $escapedDn = ldap_escape($groupDn, "", LDAP_ESCAPE_FILTER);
            $results = $this->connection->query()
                ->in($this->baseDn)
                ->rawFilter("(|(dn={$escapedDn})(distinguishedName={$escapedDn}))")
                ->get();
                
            if (count($results) > 0) {
                $group = $results[0];
                Log::debug("Grupo encontrado por filtro directo: {$groupDn}");
                return $group;
            }
        } catch (\Exception $e) {
            $errorMsg .= "Error búsqueda 3: " . $e->getMessage() . "; ";
            Log::debug("Error en búsqueda 3: " . $e->getMessage());
        }
        
        // Intento final: buscar por nombre en el contenedor de grupos
        try {
            Log::debug("Buscando grupo por cn={$groupName} en {$this->groupsOu}");
            $group = $this->connection->query()
                ->in($this->groupsOu)
                ->where('cn', '=', $groupName)
                ->first();
                
            if ($group) {
                Log::debug("Grupo encontrado por CN en contenedor de grupos: {$groupName}");
                return $group;
            }
        } catch (\Exception $e) {
            $errorMsg .= "Error búsqueda final: " . $e->getMessage();
            Log::debug("Error en búsqueda final: " . $e->getMessage());
        }
        
        // Último intento: comprobar usando conexión nativa
        try {
            $ldapConn = ldap_connect(config('ldap.connections.default.hosts')[0], config('ldap.connections.default.port'));
            ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);
            ldap_set_option($ldapConn, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
            
            $bind = ldap_bind(
                $ldapConn, 
                config('ldap.connections.default.username'), 
                config('ldap.connections.default.password')
            );
            
            if ($bind) {
                $search = ldap_search($ldapConn, $this->baseDn, "(cn={$groupName})");
                $entries = ldap_get_entries($ldapConn, $search);
                
                if ($entries['count'] > 0) {
                    Log::debug("Grupo encontrado mediante conexión nativa de PHP: {$groupName}");
                    ldap_close($ldapConn);
                    return $entries[0];
                }
                ldap_close($ldapConn);
            }
        } catch (\Exception $e) {
            Log::debug("Error en búsqueda nativa: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Añadir usuario a un grupo posixGroup
     */
    protected function addUserToPosixGroup($userUid, $groupDn, $groupName, $group)
    {
        // Obtener miembros actuales
        $memberUids = [];
        if (isset($group['memberuid'])) {
            $memberUids = is_array($group['memberuid']) ? $group['memberuid'] : [$group['memberuid']];
        }
        
        // Asegurarse de que es un array indexado numéricamente
        $memberUids = array_values($memberUids);
        
        // Si el usuario ya está en el grupo, no hacer nada
        if (in_array($userUid, $memberUids)) {
            Log::debug("Usuario {$userUid} ya es miembro del grupo {$groupName} (posixGroup)");
            return true;
        }
        
        // Añadir el usuario a los miembros
        $memberUids[] = $userUid;
        
        // Asegurarse nuevamente de que es un array indexado numéricamente
        $memberUids = array_values($memberUids);
        
        Log::debug("Añadiendo {$userUid} a memberUid de {$groupName}. Array final: " . json_encode($memberUids));
        
        // Realizar la modificación
        return $this->modifyGroupAttribute($groupDn, $groupName, 'memberuid', $memberUids, $userUid);
    }
    
    /**
     * Añadir usuario a un grupo groupOfUniqueNames
     */
    protected function addUserToGroupOfUniqueNames($userDn, $groupDn, $groupName, $group)
    {
        // Obtener miembros actuales
        $members = [];
        if (isset($group['uniquemember'])) {
            $members = is_array($group['uniquemember']) ? $group['uniquemember'] : [$group['uniquemember']];
        }
        
        // Asegurarse de que es un array indexado numéricamente
        $members = array_values($members);
        
        // Si el usuario ya está en el grupo, no hacer nada
        if (in_array($userDn, $members)) {
            Log::debug("Usuario {$userDn} ya es miembro del grupo {$groupName} (groupOfUniqueNames)");
            return true;
        }
        
        // Añadir el usuario a los miembros
        $members[] = $userDn;
        
        // Asegurarse nuevamente de que es un array indexado numéricamente
        $members = array_values($members);
        
        Log::debug("Añadiendo {$userDn} a uniqueMember de {$groupName}. Array final: " . json_encode($members));
        
        // Realizar la modificación
        return $this->modifyGroupAttribute($groupDn, $groupName, 'uniquemember', $members, $userDn);
    }
    
    /**
     * Añadir usuario a un grupo groupOfNames
     */
    protected function addUserToGroupOfNames($userDn, $groupDn, $groupName, $group)
    {
        // Obtener miembros actuales
        $members = [];
        if (isset($group['member'])) {
            $members = is_array($group['member']) ? $group['member'] : [$group['member']];
        }
        
        // Asegurarse de que es un array indexado numéricamente
        $members = array_values($members);
        
        // Si el usuario ya está en el grupo, no hacer nada
        if (in_array($userDn, $members)) {
            Log::debug("Usuario {$userDn} ya es miembro del grupo {$groupName} (groupOfNames)");
            return true;
        }
        
        // Añadir el usuario a los miembros
        $members[] = $userDn;
        
        // Asegurarse nuevamente de que es un array indexado numéricamente
        $members = array_values($members);
        
        Log::debug("Añadiendo {$userDn} a member de {$groupName}. Array final: " . json_encode($members));
        
        // Realizar la modificación
        return $this->modifyGroupAttribute($groupDn, $groupName, 'member', $members, $userDn);
    }
    
    /**
     * Modificar atributo de grupo con manejo de errores
     */
    protected function modifyGroupAttribute($groupDn, $groupName, $attribute, $values, $userId)
    {
        try {
            $this->connection->run(function ($ldap) use ($groupDn, $attribute, $values) {
                $ldap->modifyBatch($groupDn, [
                    [
                        'attrib' => $attribute,
                        'modtype' => LDAP_MODIFY_BATCH_REPLACE,
                        'values' => $values,
                    ],
                ]);
            });
            
            Log::info("Usuario {$userId} añadido al grupo {$groupName} (" . ucfirst($attribute) . ")");
            return true;
        } catch (\Exception $e) {
            Log::error("Error en la modificación LDAP: " . $e->getMessage());
            
            // Intento alternativo con conexión LDAP nativa
            return $this->modifyGroupAttributeNative($groupDn, $groupName, $attribute, $values, $userId);
        }
    }
    
    /**
     * Modificar atributo de grupo usando LDAP nativo
     */
    protected function modifyGroupAttributeNative($groupDn, $groupName, $attribute, $values, $userId)
    {
        try {
            $ldapConn = ldap_connect(config('ldap.connections.default.hosts')[0], config('ldap.connections.default.port'));
            ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);
            ldap_set_option($ldapConn, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
            
            $bind = ldap_bind(
                $ldapConn, 
                config('ldap.connections.default.username'), 
                config('ldap.connections.default.password')
            );
            
            if ($bind) {
                // Usar mod_replace en lugar de mod_add para reemplazar todos los valores
                $entry = [$attribute => $values];
                $result = ldap_modify($ldapConn, $groupDn, $entry);
                
                if ($result) {
                    Log::info("Usuario {$userId} añadido al grupo {$groupName} mediante LDAP nativo");
                    ldap_close($ldapConn);
                    return true;
                } else {
                    Log::error("Error en LDAP nativo: " . ldap_error($ldapConn));
                    ldap_close($ldapConn);
                    throw new Exception("Error en LDAP nativo: " . ldap_error($ldapConn));
                }
            }
            ldap_close($ldapConn);
            return false;
        } catch (\Exception $nativeEx) {
            Log::error("Error en LDAP nativo: " . $nativeEx->getMessage());
            throw $nativeEx;
        }
    }

    /**
     * Eliminar usuario de un grupo
     */
    protected function removeUserFromGroup($userDn, $groupDn)
    {
        try {
            // Extraer uid del userDn para posixGroup
            $userUid = '';
            if (preg_match('/uid=([^,]+)/', $userDn, $matches)) {
                $userUid = $matches[1];
            }
            
            // Verificar primero si el grupo existe
            try {
                $group = $this->connection->query()
                    ->in($this->baseDn)
                    ->where('dn', '=', $groupDn)
                    ->first();
                    
                if (!$group) {
                    Log::warning("Grupo no encontrado: $groupDn - No se puede eliminar usuario");
                    return false; // No lanzar excepción, simplemente retornar falso
                }
            } catch (Exception $ex) {
                Log::warning("Error al buscar el grupo $groupDn: " . $ex->getMessage());
                return false; // El grupo no existe o no se puede acceder, continuamos
            }
            
            // Verificar las clases de objeto del grupo
            $objectClasses = isset($group['objectclass']) ? $group['objectclass'] : [];
            $isPosixGroup = false;
            $isGroupOfUniqueNames = false;
            
            foreach ($objectClasses as $class) {
                $classLower = strtolower($class);
                if ($classLower === 'posixgroup') {
                    $isPosixGroup = true;
                } else if ($classLower === 'groupofuniquenames') {
                    $isGroupOfUniqueNames = true;
                }
            }
            
            // Caso 1: Para PosixGroup, eliminar el uid de memberUid
            if ($isPosixGroup && !empty($userUid) && isset($group['memberuid'])) {
                $memberUids = is_array($group['memberuid']) 
                    ? $group['memberuid'] 
                    : [$group['memberuid']];
                    
                // Eliminar el usuario del array de miembros
                $key = array_search($userUid, $memberUids);
                if ($key !== false) {
                    unset($memberUids[$key]);
                    $memberUids = array_values($memberUids);
                    
                    $this->connection->run(function ($ldap) use ($groupDn, $memberUids) {
                        $ldap->modifyBatch($groupDn, [
                            [
                                'attrib' => 'memberuid',
                                'modtype' => LDAP_MODIFY_BATCH_REPLACE,
                                'values' => $memberUids,
                            ],
                        ]);
                    });
                }
            }
            
            // Caso 2: Para groupOfUniqueNames, eliminar el DN completo de uniqueMember
            if ($isGroupOfUniqueNames && isset($group['uniquemember'])) {
                $members = is_array($group['uniquemember']) 
                    ? $group['uniquemember'] 
                    : [$group['uniquemember']];
                    
                // Eliminar el usuario del array de miembros
                $key = array_search($userDn, $members);
                if ($key !== false) {
                    unset($members[$key]);
                    $members = array_values($members);
                    
                    $this->connection->run(function ($ldap) use ($groupDn, $members) {
                        $ldap->modifyBatch($groupDn, [
                            [
                                'attrib' => 'uniquemember',
                                'modtype' => LDAP_MODIFY_BATCH_REPLACE,
                                'values' => $members,
                            ],
                        ]);
                    });
                }
            }
            
            return true;
        } catch (Exception $e) {
            Log::error("Error al eliminar usuario del grupo: " . $e->getMessage());
            throw new Exception("Error al eliminar usuario del grupo: " . $e->getMessage());
        }
    }

    /**
     * Actualizar grupos de un usuario
     */
    protected function updateUserGroups($userDn, $selectedGroups)
    {
        try {
            Log::debug("Actualizando grupos para usuario: $userDn");
            Log::debug("Grupos seleccionados: " . json_encode($selectedGroups));
            
            // Lista de grupos que necesitan manejo especial
            $gruposEspeciales = ['everybody']; // Solo ignoraremos 'everybody'
            
            // Filtrar solamente los grupos que no podemos manejar
            $filteredSelectedGroups = array_filter($selectedGroups, function($group) use ($gruposEspeciales) {
                return !in_array($group, $gruposEspeciales);
            });
            
            // Informar sobre los grupos filtrados
            if (count($selectedGroups) !== count($filteredSelectedGroups)) {
                $gruposIgnorados = array_diff($selectedGroups, $filteredSelectedGroups);
                Log::info("Se ignoraron los siguientes grupos que no pueden ser manejados: " . implode(', ', $gruposIgnorados));
            }
            
            // Obtener todos los grupos posixGroup
            $allGroups = [];
            try {
                $allGroups = $this->connection->query()
                    ->in($this->groupsOu)
                    ->where('objectclass', '=', 'posixGroup')
                    ->get();
                
                Log::debug("Grupos posixGroup encontrados: " . count($allGroups));
            } catch (\Exception $e) {
                Log::warning("Error al buscar grupos posixGroup: " . $e->getMessage());
            }
            
            // Agregar también los grupos conocidos
            $knownGroups = [];
            
            // Manejar grupos conocidos - crearlos si no existen
            $gruposConocidos = [
                'ldapadmins' => $this->adminGroupDn,
                'profesores' => $this->profesoresGroupDn,
                'alumnos' => $this->alumnosGroupDn
            ];
            
            foreach ($gruposConocidos as $groupName => $groupDn) {
                if (in_array($groupName, $filteredSelectedGroups)) {
                    try {
                        $groupExists = $this->connection->query()->find($groupDn);
                        if ($groupExists) {
                            $knownGroups[] = ['dn' => $groupDn, 'cn' => [$groupName]];
                            Log::debug("Grupo $groupName encontrado y añadido");
                        } else {
                            // Crear el grupo si no existe
                            Log::info("Grupo $groupName no encontrado, intentando crearlo");
                            $this->createLdapGroup($groupName, $groupDn);
                            $knownGroups[] = ['dn' => $groupDn, 'cn' => [$groupName]];
                        }
                    } catch (\Exception $e) {
                        Log::warning("Error al verificar grupo $groupName: " . $e->getMessage());
                        if (strpos($e->getMessage(), 'No such object') !== false) {
                            // Crear el grupo si no existe
                            Log::info("Grupo $groupName no encontrado, intentando crearlo");
                            $this->createLdapGroup($groupName, $groupDn);
                            $knownGroups[] = ['dn' => $groupDn, 'cn' => [$groupName]];
                        }
                    }
                }
            }
            
            // Combinar grupos
            $allGroups = array_merge($allGroups, $knownGroups);
            Log::debug("Total de grupos a procesar: " . count($allGroups));
            
            if (empty($allGroups)) {
                Log::warning("No se encontraron grupos válidos para procesar");
                return true; // Retornar éxito aunque no haya grupos para evitar error
            }
            
            $errors = [];
            
            foreach ($allGroups as $group) {
                // Verificar que el grupo tenga atributos necesarios
                if (!isset($group['dn']) || !isset($group['cn']) || !is_array($group['cn']) || empty($group['cn'])) {
                    Log::warning("Grupo sin atributos válidos, omitiendo: " . json_encode($group));
                    continue;
                }
                
                $groupDn = $group['dn'];
                $groupName = $group['cn'][0];
                
                Log::debug("Procesando grupo: $groupName");
                
                // Saltar los grupos problemáticos
                if (in_array($groupName, $gruposEspeciales)) {
                    Log::debug("Omitiendo grupo especial: $groupName");
                    continue;
                }
                
                try {
                    // Verificar que el grupo existe antes de modificarlo
                    try {
                        $groupExists = $this->connection->query()->find($groupDn);
                        if (!$groupExists) {
                            Log::warning("Grupo $groupName con DN $groupDn no existe, intentando crearlo");
                            $this->createLdapGroup($groupName, $groupDn);
                        }
                    } catch (\Exception $e) {
                        Log::warning("Error al verificar grupo $groupName: " . $e->getMessage());
                        // Intentar crear el grupo si no existe
                        if (strpos($e->getMessage(), 'No such object') !== false) {
                            $this->createLdapGroup($groupName, $groupDn);
                        } else {
                            continue;
                        }
                    }
                    
                    // Si está seleccionado y no está en el grupo, añadirlo
                    if (in_array($groupName, $filteredSelectedGroups)) {
                        try {
                            Log::debug("Añadiendo usuario a grupo: $groupName");
                            $this->addUserToGroup($userDn, $groupDn);
                        } catch (\Exception $e) {
                            Log::warning("Error al añadir usuario a grupo $groupName: " . $e->getMessage());
                            $errors[] = "Error al añadir usuario al grupo $groupName: " . $e->getMessage();
                        }
                    } 
                    // Si no está seleccionado y está en el grupo, eliminarlo
                    else {
                        try {
                            Log::debug("Eliminando usuario de grupo: $groupName");
                            $this->removeUserFromGroup($userDn, $groupDn);
                        } catch (\Exception $e) {
                            Log::warning("Error al eliminar usuario de grupo $groupName: " . $e->getMessage());
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Error al procesar grupo $groupName: " . $e->getMessage());
                    $errors[] = "Error con grupo $groupName: " . $e->getMessage();
                }
            }
            
            // Filtrar errores sobre grupos especiales
            $filteredErrors = [];
            foreach ($errors as $error) {
                $isSpecialGroupError = false;
                foreach ($gruposEspeciales as $specialGroup) {
                    if (strpos($error, $specialGroup) !== false) {
                        $isSpecialGroupError = true;
                        break;
                    }
                }
                
                if (!$isSpecialGroupError) {
                    $filteredErrors[] = $error;
                }
            }
            
            if (!empty($filteredErrors)) {
                $errorMsg = implode("; ", $filteredErrors);
                throw new Exception("Algunos grupos no pudieron actualizarse: " . $errorMsg);
            }
            
            return true;
            
        } catch (Exception $e) {
            Log::error("Error al actualizar grupos del usuario: " . $e->getMessage());
            throw new Exception("Error al actualizar grupos del usuario: " . $e->getMessage());
        }
    }
    
    /**
     * Crear un grupo LDAP si no existe
     */
    protected function createLdapGroup($groupName, $groupDn)
    {
        try {
            // Verificar si el grupo ya existe
            try {
                $exists = $this->connection->query()->find($groupDn);
                if ($exists) {
                    Log::debug("Grupo $groupName ya existe, no es necesario crearlo");
                    return true;
                }
            } catch (\Exception $e) {
                // Continuar con la creación si hay error
                Log::debug("Excepción al verificar grupo: " . $e->getMessage());
            }
            
            // Extraer el padre del grupo para verificar si existe
            $parentDn = preg_replace('/^cn=[^,]+,/', '', $groupDn);
            try {
                $parentExists = $this->connection->query()->find($parentDn);
                if (!$parentExists) {
                    Log::warning("El contenedor padre $parentDn no existe, no se puede crear el grupo");
                    return false;
                }
            } catch (\Exception $e) {
                Log::warning("Error al verificar contenedor padre $parentDn: " . $e->getMessage());
                return false;
            }
            
            Log::info("Creando grupo LDAP: $groupName con DN: $groupDn");
            
            // Obtener siguiente GID disponible
            $gid = $this->getNextGidNumber();
            
            // Definir atributos para el grupo
            $attributes = [
                'objectclass' => ['top', 'posixGroup'],
                'cn' => $groupName,
                'gidNumber' => $gid,
                'memberUid' => [$userUid]
            ];
            
            // Crear el grupo
            $this->connection->run(function ($ldap) use ($groupDn, $attributes) {
                $ldap->add($groupDn, $attributes);
            });
            
            Log::info("Grupo LDAP creado exitosamente: $groupName");
            return true;
            
        } catch (\Exception $e) {
            Log::error("Error al crear grupo LDAP $groupName: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener siguiente GID disponible
     */
    protected function getNextGidNumber()
    {
        try {
            $groups = $this->connection->query()
                ->in($this->groupsOu)
                ->where('objectclass', '=', 'posixGroup')
                ->get();
                
            $maxGid = 500; // Valor inicial
            
            foreach ($groups as $group) {
                if (isset($group['gidnumber'])) {
                    $gid = (int) $group['gidnumber'][0];
                    if ($gid > $maxGid) {
                        $maxGid = $gid;
                    }
                }
            }
            
            return $maxGid + 1;
            
        } catch (\Exception $e) {
            Log::error('Error al obtener siguiente GID: ' . $e->getMessage());
            return 501; // Valor por defecto en caso de error
        }
    }

    /**
     * Obtener los grupos a los que pertenece un usuario
     */
    protected function getUserGroups($userDn)
    {
        $userGroups = [];
        
        try {
            // Extraer uid del userDn para búsquedas adicionales
            $userUid = '';
            if (preg_match('/uid=([^,]+)/', $userDn, $matches)) {
                $userUid = $matches[1];
            }
            
            // Obtener todos los grupos disponibles
            $allGroups = $this->connection->query()
                ->in($this->baseDn)
                ->rawFilter('(|(objectclass=groupOfUniqueNames)(objectclass=posixGroup))')
                ->get();
            
            foreach ($allGroups as $group) {
                $isMember = false;
                
                // Verificar si el grupo tiene miembros y mostrarlos (para groupOfUniqueNames)
                if (isset($group['uniquemember'])) {
                    $members = is_array($group['uniquemember']) 
                        ? $group['uniquemember'] 
                        : [$group['uniquemember']];
                    
                    // Validar si el usuario está en este grupo
                    if (in_array($userDn, $members)) {
                        $isMember = true;
                    }
                }
                
                // También verificar memberUid para posixGroup
                if (isset($group['memberuid']) && !empty($userUid)) {
                    $memberUids = is_array($group['memberuid']) 
                        ? $group['memberuid'] 
                        : [$group['memberuid']];
                    
                    if (in_array($userUid, $memberUids)) {
                        $isMember = true;
                    }
                }
                
                // Si el usuario es miembro por cualquier método, añadir el grupo
                if ($isMember) {
                    $userGroups[] = $group;
                }
            }
            
        } catch (Exception $e) {
            Log::error('Error al obtener grupos del usuario: ' . $e->getMessage());
        }
        
        return $userGroups;
    }

    /**
     * Obtener usuarios administradores
     */
    protected function getAdminUsers()
    {
        $adminUsers = [];
        
        try {
            $adminGroup = $this->connection->query()
                ->in($this->adminGroupDn)
                ->first();
                
            if ($adminGroup && isset($adminGroup['uniquemember'])) {
                $adminUsers = is_array($adminGroup['uniquemember']) 
                    ? $adminGroup['uniquemember'] 
                    : [$adminGroup['uniquemember']];
            }
            
        } catch (Exception $e) {
            Log::error('Error al obtener usuarios admin: ' . $e->getMessage());
        }
        
        return $adminUsers;
    }

    /**
     * Obtener siguiente UID disponible
     */
    protected function getNextUidNumber()
    {
        try {
            $users = $this->connection->query()
                ->in($this->peopleOu)
                ->where('objectclass', '=', 'posixAccount')
                ->get();
                
            $maxUid = 10000; // Valor inicial
            
            foreach ($users as $user) {
                if (isset($user['uidnumber'])) {
                    $uid = (int) $user['uidnumber'][0];
                    if ($uid > $maxUid) {
                        $maxUid = $uid;
                    }
                }
            }
            
            return $maxUid + 1;
            
        } catch (Exception $e) {
            Log::error('Error al obtener siguiente UID: ' . $e->getMessage());
            return 10001; // Valor por defecto en caso de error
        }
    }

    /**
     * Hashear contraseña para LDAP
     */
    protected function hashPassword($password)
    {
        return '{SSHA}' . base64_encode(sha1($password . openssl_random_pseudo_bytes(4), true) . openssl_random_pseudo_bytes(4));
    }
    
    /**
     * Obtener el nombre del usuario actual para logs
     */
    protected function getCurrentUsername()
    {
        // Intentar obtener de la sesión LDAP
        if (session()->has('auth_user') && !empty(session('auth_user')['username'])) {
            return session('auth_user')['username'];
        }
        
        // Intentar obtener del usuario autenticado
        if (auth()->check()) {
            return auth()->user()->name;
        }
        
        // Intentar obtener de la sesión
        if (session()->has('ldap_uid')) {
            return session('ldap_uid');
        }
        
        // Valor por defecto
        return 'sistema';
    }
    
    /**
     * Muestra los logs de actividad LDAP.
     *
     * @return \Illuminate\View\View
     */
    public function logs()
    {
        // Definir la ruta al archivo de log de Laravel
        $logFile = storage_path('logs/laravel.log');
        $logs = [];
        $id = 1;
        
        try {
            // Verificar si el archivo existe
            if (file_exists($logFile)) {
                // Obtener las últimas 500 líneas del archivo para tener más datos
                $command = "tail -n 500 " . escapeshellarg($logFile);
                $logContent = shell_exec($command);
                
                if ($logContent) {
                    // Dividir el contenido en líneas
                    $logLines = explode("\n", $logContent);
                    
                    // Palabras clave para filtrar logs de acciones de usuarios LDAP
                    $userActionKeywords = [
                        'Usuario LDAP creado', 
                        'Usuario LDAP actualizado', 
                        'Usuario LDAP eliminado',
                        'añadido al grupo de administradores',
                        'removido del grupo de administradores',
                        'Contraseña restablecida para usuario LDAP'
                    ];
                    
                    // Procesar cada línea
                    foreach ($logLines as $line) {
                        if (empty(trim($line))) continue;
                        
                        // Verificar si la línea contiene alguna de las palabras clave de acciones de usuarios
                        $containsUserAction = false;
                        foreach ($userActionKeywords as $keyword) {
                            if (stripos($line, $keyword) !== false) {
                                $containsUserAction = true;
                        break;
                    }
                }
                
                        // Si no contiene acciones de usuarios, saltamos esta línea
                        if (!$containsUserAction) continue;
                        
                        // Intentar extraer información del log
                        preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] (\w+)\.(\w+): (.+)/', $line, $matches);
                        
                        if (count($matches) >= 5) {
                            $fecha = $matches[1];
                            $nivel = $matches[2];
                            $canal = $matches[3];
                            $mensaje = $matches[4];
                            
                            // Extraer el usuario que realizó la acción
                            $usuario = 'Sistema';
                            if (preg_match('/por ([^\s\.]+)/', $mensaje, $userMatches)) {
                                $usuario = $userMatches[1];
                            }
                            
                            // Determinar acción basada en el contenido del mensaje
                            $accion = 'Acción LDAP';
                            if (stripos($mensaje, 'creado') !== false) {
                                $accion = 'Creación';
                            } elseif (stripos($mensaje, 'eliminado') !== false) {
                                $accion = 'Eliminación';
                            } elseif (stripos($mensaje, 'actualizado') !== false) {
                                $accion = 'Actualización';
                            } elseif (stripos($mensaje, 'añadido al grupo') !== false) {
                                $accion = 'Asignación Admin';
                            } elseif (stripos($mensaje, 'removido del grupo') !== false) {
                                $accion = 'Desasignación Admin';
                            } elseif (stripos($mensaje, 'Contraseña restablecida') !== false) {
                                $accion = 'Cambio Contraseña';
                            }
                            
                            $logs[] = [
                                'id' => $id++,
                                'fecha' => $fecha,
                                'nivel' => $nivel,
                                'usuario' => $usuario,
                                'accion' => $accion,
                                'descripcion' => $mensaje,
                            ];
                        }
                    }
                }
                
                \Log::info('Logs de acciones de usuarios LDAP procesados: ' . count($logs) . ' líneas.');
            } else {
                \Log::warning('Archivo de log no encontrado: ' . $logFile);
                
                // Generar mensaje de advertencia si no se encuentra el archivo
                $logs[] = [
                    'id' => $id++,
                    'fecha' => date('Y-m-d H:i:s'),
                    'nivel' => 'WARNING',
                    'usuario' => 'Sistema',
                    'accion' => 'Advertencia',
                    'descripcion' => 'No se pudo encontrar el archivo de logs. Verifica la configuración.',
                ];
            }
            
            // Si no hay logs relacionados con usuarios, mostrar un mensaje
            if (empty($logs)) {
                $logs[] = [
                    'id' => $id++,
                    'fecha' => date('Y-m-d H:i:s'),
                    'nivel' => 'INFO',
                    'usuario' => 'Sistema',
                    'accion' => 'Información',
                    'descripcion' => 'No se encontraron registros de acciones realizadas por usuarios LDAP.',
                ];
            }
            
        } catch (\Exception $e) {
            \Log::error('Error al procesar logs: ' . $e->getMessage());
            
            // En caso de error, mostrar entrada informativa
            $logs[] = [
                'id' => $id++,
                'fecha' => date('Y-m-d H:i:s'),
                'nivel' => 'ERROR',
                'usuario' => 'Sistema',
                'accion' => 'Error',
                'descripcion' => 'Error al procesar los logs: ' . $e->getMessage(),
            ];
        }
        
        // Ordenar logs por fecha descendente (más recientes primero)
        usort($logs, function($a, $b) {
            return strtotime($b['fecha']) - strtotime($a['fecha']);
        });
        
        return view('admin.users.logs', compact('logs'));
    }
} 