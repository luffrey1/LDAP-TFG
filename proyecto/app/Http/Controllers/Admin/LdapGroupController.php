<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use LdapRecord\Connection;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Foundation\Validation\ValidatesRequests;

class LdapGroupController extends Controller
{
    use ValidatesRequests;
    
    protected $connection;
    protected $baseDn = 'dc=tierno,dc=es';
    protected $groupsOu = 'ou=groups,dc=tierno,dc=es';
    
    public function __construct()
    {
        $config = config('ldap.connections.default');
        $this->baseDn = $config['base_dn'];
        $this->groupsOu = "ou=groups,{$this->baseDn}";
        
        Log::debug("Configurando conexión LDAP con los siguientes parámetros: " . json_encode([
            'hosts' => $config['hosts'],
            'port' => 636,
            'base_dn' => $config['base_dn'],
            'username' => $config['username'],
            'use_ssl' => true,
            'use_tls' => false,
            'timeout' => $config['timeout']
        ]));

        $maxRetries = 3;
        $retryCount = 0;
        $lastError = null;

        while ($retryCount < $maxRetries) {
            try {
                $this->connection = new Connection([
                    'hosts' => $config['hosts'],
                    'port' => 636,
                    'base_dn' => $config['base_dn'],
                    'username' => $config['username'],
                    'password' => $config['password'],
                    'use_ssl' => true,
                    'use_tls' => false,
                    'timeout' => $config['timeout']
                ]);

                // Forzar la conexión SSL
                $this->connection->getLdapConnection()->setOption(LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
                
                // Intentar conectar
                $this->connection->connect();
                
                // Verificar la conexión con una búsqueda simple
                $search = $this->connection->query()->where('objectclass', '*')->limit(1)->get();
                
                if (empty($search)) {
                    throw new Exception("No se pudo realizar la búsqueda de prueba en LDAP");
                }
                
                Log::debug("Conexión LDAP establecida correctamente");
                Log::debug("Búsqueda de prueba exitosa");
                return; // Si llegamos aquí, la conexión fue exitosa
                
            } catch (Exception $e) {
                $lastError = $e;
                $retryCount++;
                Log::warning("Intento {$retryCount} de {$maxRetries} fallido al conectar con LDAP: " . $e->getMessage());
                
                if ($retryCount < $maxRetries) {
                    // Esperar un poco antes de reintentar (1 segundo por intento)
                    sleep(1);
                }
            }
        }

        // Si llegamos aquí, todos los intentos fallaron
        Log::error("Error al conectar con LDAP después de {$maxRetries} intentos: " . $lastError->getMessage());
        throw $lastError;
    }

    public function index(Request $request)
    {
        try {
            Log::debug('Iniciando búsqueda de grupos LDAP');
            
            // Obtener la conexión LDAP nativa
            $ldapConn = $this->connection->getLdapConnection()->getConnection();
            
            // Construir el filtro base
            $filter = '(objectClass=*)';
            
            // Aplicar filtro por tipo si se especifica
            if ($request->has('type') && $request->type !== 'all') {
                switch ($request->type) {
                    case 'posix':
                        $filter = '(objectClass=posixGroup)';
                        break;
                    case 'unique':
                        $filter = '(objectClass=groupOfUniqueNames)';
                        break;
                    case 'combined':
                        $filter = '(&(objectClass=posixGroup)(objectClass=groupOfUniqueNames))';
                        break;
                }
            }
            
            // Aplicar búsqueda por nombre si se especifica
            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = $request->search;
                $filter = "(&{$filter}(cn=*{$searchTerm}*))";
            }
            
            // Realizar la búsqueda de grupos
            $search = ldap_search(
                $ldapConn,
                $this->groupsOu,
                $filter,
                ['cn', 'gidNumber', 'description', 'memberUid', 'member', 'uniqueMember', 'objectClass']
            );
            
            if (!$search) {
                throw new Exception("Error al buscar grupos: " . ldap_error($ldapConn));
            }
            
            $entries = ldap_get_entries($ldapConn, $search);
            
            if (!$entries) {
                throw new Exception("Error al obtener resultados: " . ldap_error($ldapConn));
            }
            
            $groups = [];
            for ($i = 0; $i < $entries['count']; $i++) {
                $entry = $entries[$i];
                $group = [
                    'dn' => $entry['dn'],
                    'cn' => $entry['cn'][0] ?? '',
                    'gidNumber' => $entry['gidnumber'][0] ?? '',
                    'description' => $entry['description'][0] ?? '',
                    'type' => $this->determineGroupType($entry),
                    'members' => []
                ];
                
                // Procesar miembros según el tipo de grupo
                if (isset($entry['memberuid'])) {
                    for ($j = 0; $j < $entry['memberuid']['count']; $j++) {
                        $group['members'][] = $entry['memberuid'][$j];
                    }
                }
                
                $groups[] = $group;
            }
            
            // Si es una petición AJAX, devolver JSON
            if ($request->ajax()) {
                return response()->json(['groups' => $groups]);
            }
            
            return view('gestion.grupos.index', compact('groups'));
            
        } catch (Exception $e) {
            Log::error('Error al obtener grupos LDAP: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return back()->with('error', 'Error al obtener grupos: ' . $e->getMessage());
        }
    }

    protected function determineGroupType($entry)
    {
        if (isset($entry['objectclass'])) {
            $classes = array_map('strtolower', $entry['objectclass']);
            if (in_array('posixgroup', $classes) && in_array('groupofuniquenames', $classes)) {
                return 'combined';
            } elseif (in_array('posixgroup', $classes)) {
                return 'posix';
            } elseif (in_array('groupofuniquenames', $classes)) {
                return 'unique';
            }
        }
        return 'unknown';
    }

    public function create()
    {
        return view('gestion.grupos.create');
    }

    public function store(Request $request)
    {
        Log::debug('Iniciando creación de grupo LDAP');
        Log::debug('Datos del grupo: ' . json_encode($request->all()));

        try {
            $this->validate($request, [
                'type' => 'required|string',
                'cn' => 'required|string|max:255',
                'gidNumber' => 'required_if:type,posix|nullable|numeric',
                'description' => 'nullable|string|max:255'
            ]);

            // Obtener la conexión LDAP nativa
            $ldapConn = $this->connection->getLdapConnection()->getConnection();
            Log::debug('Conexión LDAP establecida correctamente');

            $groupDn = "cn={$request->cn},{$this->groupsOu}";
            Log::debug('Intentando crear grupo con DN: ' . $groupDn);

            // Verificar si el grupo ya existe
            $search = ldap_search($ldapConn, $this->groupsOu, "(cn={$request->cn})");
            if ($search && ldap_count_entries($ldapConn, $search) > 0) {
                Log::warning('El grupo ya existe: ' . $groupDn);
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe un grupo con ese nombre.'
                ], 422);
            }

            $attributes = [
                'objectclass' => ['top'],
                'cn' => $request->cn
            ];

            if ($request->description) {
                $attributes['description'] = $request->description;
            }

            // Procesar los tipos de grupo
            $types = explode(',', $request->type);
            
            if (in_array('posix', $types)) {
                $attributes['objectclass'] = ['top', 'posixGroup', 'groupOfNames'];
                $attributes['gidNumber'] = (string)$request->gidNumber;
                $attributes['memberUid'] = ['profesor'];
                $attributes['member'] = ['cn=nobody'];
            }
            
            if (in_array('unique', $types)) {
                $attributes['objectclass'] = ['top', 'posixGroup', 'groupOfUniqueNames'];
                $attributes['uniqueMember'] = ['uid=profesor,ou=people,dc=tierno,dc=es'];
            }

            Log::debug('Atributos del grupo: ' . json_encode($attributes));

            if (!ldap_add($ldapConn, $groupDn, $attributes)) {
                $error = ldap_error($ldapConn);
                Log::error('Error al crear grupo: ' . $error);
                throw new \Exception('Error al crear grupo: ' . $error);
            }

            Log::info('Grupo creado exitosamente: ' . $groupDn);
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Grupo creado exitosamente'
                ]);
            }
            
            return redirect()->route('gestion.grupos.index')->with('success', 'Grupo creado exitosamente');

        } catch (\Exception $e) {
            Log::error('Error al crear grupo: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al crear grupo: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Error al crear grupo: ' . $e->getMessage())->withInput();
        }
    }

    public function edit($cn)
    {
        try {
            $config = config('ldap.connections.default');
            
            // Crear conexión LDAP nativa con SSL
            $ldapConn = ldap_connect('ldaps://' . $config['hosts'][0], 636);
            if (!$ldapConn) {
                Log::error("Error al crear conexión LDAP");
                throw new Exception("No se pudo establecer la conexión LDAP");
            }
            
            Log::debug("Conexión LDAP creada, configurando opciones...");
            
            ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);
            ldap_set_option($ldapConn, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
            
            // Configurar SSL
            Log::debug("Configurando SSL para conexión LDAP...");
            ldap_set_option($ldapConn, LDAP_OPT_X_TLS_CACERTFILE, '/etc/ssl/certs/ldap/ca.crt');
            ldap_set_option($ldapConn, LDAP_OPT_X_TLS_CERTFILE, '/etc/ssl/certs/ldap/cert.pem');
            ldap_set_option($ldapConn, LDAP_OPT_X_TLS_KEYFILE, '/etc/ssl/certs/ldap/privkey.pem');
            
            // Intentar bind con credenciales
            $bind = @ldap_bind(
                $ldapConn, 
                $config['username'], 
                $config['password']
            );
            
            if (!$bind) {
                $error = ldap_error($ldapConn);
                Log::error("Error al conectar al servidor LDAP: " . $error);
                Log::error("Código de error LDAP: " . ldap_errno($ldapConn));
                throw new Exception("No se pudo conectar al servidor LDAP: " . $error);
            }
            
            Log::debug("Conexión LDAP establecida correctamente");

            // Buscar el grupo
            $filter = "(cn=$cn)";
            $search = ldap_search($ldapConn, "ou=groups,dc=tierno,dc=es", $filter);
            
            if (!$search) {
                ldap_close($ldapConn);
                throw new Exception("Error al buscar grupo: " . ldap_error($ldapConn));
            }
            
            $entries = ldap_get_entries($ldapConn, $search);
            if ($entries['count'] == 0) {
                ldap_close($ldapConn);
                return redirect()->route('gestion.grupos.index')
                    ->with('error', 'Grupo no encontrado');
            }
            
            // Obtener los datos del grupo
            $group = $entries[0];
            
            $groupData = [
                'cn' => $group['cn'][0] ?? $cn,
                'gidNumber' => isset($group['gidnumber']) ? $group['gidnumber'][0] : '',
                'description' => isset($group['description']) ? $group['description'][0] : '',
            ];
            
            ldap_close($ldapConn);

            return view('gestion.grupos.edit', ['groupData' => $groupData]);
        } catch (\Exception $e) {
            Log::error('Error al obtener grupo LDAP: ' . $e->getMessage());
            return redirect()->route('gestion.grupos.index')
                ->with('error', 'Error al obtener el grupo: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $cn)
    {
        $request->validate([
            'gidNumber' => 'required|integer',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            $config = config('ldap.connections.default');
            
            // Crear conexión LDAP nativa con SSL
            $ldapConn = ldap_connect('ldaps://' . $config['hosts'][0], 636);
            if (!$ldapConn) {
                Log::error("Error al crear conexión LDAP");
                throw new Exception("No se pudo establecer la conexión LDAP");
            }
            
            Log::debug("Conexión LDAP creada, configurando opciones...");
            
            ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);
            ldap_set_option($ldapConn, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
            
            // Configurar SSL
            Log::debug("Configurando SSL para conexión LDAP...");
            ldap_set_option($ldapConn, LDAP_OPT_X_TLS_CACERTFILE, '/etc/ssl/certs/ldap/ca.crt');
            ldap_set_option($ldapConn, LDAP_OPT_X_TLS_CERTFILE, '/etc/ssl/certs/ldap/cert.pem');
            ldap_set_option($ldapConn, LDAP_OPT_X_TLS_KEYFILE, '/etc/ssl/certs/ldap/privkey.pem');
            
            // Intentar bind con credenciales
            $bind = @ldap_bind(
                $ldapConn, 
                $config['username'], 
                $config['password']
            );
            
            if (!$bind) {
                $error = ldap_error($ldapConn);
                Log::error("Error al conectar al servidor LDAP: " . $error);
                Log::error("Código de error LDAP: " . ldap_errno($ldapConn));
                throw new Exception("No se pudo conectar al servidor LDAP: " . $error);
            }
            
            Log::debug("Conexión LDAP establecida correctamente");

            // Verificar que el grupo existe
            $dn = "cn={$cn},ou=groups,dc=tierno,dc=es";
            $filter = "(objectClass=*)";
            $search = ldap_read($ldapConn, $dn, $filter);
            
            if (!$search) {
                ldap_close($ldapConn);
                throw new Exception("Grupo no encontrado: " . ldap_error($ldapConn));
            }
            
            // Preparar los atributos a modificar
            $entry = [];
            
            // Verificar si el grupo es posixGroup para actualizar gidNumber
            $entries = ldap_get_entries($ldapConn, $search);
            $isPosixGroup = false;
            
            if ($entries['count'] > 0) {
                if (isset($entries[0]['objectclass'])) {
                    for ($i = 0; $i < $entries[0]['objectclass']['count']; $i++) {
                        if (strtolower($entries[0]['objectclass'][$i]) === 'posixgroup') {
                            $isPosixGroup = true;
                            break;
                        }
                    }
                }
            }
            
            // Solo actualizar gidNumber si es posixGroup
            if ($isPosixGroup) {
                $entry['gidnumber'] = $request->gidNumber;
            }
            
            // La descripción se puede actualizar para cualquier tipo de grupo
            if ($request->filled('description')) {
                $entry['description'] = $request->description;
            } else {
                // Si se envió una descripción vacía, eliminarla
                $modOperation = [];
                $modOperation['description'] = [];
                ldap_mod_del($ldapConn, $dn, $modOperation);
            }
            
            // Actualizar el grupo si hay atributos para modificar
            if (!empty($entry)) {
                $result = ldap_modify($ldapConn, $dn, $entry);
                
                if (!$result) {
                    ldap_close($ldapConn);
                    throw new Exception("Error al actualizar grupo: " . ldap_error($ldapConn));
                }
            }
            
            ldap_close($ldapConn);

            Log::channel('activity')->info('Grupo LDAP actualizado', [
                'action' => 'Actualizar Grupo',
                'group' => $cn
            ]);
            
            return redirect()->route('gestion.grupos.index')->with('success', 'Grupo actualizado correctamente');
        } catch (\Exception $e) {
            Log::channel('activity')->error('Error al actualizar grupo LDAP: ' . $e->getMessage(), [
                'action' => 'Error',
                'group' => $cn
            ]);
            return back()->with('error', 'Error al actualizar grupo: ' . $e->getMessage());
        }
    }

    public function destroy($cn)
    {
        try {
            $config = config('ldap.connections.default');
            
            // Crear conexión LDAP nativa con SSL
            $ldapConn = ldap_connect('ldaps://' . $config['hosts'][0], 636);
            if (!$ldapConn) {
                Log::error("Error al crear conexión LDAP");
                throw new Exception("No se pudo establecer la conexión LDAP");
            }
            
            Log::debug("Conexión LDAP creada, configurando opciones...");
            
            ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);
            ldap_set_option($ldapConn, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
            
            // Configurar SSL
            Log::debug("Configurando SSL para conexión LDAP...");
            ldap_set_option($ldapConn, LDAP_OPT_X_TLS_CACERTFILE, '/etc/ssl/certs/ldap/ca.crt');
            ldap_set_option($ldapConn, LDAP_OPT_X_TLS_CERTFILE, '/etc/ssl/certs/ldap/cert.pem');
            ldap_set_option($ldapConn, LDAP_OPT_X_TLS_KEYFILE, '/etc/ssl/certs/ldap/privkey.pem');
            
            // Intentar bind con credenciales
            $bind = @ldap_bind(
                $ldapConn, 
                $config['username'], 
                $config['password']
            );
            
            if (!$bind) {
                $error = ldap_error($ldapConn);
                Log::error("Error al conectar al servidor LDAP: " . $error);
                Log::error("Código de error LDAP: " . ldap_errno($ldapConn));
                throw new Exception("No se pudo conectar al servidor LDAP: " . $error);
            }
            
            Log::debug("Conexión LDAP establecida correctamente");

            // Verificar si el grupo existe
            $filter = "(cn=$cn)";
            $search = ldap_search($ldapConn, "ou=groups,dc=tierno,dc=es", $filter);
            
            if (!$search) {
                ldap_close($ldapConn);
                throw new Exception("Error al buscar grupo: " . ldap_error($ldapConn));
            }
            
            $entries = ldap_get_entries($ldapConn, $search);
            if ($entries['count'] == 0) {
                ldap_close($ldapConn);
                return redirect()->route('gestion.grupos.index')
                    ->with('error', 'Grupo no encontrado');
            }

            // Verificar si el grupo está protegido
            $protectedGroups = ['admin', 'ldapadmins', 'sudo', 'profesores', 'alumnos'];
            if (in_array($cn, $protectedGroups)) {
                ldap_close($ldapConn);
                return redirect()->route('gestion.grupos.index')
                    ->with('error', 'No se puede eliminar un grupo protegido');
            }

            // Eliminar el grupo
            $dn = "cn={$cn},ou=groups,dc=tierno,dc=es";
            $result = ldap_delete($ldapConn, $dn);
            
            if (!$result) {
                ldap_close($ldapConn);
                throw new Exception("Error al eliminar grupo: " . ldap_error($ldapConn));
            }
            
            ldap_close($ldapConn);

            Log::channel('activity')->info('Grupo LDAP eliminado', [
                'action' => 'Eliminar Grupo',
                'group' => $cn
            ]);
            
            return redirect()->route('gestion.grupos.index')->with('success', 'Grupo eliminado correctamente');
        } catch (\Exception $e) {
            Log::channel('activity')->error('Error al eliminar grupo LDAP: ' . $e->getMessage(), [
                'action' => 'Error',
                'group' => $cn
            ]);
            return back()->with('error', 'Error al eliminar grupo: ' . $e->getMessage());
        }
    }

    protected function addUserToGroup($userDn, $groupDn)
    {
        try {
            // Obtener la conexión LDAP nativa
            $ldapConn = $this->connection->getLdapConnection()->getConnection();
            
            // Obtener información del grupo
            $search = ldap_read($ldapConn, $groupDn, '(objectClass=*)', ['objectClass']);
            if (!$search) {
                throw new Exception("Error al leer grupo: " . ldap_error($ldapConn));
            }
            
            $entries = ldap_get_entries($ldapConn, $search);
            if ($entries['count'] == 0) {
                throw new Exception("Grupo no encontrado");
            }
            
            $group = $entries[0];
            $objectClasses = array_map('strtolower', $group['objectclass']);
            
            // Obtener el UID del usuario
            $userSearch = ldap_read($ldapConn, $userDn, '(objectClass=*)', ['uid']);
            if (!$userSearch) {
                throw new Exception("Error al leer usuario: " . ldap_error($ldapConn));
            }
            
            $userEntries = ldap_get_entries($ldapConn, $userSearch);
            if ($userEntries['count'] == 0) {
                throw new Exception("Usuario no encontrado");
            }
            
            $userUid = $userEntries[0]['uid'][0];
            
            // Modificar el grupo según su tipo
            $modifications = [];
            
            if (in_array('posixgroup', $objectClasses)) {
                $modifications['memberUid'] = $userUid;
            }
            
            if (in_array('groupofuniquenames', $objectClasses)) {
                $modifications['uniqueMember'] = $userDn;
            }
            
            if (!empty($modifications)) {
                $result = ldap_mod_add($ldapConn, $groupDn, $modifications);
                if (!$result) {
                    throw new Exception("Error al añadir usuario al grupo: " . ldap_error($ldapConn));
                }
            }
            
            Log::info("Usuario añadido al grupo exitosamente", [
                'user' => $userDn,
                'group' => $groupDn
            ]);
            
            return true;
            
        } catch (Exception $e) {
            Log::error("Error al añadir usuario al grupo: " . $e->getMessage());
            throw $e;
        }
    }

    public function show($cn)
    {
        try {
            // Obtener la conexión LDAP nativa
            $ldapConn = $this->connection->getLdapConnection()->getConnection();
            
            // Buscar el grupo
            $filter = "(cn=$cn)";
            $search = ldap_search($ldapConn, $this->groupsOu, $filter, ['*']);
            
            if (!$search) {
                throw new Exception("Error al buscar grupo: " . ldap_error($ldapConn));
            }
            
            $entries = ldap_get_entries($ldapConn, $search);
            if ($entries['count'] == 0) {
                return redirect()->route('gestion.grupos.index')
                    ->with('error', 'Grupo no encontrado');
            }
            
            // Obtener los datos del grupo
            $entry = $entries[0];
            $group = [
                'cn' => $entry['cn'][0] ?? $cn,
                'gidNumber' => isset($entry['gidnumber']) ? $entry['gidnumber'][0] : '',
                'description' => isset($entry['description']) ? $entry['description'][0] : '',
                'type' => $this->determineGroupType($entry),
                'members' => []
            ];
            
            // Procesar miembros según el tipo de grupo
            if (isset($entry['memberuid'])) {
                for ($i = 0; $i < $entry['memberuid']['count']; $i++) {
                    $uid = $entry['memberuid'][$i];
                    // Buscar información adicional del usuario
                    $userSearch = ldap_search($ldapConn, "ou=people,{$this->baseDn}", "(uid=$uid)", ['uid', 'uidNumber', 'cn', 'givenname', 'sn']);
                    if ($userSearch) {
                        $userEntries = ldap_get_entries($ldapConn, $userSearch);
                        if ($userEntries['count'] > 0) {
                            $userEntry = $userEntries[0];
                            $group['members'][] = [
                                'uid' => $uid,
                                'uidNumber' => $userEntry['uidnumber'][0] ?? '',
                                'cn' => $userEntry['cn'][0] ?? '',
                                'givenname' => $userEntry['givenname'][0] ?? '',
                                'sn' => $userEntry['sn'][0] ?? ''
                            ];
                        } else {
                            $group['members'][] = ['uid' => $uid];
                        }
                    } else {
                        $group['members'][] = ['uid' => $uid];
                    }
                }
            }
            
            return view('gestion.grupos.show', compact('group'));
            
        } catch (Exception $e) {
            Log::error('Error al obtener detalles del grupo: ' . $e->getMessage());
            return redirect()->route('gestion.grupos.index')
                ->with('error', 'Error al obtener detalles del grupo: ' . $e->getMessage());
        }
    }
} 