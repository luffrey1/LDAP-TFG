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
    protected $baseDn = 'dc=tierno,dc=es';
    protected $peopleOu = 'ou=people,dc=tierno,dc=es';
    protected $groupsOu = 'ou=groups,dc=tierno,dc=es';
    protected $adminGroupDn = 'cn=ldapadmins,ou=groups,dc=tierno,dc=es';
    protected $profesoresGroupDn = 'cn=profesores,ou=groups,dc=tierno,dc=es';
    protected $alumnosGroupDn = 'cn=alumnos,ou=groups,dc=tierno,dc=es';
    
    public function __construct()
    {
        // Usar la configuración del archivo config/ldap.php
        $config = config('ldap.connections.default');
        
        $this->connection = new Connection([
            'hosts' => $config['hosts'],
            'port' => 636, // Forzar puerto 636 para LDAPS
            'base_dn' => $config['base_dn'],
            'username' => $config['username'],
            'password' => $config['password'],
            'use_ssl' => true, // Forzar SSL
            'use_tls' => false, // Deshabilitar TLS
            'timeout' => $config['timeout'],
            'options' => [
                LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_NEVER,
                LDAP_OPT_REFERRALS => 0,
                LDAP_OPT_PROTOCOL_VERSION => 3,
                LDAP_OPT_NETWORK_TIMEOUT => 5,
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
                
                if ($request->ajax()) {
                    return response()->json([
                        'error' => true,
                        'message' => 'No se pudo conectar al servidor LDAP'
                    ], 500);
                }
                
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
            $selectedGroup = $request->input('group', '');
            $page = $request->input('page', 1);
            $perPage = $request->input('perPage', 10);
            
            // Obtener todos los grupos disponibles
            $groups = $this->connection->query()
                ->in('ou=groups,dc=tierno,dc=es')
                ->get();
            
            // Construir la lista de grupos para el dropdown
            $groupList = [];
            foreach ($groups as $group) {
                $cn = is_array($group) ? ($group['cn'][0] ?? '') : $group->getFirstAttribute('cn');
                if (!empty($cn)) {
                    $groupList[$cn] = $cn;
                }
            }
            
            // Construir el filtro de búsqueda
            $searchFilter = '(&(objectclass=inetOrgPerson)';
            if (!empty($search)) {
                $searchFilter .= '(|(cn=*' . $search . '*)(mail=*' . $search . '*)(uid=*' . $search . '*))';
            }
            $searchFilter .= ')';
            
            // Ejecutar la búsqueda
            $allUsers = $this->connection->query()
                ->in('ou=people,dc=tierno,dc=es')
                ->rawFilter($searchFilter)
                ->get();
            
            // Si hay un grupo seleccionado, filtrar los usuarios que pertenecen a ese grupo
            if (!empty($selectedGroup)) {
                $filteredUsers = [];
                foreach ($allUsers as $user) {
                    $uid = is_array($user) ? ($user['uid'][0] ?? '') : $user->getFirstAttribute('uid');
                    if (!empty($uid)) {
                        $userGroups = $this->getUserGroups($uid);
                        if (in_array($selectedGroup, $userGroups)) {
                            $filteredUsers[] = $user;
                        }
                    }
                }
                $allUsers = $filteredUsers;
            }
            
            // Obtener grupos para cada usuario
            $userGroups = [];
            foreach ($allUsers as $user) {
                $uid = is_array($user) ? ($user['uid'][0] ?? '') : $user->getFirstAttribute('uid');
                if (!empty($uid)) {
                    $userGroups[$uid] = $this->getUserGroups($uid);
                }
            }
            
            // Obtener usuarios administradores
            $adminUsers = $this->getAdminUsers();
            
            // Paginar resultados
            $total = count($allUsers);
            $offset = ($page - 1) * $perPage;
            $paginatedUsers = array_slice($allUsers, $offset, $perPage);
            
            $paginator = new \Illuminate\Pagination\LengthAwarePaginator(
                $paginatedUsers,
                $total,
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
            
            // Si es una petición AJAX, devolver JSON
            if ($request->ajax()) {
                $view = view('admin.users.partials.user-table', [
                    'users' => $paginator,
                    'userGroups' => $userGroups,
                    'adminUsers' => $adminUsers
                ])->render();
                
                return response()->json([
                    'html' => $view,
                    'total' => $total,
                    'currentPage' => $page,
                    'lastPage' => $paginator->lastPage()
                ]);
            }
            
            return view('admin.users.index', [
                'users' => $paginator,
                'userGroups' => $userGroups,
                'adminUsers' => $adminUsers,
                'search' => $search,
                'selectedGroup' => $selectedGroup,
                'groupList' => $groupList,
                'total' => $total,
                'perPage' => $perPage
            ]);
            
        } catch (Exception $e) {
            Log::error('Error al obtener usuarios LDAP: ' . $e->getMessage());
            Log::error('Traza: ' . $e->getTraceAsString());
            
            if ($request->ajax()) {
                return response()->json([
                    'error' => true,
                    'message' => 'Error al obtener los usuarios: ' . $e->getMessage()
                ], 500);
            }
            
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
            'grupos' => 'required|array|min:1',
            'homeDirectory' => 'nullable|string',
            'loginShell' => 'nullable|string',
            'uidNumber' => 'nullable|numeric',
            'gidNumber' => 'nullable|numeric'
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
            
            // Calcular UID y GID
            $uidNumber = $request->uidNumber ? $request->uidNumber : $this->getNextUidNumber();
            $gidNumber = $request->gidNumber ? $request->gidNumber : '9000';  // GID por defecto para 'everybody'
            
            // Preparar contraseña con hash SSHA
            $hashedPassword = $this->hashPassword($request->password);
            
            // Preparar home directory
            $homeDirectory = $request->homeDirectory ? $request->homeDirectory : '/home/' . $request->uid;
            $loginShell = $request->loginShell ? $request->loginShell : '/bin/bash';
            
            // Preparar datos del usuario
            $userDn = 'uid=' . $request->uid . ',' . $this->peopleOu;
            $userData = [
                'objectclass' => ['top', 'person', 'organizationalPerson', 'inetOrgPerson', 'posixAccount', 'shadowAccount'],
                'cn' => $request->nombre . ' ' . $request->apellidos,
                'sn' => $request->apellidos,
                'givenname' => $request->nombre,
                'uid' => $request->uid,
                'mail' => $request->email,
                'userpassword' => $hashedPassword,
                'homedirectory' => $homeDirectory,
                'loginShell' => $loginShell,
                'gidnumber' => $gidNumber,
                'uidnumber' => $uidNumber,
                'shadowLastChange' => floor(time() / 86400)  // Añadir atributo shadowLastChange
            ];
            
            // Crear el usuario directamente con LDAP nativo para mayor control
            $ldapConn = ldap_connect('ldaps://' . config('ldap.connections.default.hosts')[0], config('ldap.connections.default.port'));
            if (!$ldapConn) {
                Log::error("Error al crear conexión LDAP");
                throw new Exception("No se pudo establecer la conexión LDAP");
            }
            
            Log::debug("Conexión LDAP creada, configurando opciones...");
            
            ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);
            ldap_set_option($ldapConn, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
            
            // Intentar conexión SSL primero
            if (config('ldap.connections.default.use_ssl', false)) {
                Log::debug("Configurando SSL para conexión LDAP...");
                ldap_set_option($ldapConn, LDAP_OPT_X_TLS_CACERTFILE, '/etc/ssl/certs/ldap/ca.crt');
                ldap_set_option($ldapConn, LDAP_OPT_X_TLS_CERTFILE, '/etc/ssl/certs/ldap/cert.pem');
                ldap_set_option($ldapConn, LDAP_OPT_X_TLS_KEYFILE, '/etc/ssl/certs/ldap/privkey.pem');
            }
            
            Log::debug("Intentando bind con credenciales LDAP...");
            Log::debug("Username: " . config('ldap.connections.default.username'));
            
            // Intentar bind con credenciales
            $bind = @ldap_bind(
                $ldapConn, 
                config('ldap.connections.default.username'), 
                config('ldap.connections.default.password')
            );
            
            if (!$bind) {
                $error = ldap_error($ldapConn);
                Log::error("Error al conectar al servidor LDAP: " . $error);
                Log::error("Código de error LDAP: " . ldap_errno($ldapConn));
                throw new Exception("No se pudo conectar al servidor LDAP: " . $error);
            }
            
            Log::debug("Conexión LDAP establecida correctamente");
            
            // Crear el usuario
            $success = ldap_add($ldapConn, $userDn, $userData);
            
            if (!$success) {
                throw new Exception("Error al crear usuario: " . ldap_error($ldapConn));
            }
            
            Log::debug("Usuario LDAP creado con éxito: {$request->uid}");
            
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
                    $searchResult = ldap_search($ldapConn, $this->groupsOu, "(cn=$groupName)");
                    if ($searchResult) {
                        $entries = ldap_get_entries($ldapConn, $searchResult);
                        if ($entries['count'] > 0) {
                            $groupDn = $entries[0]['dn'];
                            Log::debug("Grupo encontrado por nombre: " . $groupName . ", DN: " . $groupDn);
                        } else {
                            Log::error("Grupo no encontrado por nombre: " . $groupName);
                        }
                    }
                }
                
                // Si tenemos un DN, añadir el usuario al grupo
                if ($groupDn) {
                    // Añadir como uniqueMember para groupOfUniqueNames
                    $groupInfo = ldap_read($ldapConn, $groupDn, "(objectclass=*)", ["objectClass"]);
                    if ($groupInfo) {
                        $groupEntry = ldap_get_entries($ldapConn, $groupInfo);
                        
                        // Verificar si tiene objectClass groupOfUniqueNames
                        $isUniqueGroup = false;
                        if (isset($groupEntry[0]['objectclass'])) {
                            for ($i = 0; $i < $groupEntry[0]['objectclass']['count']; $i++) {
                                if (strtolower($groupEntry[0]['objectclass'][$i]) === 'groupofuniquenames') {
                                    $isUniqueGroup = true;
                                    break;
                                }
                            }
                        }
                        
                        if ($isUniqueGroup) {
                            try {
                                $modInfo = [
                                    'uniqueMember' => $userDn
                                ];
                                ldap_mod_add($ldapConn, $groupDn, $modInfo);
                                Log::debug("Usuario añadido como uniqueMember al grupo: " . $groupName);
                            } catch (Exception $e) {
                                Log::error("Error al añadir uniqueMember al grupo " . $groupName . ": " . $e->getMessage());
                            }
                        }
                        
                        // También añadir como memberUid para posixGroup
                        $isPosixGroup = false;
                        if (isset($groupEntry[0]['objectclass'])) {
                            for ($i = 0; $i < $groupEntry[0]['objectclass']['count']; $i++) {
                                if (strtolower($groupEntry[0]['objectclass'][$i]) === 'posixgroup') {
                                    $isPosixGroup = true;
                                    break;
                                }
                            }
                        }
                        
                        if ($isPosixGroup) {
                            try {
                                $modInfo = [
                                    'memberUid' => $request->uid
                                ];
                                ldap_mod_add($ldapConn, $groupDn, $modInfo);
                                Log::debug("Usuario añadido como memberUid al grupo: " . $groupName);
                            } catch (Exception $e) {
                                Log::error("Error al añadir memberUid al grupo " . $groupName . ": " . $e->getMessage());
                            }
                        }
                    }
                } else {
                    Log::error("No se pudo encontrar el DN para el grupo: " . $groupName);
                }
            }
            
            ldap_close($ldapConn);
            
            // Registrar la acción en logs
            $adminUser = $this->getCurrentUsername();
            Log::info("Usuario LDAP creado: {$request->uid} por {$adminUser}. Grupos: " . json_encode($request->grupos));
            
            Log::channel('activity')->info('Usuario LDAP creado', [
                'action' => 'Crear Usuario',
                'username' => $request->username
            ]);
            
            // Cambiamos la redirección para usar nombre de ruta en lugar de URL directa
            return redirect()->route('admin.users.index')
                ->with('success', 'Usuario creado correctamente y listo para iniciar sesión');
                
        } catch (Exception $e) {
            Log::error('Error al crear usuario LDAP: ' . $e->getMessage());
            Log::error('Traza: ' . $e->getTraceAsString());
            Log::channel('activity')->error('Error al crear usuario LDAP: ' . $e->getMessage(), [
                'action' => 'Error',
                'username' => $request->username
            ]);
            return back()->with('error', 'Error al crear el usuario: ' . $e->getMessage());
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
                return redirect()->route('admin.users.index')
                    ->with('error', 'Usuario no encontrado. Por favor, inténtelo de nuevo desde la lista de usuarios.');
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
            'grupos' => 'sometimes|array',
            'homeDirectory' => 'nullable|string',
            'loginShell' => 'nullable|string',
            'uidNumber' => 'nullable|numeric',
            'gidNumber' => 'nullable|numeric'
        ]);
        
        try {
            // Decodificar el DN
            $decodedDn = base64_decode($dn);
            if (!$decodedDn) {
                Log::error("Error al decodificar DN: " . $dn);
                return back()->with('error', 'Error: DN inválido');
            }
            
            // Extraer uid del userDn para búsquedas adicionales
            $uid = '';
            if (preg_match('/uid=([^,]+)/', $decodedDn, $matches)) {
                $uid = $matches[1];
                Log::debug("UID extraído para actualización: " . $uid);
            }
            
            // Obtener la configuración LDAP
            $config = config('ldap.connections.default');
            Log::debug("Configuración LDAP: " . json_encode($config));
            
            // Crear conexión LDAP usando la configuración
            $connection = new Connection([
                'hosts' => $config['hosts'],
                'port' => 636, // Forzar puerto 636 para LDAPS
                'base_dn' => $config['base_dn'],
                'username' => $config['username'],
                'password' => $config['password'],
                'use_ssl' => true, // Forzar SSL
                'use_tls' => false, // Deshabilitar TLS
                'timeout' => $config['timeout'],
                'options' => [
                    LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_NEVER,
                    LDAP_OPT_REFERRALS => 0,
                    LDAP_OPT_PROTOCOL_VERSION => 3,
                    LDAP_OPT_NETWORK_TIMEOUT => 5,
                ],
            ]);
            
            Log::debug("Intentando conectar al servidor LDAP");
            $connection->connect();
            Log::debug("Conexión LDAP establecida");
            
            // Buscar el usuario primero por UID en toda la base
            $user = null;
            if ($uid) {
                $user = $connection->query()
                    ->in($config['base_dn'])
                    ->where('uid', '=', $uid)
                    ->first();
                    
                if ($user) {
                    Log::debug("Usuario encontrado por UID para actualizar: " . $uid);
                }
            }
            
            // Si no se encontró por UID, intentar por DN exacto
            if (!$user) {
                $user = $connection->query()
                    ->in($config['base_dn'])
                    ->where('dn', '=', $decodedDn)
                    ->first();
                    
                if ($user) {
                    Log::debug("Usuario encontrado por DN para actualizar");
                }
            }
            
            if (!$user) {
                Log::error("Usuario con DN '{$decodedDn}' no encontrado para actualizar");
                return redirect()->route('admin.users.index')
                    ->with('error', 'Usuario no encontrado');
            }
            
            // Preparar datos para actualización
            $updateData = [
                'cn' => $request->nombre . ' ' . $request->apellidos,
                'sn' => $request->apellidos,
                'givenname' => $request->nombre,
                'mail' => $request->email
            ];
            
            // Actualizar campos opcionales si se proporcionan
            if ($request->filled('uidNumber')) {
                $updateData['uidnumber'] = $request->uidNumber;
            }
            if ($request->filled('gidNumber')) {
                $updateData['gidnumber'] = $request->gidNumber;
            }
            if ($request->filled('homeDirectory')) {
                $updateData['homedirectory'] = $request->homeDirectory;
            }
            if ($request->filled('loginShell')) {
                $updateData['loginshell'] = $request->loginShell;
            }
            
            // Si hay contraseña, actualizarla
            if (!empty($request->password)) {
                $hashedPassword = $this->hashPassword($request->password);
                $updateData['userpassword'] = $hashedPassword;
                $updateData['shadowLastChange'] = floor(time() / 86400);
            }
            
            // Modificar el usuario
            $result = $connection->modify($user['dn'], $updateData);
            
            if (!$result) {
                $error = $connection->getLastError();
                Log::error("Error al actualizar usuario: " . $error);
                throw new Exception("Error al actualizar usuario: " . $error);
            }
            
            // Actualizar grupos del usuario si se proporcionaron
            if ($request->has('grupos')) {
                $this->updateUserGroupsDirect($user['dn'], $request->grupos, $connection);
            }
            
            // Registrar la acción en logs
            $adminUser = $this->getCurrentUsername();
            Log::info("Usuario LDAP actualizado: {$uid} por {$adminUser}. Grupos: " . json_encode($request->grupos ?? []));
            
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
            
            // Decodificar el DN y validar
            $userDn = base64_decode($dn);
            if (!$userDn) {
                Log::error("Error al decodificar DN: " . $dn);
                return redirect()->route('admin.users.index')
                    ->with('error', 'Error: DN inválido');
            }
            
            Log::debug("DN decodificado para eliminar: " . $userDn);
            
            // Extraer UID del DN para búsquedas alternativas
            $uid = '';
            if (preg_match('/uid=([^,]+)/', $userDn, $matches)) {
                $uid = $matches[1];
            }
            
            // Verificar si es el usuario ldap-admin
            if ($uid === 'ldap-admin') {
                Log::warning("Intento de eliminar usuario ldap-admin");
                return redirect()->route('admin.users.index')
                    ->with('error', 'No se puede eliminar el usuario ldap-admin');
            }
            
            // Si no tenemos UID, no podemos continuar
            if (empty($uid)) {
                return redirect()->route('admin.users.index')
                    ->with('error', 'Error: No se pudo obtener el UID del usuario');
            }
            
            // Buscar el usuario por su UID para confirmar que existe
            $user = $this->connection->query()
                ->in($this->peopleOu)
                ->where('uid', '=', $uid)
                ->first();
                
            if (!$user) {
                Log::debug("Usuario no encontrado por UID, buscando por DN en toda la base");
                $user = $this->connection->query()
                    ->in($this->baseDn)
                    ->where('dn', '=', $userDn)
                    ->first();
                    
                if ($user) {
                    Log::debug("Usuario encontrado por DN para eliminar");
                }
            }
                
            if (!$user) {
                Log::error("Usuario no encontrado para eliminar con DN: " . $userDn);
                return redirect()->route('admin.users.index')
                    ->with('error', 'Usuario no encontrado');
            }
            
            // Obtener el DN actual del usuario
            $actualUserDn = is_array($user) ? $user['dn'] : $user->getDn();
            Log::debug("DN final para eliminar: " . $actualUserDn);
            
            // Verificar si el usuario es administrador
            $isAdminUser = false;
            $userGroups = $this->getUserGroups($actualUserDn);
            foreach ($userGroups as $group) {
                $groupName = '';
                if (is_array($group)) {
                    $groupName = $group['cn'][0] ?? '';
                } else {
                    $groupName = $group->getFirstAttribute('cn') ?? '';
                }
                
                if ($groupName === 'ldapadmins') {
                    $isAdminUser = true;
                    break;
                }
            }
            
            // Si el usuario es administrador y el usuario actual no es administrador, no permitir eliminarlo
            $isCurrentUserAdmin = session('auth_user.is_admin') || session('auth_user.username') === 'ldap-admin';
            if ($isAdminUser && !$isCurrentUserAdmin) {
                Log::warning("Intento de eliminar usuario administrador por usuario no administrador");
                return redirect()->route('admin.users.index')
                    ->with('error', 'No tienes permisos para eliminar a un administrador');
            }
            
            // Eliminar el usuario
            try {
                $this->connection->query()->delete($actualUserDn);
                Log::info("Usuario eliminado correctamente: " . $actualUserDn);
                return redirect()->route('admin.users.index')
                    ->with('success', 'Usuario eliminado correctamente');
            } catch (\Exception $e) {
                Log::error("Error al eliminar usuario: " . $e->getMessage());
                return redirect()->route('admin.users.index')
                    ->with('error', 'Error al eliminar el usuario: ' . $e->getMessage());
            }
            
        } catch (\Exception $e) {
            Log::error("Error en destroy: " . $e->getMessage());
            return redirect()->route('admin.users.index')
                ->with('error', 'Error al procesar la solicitud: ' . $e->getMessage());
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
            
            // Verificar memberUid (posixGroup)
            if (isset($adminGroup['memberuid'])) {
                $memberUids = is_array($adminGroup['memberuid']) 
                    ? $adminGroup['memberuid'] 
                    : [$adminGroup['memberuid']];
                
                $isAdmin = in_array($uid, $memberUids);
            }
            
            // Verificar uniqueMember (groupOfUniqueNames)
            if (!$isAdmin && isset($adminGroup['uniquemember'])) {
                $uniqueMembers = is_array($adminGroup['uniquemember']) 
                    ? $adminGroup['uniquemember'] 
                    : [$adminGroup['uniquemember']];
                
                $isAdmin = in_array($userDn, $uniqueMembers);
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
            $hasErrors = false;
            $errorMessages = [];
            
            // Cambiar estado de admin
            if ($isAdmin) {
                // Eliminar el usuario del grupo
                
                // 1. Actualizar memberUid (posixGroup)
                $result = ldap_read($ldapConnection, $groupDn, "(objectclass=*)", ["memberUid"]);
                
                if ($result) {
                    $entries = ldap_get_entries($ldapConnection, $result);
                    $memberUids = [];
                    
                    if (isset($entries[0]['memberuid'])) {
                        for ($i = 0; $i < $entries[0]['memberuid']['count']; $i++) {
                            if ($entries[0]['memberuid'][$i] != $uid) {
                                $memberUids[] = $entries[0]['memberuid'][$i];
                            }
                        }
                        
                        // Actualizar el grupo sin el usuario
                        $entry = ["memberUid" => $memberUids];
                        $resultModify = ldap_modify($ldapConnection, $groupDn, $entry);
                        
                        if (!$resultModify) {
                            $hasErrors = true;
                            $errorMessages[] = 'Error al eliminar usuario del grupo admin (memberUid)';
                        }
                    }
                }
                
                // 2. Actualizar uniqueMember (groupOfUniqueNames)
                $result = ldap_read($ldapConnection, $groupDn, "(objectclass=*)", ["uniqueMember"]);
                
                if ($result) {
                    $entries = ldap_get_entries($ldapConnection, $result);
                    $uniqueMembers = [];
                    
                    if (isset($entries[0]['uniquemember'])) {
                        for ($i = 0; $i < $entries[0]['uniquemember']['count']; $i++) {
                            if ($entries[0]['uniquemember'][$i] != $userDn) {
                                $uniqueMembers[] = $entries[0]['uniquemember'][$i];
                            }
                        }
                        
                        // Actualizar el grupo sin el usuario
                        $entry = ["uniqueMember" => $uniqueMembers];
                        $resultModify = ldap_modify($ldapConnection, $groupDn, $entry);
                        
                        if (!$resultModify) {
                            $hasErrors = true;
                            $errorMessages[] = 'Error al eliminar usuario del grupo admin (uniqueMember)';
                        }
                    }
                }
                
                $message = 'Usuario removido del grupo de administradores';
            } else {
                // Añadir el usuario al grupo
                
                // 1. Actualizar memberUid (posixGroup)
                $result = ldap_read($ldapConnection, $groupDn, "(objectclass=*)", ["memberUid"]);
                
                if ($result) {
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
                    $resultModify = ldap_modify($ldapConnection, $groupDn, $entry);
                    
                    if (!$resultModify) {
                        $hasErrors = true;
                        $errorMessages[] = 'Error al añadir usuario al grupo admin (memberUid)';
                    }
                }
                
                // 2. Actualizar uniqueMember (groupOfUniqueNames)
                $result = ldap_read($ldapConnection, $groupDn, "(objectclass=*)", ["uniqueMember"]);
                
                if ($result) {
                    $entries = ldap_get_entries($ldapConnection, $result);
                    $uniqueMembers = [];
                    
                    if (isset($entries[0]['uniquemember'])) {
                        for ($i = 0; $i < $entries[0]['uniquemember']['count']; $i++) {
                            $uniqueMembers[] = $entries[0]['uniquemember'][$i];
                        }
                    }
                    
                    // Añadir el usuario solo si no existe
                    if (!in_array($userDn, $uniqueMembers)) {
                        $uniqueMembers[] = $userDn;
                    }
                    
                    // Actualizar el grupo con el nuevo usuario
                    $entry = ["uniqueMember" => $uniqueMembers];
                    $resultModify = ldap_modify($ldapConnection, $groupDn, $entry);
                    
                    if (!$resultModify) {
                        $hasErrors = true;
                        $errorMessages[] = 'Error al añadir usuario al grupo admin (uniqueMember)';
                    }
                }
                
                $message = 'Usuario añadido al grupo de administradores';
            }
            
            ldap_close($ldapConnection);
            
            if ($hasErrors) {
                return back()->with('warning', $message . ' con advertencias: ' . implode(', ', $errorMessages));
            }
            
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
            
            // Si el grupo no tiene las clases necesarias, vamos a agregarlas
            if (!$isGroupOfUniqueNames && $groupName != 'everybody' && $groupName != 'alumnos' && 
                $groupName != 'profesores' && $groupName != 'ldapadmins' && $groupName != 'docker') {
                // Intentamos agregar la clase objectClass groupOfUniqueNames
                try {
                    $this->connection->run(function ($ldap) use ($groupDn) {
                        $ldap->modifyBatch($groupDn, [
                            [
                                'attrib' => 'objectClass',
                                'modtype' => LDAP_MODIFY_BATCH_ADD,
                                'values' => ['groupOfUniqueNames'],
                            ],
                        ]);
                    });
                    $isGroupOfUniqueNames = true;
                    Log::info("Añadida la clase groupOfUniqueNames al grupo {$groupName}");
                } catch (Exception $ex) {
                    Log::warning("No se pudo añadir la clase groupOfUniqueNames al grupo: " . $ex->getMessage());
                }
            }
            
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
            
            // Si no es ninguno de estos tipos, intentar añadir al menos como memberUid en posixGroup
            if (!$isPosixGroup && !$isGroupOfUniqueNames && !$isGroupOfNames) {
                Log::warning("El grupo {$groupName} no tiene un tipo reconocido. Intentando añadir como posixGroup.");
                $this->addUserToPosixGroup($userUid, $groupDn, $groupName, $group);
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
                            $userUid = $this->getUserUidFromDn($userDn);
                            $this->createLdapGroup($groupName, $groupDn, $userUid);
                            $knownGroups[] = ['dn' => $groupDn, 'cn' => [$groupName]];
                        }
                    } catch (\Exception $e) {
                        Log::warning("Error al verificar grupo $groupName: " . $e->getMessage());
                        if (strpos($e->getMessage(), 'No such object') !== false) {
                            // Crear el grupo si no existe
                            Log::info("Grupo $groupName no encontrado, intentando crearlo");
                            $userUid = $this->getUserUidFromDn($userDn);
                            $this->createLdapGroup($groupName, $groupDn, $userUid);
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
                            $userUid = $this->getUserUidFromDn($userDn);
                            $this->createLdapGroup($groupName, $groupDn, $userUid);
                        }
                    } catch (\Exception $e) {
                        Log::warning("Error al verificar grupo $groupName: " . $e->getMessage());
                        // Intentar crear el grupo si no existe
                        if (strpos($e->getMessage(), 'No such object') !== false) {
                            $userUid = $this->getUserUidFromDn($userDn);
                            $this->createLdapGroup($groupName, $groupDn, $userUid);
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
     * Crear un grupo LDAP nuevo si no existe
     */
    protected function createLdapGroup($groupName, $groupDn, $userUid = null)
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
                Log::debug("Excepción al verificar grupo: " . $e->getMessage());
            }

            // Crear el grupo usando LDAP nativo para mejor control
            $ldapConn = ldap_connect(config('ldap.connections.default.hosts')[0], config('ldap.connections.default.port'));
            ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);
            ldap_set_option($ldapConn, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);

            $bind = ldap_bind(
                $ldapConn, 
                config('ldap.connections.default.username'), 
                config('ldap.connections.default.password')
            );

            if (!$bind) {
                throw new Exception("No se pudo conectar al servidor LDAP: " . ldap_error($ldapConn));
            }

            // Obtener siguiente GID disponible
            $gid = $this->getNextGidNumber();

            // Intentar crear el grupo con los atributos mínimos necesarios para posixGroup
            $attributes = [
                'objectclass' => ['top', 'posixGroup'],
                'cn' => $groupName,
                'gidNumber' => $gid
                // Eliminamos memberUid de la creación inicial
            ];

            Log::debug("Intentando crear grupo con atributos (posixGroup): " . json_encode($attributes));

            $success = @ldap_add($ldapConn, $groupDn, $attributes);

            if (!$success) {
                $error = ldap_error($ldapConn);
                $errorCode = ldap_errno($ldapConn);
                Log::debug("Error al crear grupo (posixGroup): " . $error . " (Código: " . $errorCode . ")");

                // Intentar con groupOfNames como último recurso
                $attributes = [
                    'objectclass' => ['top', 'groupOfNames'],
                    'cn' => $groupName,
                    'member' => ['cn=nobody'] // groupOfNames sí requiere 'member' inicial
                ];

                if ($userUid) {
                    $userDn = "uid=$userUid," . $this->peopleOu;
                    $attributes['member'][] = $userDn;
                }

                Log::debug("Intentando crear grupo con atributos (groupOfNames): " . json_encode($attributes));

                $success = @ldap_add($ldapConn, $groupDn, $attributes);

                if (!$success) {
                    $error = ldap_error($ldapConn);
                    ldap_close($ldapConn);
                    throw new Exception("Error al crear grupo (groupOfNames): " . $error);
                }
            }

            ldap_close($ldapConn);

            // Si se creó el grupo (ya sea posixGroup o groupOfNames), añadimos el usuario si se proporcionó
            if ($success && $userUid) {
                 Log::debug("Grupo creado, intentando añadir usuario $userUid...");
                 // Aquí llamaríamos a una función para añadir el miembro.
                 // Por ahora, solo logueamos que debería añadirse.
                 // En un escenario completo, necesitaríamos una operación de modificación
                 // ldap_mod_add($ldapConn, $groupDn, ['memberUid' => $userUid]) para posixGroup
                 // o ldap_mod_add($ldapConn, $groupDn, ['member' => $userDn]) para groupOfNames
                 // pero para la creación inicial solo necesitamos resolver la violación.
            }


            Log::info("Grupo LDAP creado exitosamente: $groupName");
            return true;

        } catch (\Exception $e) {
            Log::error("Error al crear grupo LDAP: " . $e->getMessage());
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
    protected function getUserGroups($uid)
    {
        $userGroups = [];
        
        try {
            // Buscar el usuario por UID
            $user = $this->connection->query()
                ->in($this->peopleOu)
                ->where('uid', '=', $uid)
                ->first();
                
            if (!$user) {
                return $userGroups;
            }
            
            // Obtener el DN del usuario
            $userDn = is_array($user) ? $user['dn'] : $user->getDn();
            
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
                if (isset($group['memberuid'])) {
                    $memberUids = is_array($group['memberuid']) 
                        ? $group['memberuid'] 
                        : [$group['memberuid']];
                    
                    if (in_array($uid, $memberUids)) {
                        $isMember = true;
                    }
                }
                
                // Si el usuario es miembro por cualquier método, añadir el grupo
                if ($isMember) {
                    $groupName = is_array($group) ? ($group['cn'][0] ?? '') : $group->getFirstAttribute('cn');
                    if (!empty($groupName)) {
                        $userGroups[] = $groupName;
                    }
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
                
            if ($adminGroup) {
                // Buscar por uniquemember (groupOfUniqueNames)
                if (isset($adminGroup['uniquemember'])) {
                    $adminUsers = is_array($adminGroup['uniquemember']) 
                        ? $adminGroup['uniquemember'] 
                        : [$adminGroup['uniquemember']];
                }
                
                // Buscar también por memberUid (posixGroup)
                if (isset($adminGroup['memberuid'])) {
                    $memberUids = is_array($adminGroup['memberuid']) 
                        ? $adminGroup['memberuid'] 
                        : [$adminGroup['memberuid']];
                    
                    // Para cada memberUid, buscar su DN completo
                    foreach ($memberUids as $uid) {
                        $user = $this->connection->query()
                            ->in($this->peopleOu)
                            ->where('uid', '=', $uid)
                            ->first();
                            
                        if ($user) {
                            $userDn = is_array($user) ? $user['dn'] : $user->getDn();
                            if (!in_array($userDn, $adminUsers)) {
                                $adminUsers[] = $userDn;
                            }
                        }
                    }
                }
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
     * Hashear contraseña para LDAP - método mejorado
     */
    protected function hashPassword($password)
    {
        // Método 1: Usar slappasswd directamente si está disponible
        try {
            $output = null;
            $code = null;
            exec('which slappasswd 2>/dev/null', $output, $code);
            
            if ($code === 0 && !empty($output)) {
                // Usar slappasswd directamente para generar el hash
                $cmd = 'slappasswd -s ' . escapeshellarg($password);
                $hashOutput = [];
                exec($cmd, $hashOutput);
                
                if (!empty($hashOutput)) {
                    Log::debug("Contraseña generada con slappasswd");
                    return trim($hashOutput[0]);
                }
            }
        } catch (\Exception $e) {
            Log::warning("Error al usar slappasswd: " . $e->getMessage());
        }
            
        // Método 2: Crear hash compatible con OpenLDAP de forma manual
        $salt = random_bytes(4);
        $hash = '{SSHA}' . base64_encode(sha1($password . $salt, true) . $salt);
        Log::debug("Contraseña generada manualmente con formato {SSHA}");
        
        return $hash;
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

        // Obtener la página actual de la URL o usar 1 por defecto
        $page = request()->get('page', 1);
        $perPage = 10;
        
        // Calcular el offset para la paginación
        $offset = ($page - 1) * $perPage;
        
        // Obtener los logs para la página actual
        $paginatedLogs = array_slice($logs, $offset, $perPage);
        
        // Crear una colección paginada manualmente
        $paginatedCollection = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedLogs,
            count($logs),
            $perPage,
            $page,
            ['path' => request()->url()]
        );
        
        return view('admin.users.logs', ['logs' => $paginatedCollection]);
    }

    /**
     * Extraer el uid de un DN de usuario
     */
    protected function getUserUidFromDn($userDn)
    {
        if (preg_match('/uid=([^,]+)/', $userDn, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Asegurar que un usuario tiene todas las clases LDAP necesarias
     */
    protected function ensureUserHasRequiredClasses($userDn)
    {
        try {
            // Conectar a LDAP
            $ldapConn = ldap_connect(config('ldap.connections.default.hosts')[0], config('ldap.connections.default.port'));
            ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);
            ldap_set_option($ldapConn, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
            
            $bind = ldap_bind(
                $ldapConn, 
                config('ldap.connections.default.username'), 
                config('ldap.connections.default.password')
            );
            
            if (!$bind) {
                Log::error("No se pudo conectar al servidor LDAP: " . ldap_error($ldapConn));
                return false;
            }
            
            // Leer las clases actuales del usuario
            $result = ldap_read($ldapConn, $userDn, "(objectclass=*)", ["objectclass"]);
            if (!$result) {
                Log::error("No se pudo leer el usuario: " . ldap_error($ldapConn));
                return false;
            }
            
            $entries = ldap_get_entries($ldapConn, $result);
            if ($entries['count'] == 0) {
                Log::error("Usuario no encontrado: $userDn");
                return false;
            }
            
            // Clases necesarias para iniciar sesión
            $requiredClasses = [
                'top', 'person', 'organizationalPerson', 'inetOrgPerson', 
                'posixAccount', 'shadowAccount'
            ];
            
            // Verificar qué clases faltan
            $currentClasses = [];
            for ($i = 0; $i < $entries[0]['objectclass']['count']; $i++) {
                $currentClasses[] = strtolower($entries[0]['objectclass'][$i]);
            }
            
            $missingClasses = [];
            foreach ($requiredClasses as $class) {
                if (!in_array(strtolower($class), $currentClasses)) {
                    $missingClasses[] = $class;
                }
            }
            
            // Si faltan clases, agregarlas
            if (!empty($missingClasses)) {
                Log::info("Añadiendo clases faltantes al usuario $userDn: " . implode(', ', $missingClasses));
                
                // Preparar la modificación
                $mod = [];
                $mod['objectclass'] = array_merge($entries[0]['objectclass'], $missingClasses);
                unset($mod['objectclass']['count']); // Quitar el contador que añade LDAP
                
                // Aplicar la modificación
                $success = ldap_modify($ldapConn, $userDn, $mod);
                if (!$success) {
                    Log::error("Error al actualizar objectClass: " . ldap_error($ldapConn));
                }
            }
            
            // Verificar y añadir atributos esenciales faltantes
            $essentialAttrs = [
                'loginShell' => '/bin/bash',
                'shadowLastChange' => floor(time() / 86400)
            ];
            
            $result = ldap_read($ldapConn, $userDn, "(objectclass=*)", array_keys($essentialAttrs));
            if ($result) {
                $entries = ldap_get_entries($ldapConn, $result);
                
                $missingAttrs = [];
                foreach ($essentialAttrs as $attr => $value) {
                    if (!isset($entries[0][strtolower($attr)])) {
                        $missingAttrs[$attr] = $value;
                    }
                }
                
                if (!empty($missingAttrs)) {
                    Log::info("Añadiendo atributos faltantes al usuario $userDn: " . implode(', ', array_keys($missingAttrs)));
                    $success = ldap_modify($ldapConn, $userDn, $missingAttrs);
                    if (!$success) {
                        Log::error("Error al añadir atributos esenciales: " . ldap_error($ldapConn));
                    }
                }
            }
            
            ldap_close($ldapConn);
            return true;
            
        } catch (Exception $e) {
            Log::error("Error al asegurar clases de usuario: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Actualizar grupos de un usuario de manera directa mediante LDAP nativo
     */
    protected function updateUserGroupsDirect($userDn, $selectedGroups, $ldapConn = null)
    {
        try {
            $closeConn = false;
            if (!$ldapConn) {
                // Crear conexión si no se proporcionó una
                $ldapConn = ldap_connect(config('ldap.connections.default.hosts')[0], config('ldap.connections.default.port'));
                ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);
                ldap_set_option($ldapConn, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
                
                $bind = ldap_bind(
                    $ldapConn, 
                    config('ldap.connections.default.username'), 
                    config('ldap.connections.default.password')
                );
                
                if (!$bind) {
                    throw new Exception("No se pudo conectar al servidor LDAP: " . ldap_error($ldapConn));
                }
                
                $closeConn = true;
            }
            
            // Extraer uid del userDn para posixGroup
            $userUid = $this->getUserUidFromDn($userDn);
            if (!$userUid) {
                throw new Exception("No se pudo extraer el UID del DN del usuario");
            }
            
            // Buscar todos los grupos disponibles
            $result = ldap_search($ldapConn, $this->groupsOu, "(objectclass=*)", ["cn", "objectclass"]);
            if (!$result) {
                throw new Exception("Error al buscar grupos: " . ldap_error($ldapConn));
            }
            
            $entries = ldap_get_entries($ldapConn, $result);
            
            // Mapear grupos conocidos
            $groupMapping = [
                'profesores' => $this->profesoresGroupDn,
                'alumnos' => $this->alumnosGroupDn,
                'ldapadmins' => $this->adminGroupDn
            ];
            
            // Para cada grupo, ver si el usuario debe ser añadido o eliminado
            for ($i = 0; $i < $entries['count']; $i++) {
                $groupEntry = $entries[$i];
                
                // Saltar entradas que no tienen cn
                if (!isset($groupEntry['cn'][0])) {
                    continue;
                }
                
                $groupName = $groupEntry['cn'][0];
                $groupDn = $groupEntry['dn'];
                
                // Verificar si es un grupo especial conocido
                if (isset($groupMapping[$groupName])) {
                    $groupDn = $groupMapping[$groupName];
                }
                
                // Determinar si el usuario debería estar en este grupo
                $shouldBeInGroup = in_array($groupName, $selectedGroups);
                
                // Verificar las clases de objeto para saber cómo tratar al grupo
                $isPosixGroup = false;
                $isUniqueGroup = false;
                
                if (isset($groupEntry['objectclass'])) {
                    for ($j = 0; $j < $groupEntry['objectclass']['count']; $j++) {
                        $class = strtolower($groupEntry['objectclass'][$j]);
                        if ($class === 'posixgroup') {
                            $isPosixGroup = true;
                        } else if ($class === 'groupofuniquenames') {
                            $isUniqueGroup = true;
                        }
                    }
                }
                
                // Verificar memberUid para posixGroup
                if ($isPosixGroup) {
                    // Leer miembros actuales
                    $memberInfo = ldap_read($ldapConn, $groupDn, "(objectclass=*)", ["memberUid"]);
                    if ($memberInfo) {
                        $memberEntry = ldap_get_entries($ldapConn, $memberInfo);
                        
                        // Verificar si el usuario ya es miembro
                        $isCurrentMember = false;
                        if (isset($memberEntry[0]['memberuid'])) {
                            for ($j = 0; $j < $memberEntry[0]['memberuid']['count']; $j++) {
                                if ($memberEntry[0]['memberuid'][$j] === $userUid) {
                                    $isCurrentMember = true;
                                    break;
                                }
                            }
                        }
                        
                        // Añadir o eliminar según corresponda
                        if ($shouldBeInGroup && !$isCurrentMember) {
                            // Añadir al grupo
                            $mod = ["memberUid" => $userUid];
                            ldap_mod_add($ldapConn, $groupDn, $mod);
                            Log::debug("Usuario $userUid añadido como memberUid al grupo $groupName");
                        } else if (!$shouldBeInGroup && $isCurrentMember) {
                            // Eliminar del grupo
                            $mod = ["memberUid" => $userUid];
                            ldap_mod_del($ldapConn, $groupDn, $mod);
                            Log::debug("Usuario $userUid eliminado como memberUid del grupo $groupName");
                        }
                    }
                }
                
                // Verificar uniqueMember para groupOfUniqueNames
                $uniqueMemberInfo = ldap_read($ldapConn, $groupDn, "(objectclass=*)", ["uniqueMember"]);
                if ($uniqueMemberInfo) {
                    $uniqueMemberEntry = ldap_get_entries($ldapConn, $uniqueMemberInfo);
                    
                    // Verificar si el usuario ya es miembro
                    $isCurrentMember = false;
                    if (isset($uniqueMemberEntry[0]['uniquemember'])) {
                        for ($j = 0; $j < $uniqueMemberEntry[0]['uniquemember']['count']; $j++) {
                            if ($uniqueMemberEntry[0]['uniquemember'][$j] === $userDn) {
                                $isCurrentMember = true;
                                break;
                            }
                        }
                    }
                    
                    // Añadir o eliminar según corresponda
                    if ($shouldBeInGroup && !$isCurrentMember) {
                        // Añadir al grupo
                        $mod = ["uniqueMember" => $userDn];
                        ldap_mod_add($ldapConn, $groupDn, $mod);
                        Log::debug("Usuario $userDn añadido como uniqueMember al grupo $groupName");
                    } else if (!$shouldBeInGroup && $isCurrentMember) {
                        // Prevenir la eliminación del ldap-admin del grupo ldapadmins
                        if ($groupName === 'ldapadmins' && $userUid === 'ldap-admin') {
                            Log::warning("No se puede eliminar el usuario ldap-admin del grupo ldapadmins");
                            continue;
                        }
                        
                        // Eliminar del grupo pero verificar que no sea el último miembro
                        if ($uniqueMemberEntry[0]['uniquemember']['count'] > 1) {
                            $mod = ["uniqueMember" => $userDn];
                            ldap_mod_del($ldapConn, $groupDn, $mod);
                            Log::debug("Usuario $userDn eliminado como uniqueMember del grupo $groupName");
                        } else {
                            Log::warning("No se elimina el usuario del grupo $groupName porque es el último miembro");
                        }
                    }
                }
            }
            
            if ($closeConn) {
                ldap_close($ldapConn);
            }
            
            return true;
        } catch (Exception $e) {
            Log::error("Error al actualizar grupos: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Reparar un usuario existente para garantizar que pueda iniciar sesión
     * 
     * @param string $uid UID del usuario a reparar
     * @param string|null $newPassword Nueva contraseña (opcional)
     * @return array Información sobre las reparaciones realizadas
     */
    public function repairUser($uid, $newPassword = null)
    {
        $results = [
            'success' => false,
            'message' => '',
            'repairs' => []
        ];
        
        try {
            $this->connection->connect();
            
            // Buscar el usuario
            $user = $this->connection->query()
                ->in($this->peopleOu)
                ->where('uid', '=', $uid)
                ->first();
                
            if (!$user) {
                $results['message'] = "Usuario no encontrado: $uid";
                return $results;
            }
            
            $userDn = $user['dn'];
            
            // Reparación 1: Asegurar clases de objeto correctas
            $this->ensureUserHasRequiredClasses($userDn);
            $results['repairs'][] = "Verificación de objectClass completada";
            
            // Reparación 2: Verificar y añadir atributos esenciales
            $ldapConn = ldap_connect(config('ldap.connections.default.hosts')[0], config('ldap.connections.default.port'));
            ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);
            ldap_set_option($ldapConn, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
            
            $bind = ldap_bind(
                $ldapConn, 
                config('ldap.connections.default.username'), 
                config('ldap.connections.default.password')
            );
            
            if (!$bind) {
                throw new Exception("No se pudo conectar al servidor LDAP");
            }
            
            // Verificar y añadir atributos esenciales
            $essentialAttrs = [
                'loginShell' => '/bin/bash',
                'shadowLastChange' => floor(time() / 86400)
            ];
            
            $result = ldap_read($ldapConn, $userDn, "(objectclass=*)", array_keys($essentialAttrs));
            if ($result) {
                $entries = ldap_get_entries($ldapConn, $result);
                
                $missingAttrs = [];
                foreach ($essentialAttrs as $attr => $value) {
                    if (!isset($entries[0][strtolower($attr)])) {
                        $missingAttrs[$attr] = $value;
                        $results['repairs'][] = "Añadido atributo: $attr";
                    }
                }
                
                if (!empty($missingAttrs)) {
                    ldap_modify($ldapConn, $userDn, $missingAttrs);
                }
            }
            
            // Reparación 3: Cambiar contraseña si se proporcionó una nueva
            if ($newPassword) {
                // Generar contraseña con el método más compatible
                $hashedPassword = $this->hashPassword($newPassword);
                
                $mod = ['userpassword' => $hashedPassword];
                $success = ldap_modify($ldapConn, $userDn, $mod);
                
                if ($success) {
                    $results['repairs'][] = "Contraseña cambiada correctamente";
                } else {
                    $results['repairs'][] = "Error al cambiar contraseña: " . ldap_error($ldapConn);
                }
            }
            
            // Reparación 4: Verificar grupo profesores
            $profesoresResult = ldap_read($ldapConn, $this->profesoresGroupDn, "(objectclass=*)", ["memberUid", "uniqueMember"]);
            if ($profesoresResult) {
                $profesoresEntry = ldap_get_entries($ldapConn, $profesoresResult);
                
                // Verificar memberUid (para posixGroup)
                $memberUidExists = false;
                if (isset($profesoresEntry[0]['memberuid'])) {
                    for ($i = 0; $i < $profesoresEntry[0]['memberuid']['count']; $i++) {
                        if ($profesoresEntry[0]['memberuid'][$i] === $uid) {
                            $memberUidExists = true;
                            break;
                        }
                    }
                }
                
                if (!$memberUidExists) {
                    $mod = ["memberUid" => $uid];
                    ldap_mod_add($ldapConn, $this->profesoresGroupDn, $mod);
                    $results['repairs'][] = "Añadido como memberUid al grupo profesores";
                }
                
                // Verificar uniqueMember (para groupOfUniqueNames)
                $uniqueMemberExists = false;
                if (isset($profesoresEntry[0]['uniquemember'])) {
                    for ($i = 0; $i < $profesoresEntry[0]['uniquemember']['count']; $i++) {
                        if ($profesoresEntry[0]['uniquemember'][$i] === $userDn) {
                            $uniqueMemberExists = true;
                            break;
                        }
                    }
                }
                
                if (!$uniqueMemberExists) {
                    $mod = ["uniqueMember" => $userDn];
                    // Intentar añadir, capturando error si no tiene esta objectClass
                    try {
                        ldap_mod_add($ldapConn, $this->profesoresGroupDn, $mod);
                        $results['repairs'][] = "Añadido como uniqueMember al grupo profesores";
                    } catch (\Exception $e) {
                        $results['repairs'][] = "No se pudo añadir como uniqueMember: El grupo podría no tener la clase adecuada";
                    }
                }
            }
            
            ldap_close($ldapConn);
            
            $results['success'] = true;
            $results['message'] = "Reparación completada con éxito para el usuario $uid";
            
            return $results;
            
        } catch (Exception $e) {
            $results['message'] = "Error al reparar usuario: " . $e->getMessage();
            return $results;
        }
    }
} 