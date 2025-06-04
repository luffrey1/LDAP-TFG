<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use LdapRecord\Connection;
use Illuminate\Support\Facades\Log;
use Exception;

class LdapGroupController extends Controller
{
    public function index()
    {
        try {
            Log::debug('Iniciando búsqueda de grupos LDAP');
            
            $config = config('ldap.connections.default');
            Log::debug('Configuración LDAP: ' . json_encode($config));
            
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

            Log::debug('Intentando conectar al servidor LDAP');
            $connection->connect();
            Log::debug('Conexión LDAP establecida');

            $query = $connection->query()->in('ou=groups,dc=tierno,dc=es');
            Log::debug('Query LDAP creada: ' . json_encode($query));
            
            $groups = $query->get();
            Log::debug('Grupos encontrados (raw): ' . json_encode($groups));

            if (empty($groups)) {
                Log::warning('No se encontraron grupos LDAP');
                return view('admin.groups.index', ['groups' => []]);
            }

            $groupData = [];
            foreach ($groups as $group) {
                $groupData[] = [
                    'dn' => is_array($group) ? $group['dn'] : $group->getDn(),
                    'cn' => is_array($group) ? ($group['cn'][0] ?? '') : $group->getFirstAttribute('cn'),
                    'description' => is_array($group) ? ($group['description'][0] ?? '') : $group->getFirstAttribute('description'),
                    'gidNumber' => is_array($group) ? ($group['gidnumber'][0] ?? '') : $group->getFirstAttribute('gidnumber'),
                    'memberCount' => is_array($group) ? (isset($group['member']) ? count($group['member']) : 0) : count($group->getAttribute('member', [])),
                ];
            }

            Log::debug('Datos de grupos procesados:', ['groups' => $groupData]);

            return view('admin.groups.index', ['groups' => $groupData]);

        } catch (\Exception $e) {
            Log::error('Error al obtener grupos LDAP: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return view('admin.groups.index', [
                'groups' => [],
                'error' => true,
                'errorMessage' => 'Error al obtener los grupos LDAP: ' . $e->getMessage(),
                'diagnostico' => [
                    'error' => $e->getMessage(),
                    'hosts' => config('ldap.connections.default.hosts'),
                    'port' => 636,
                    'base_dn' => config('ldap.connections.default.base_dn'),
                    'username' => config('ldap.connections.default.username'),
                    'use_ssl' => true,
                    'use_tls' => false
                ]
            ]);
        }
    }

    public function create()
    {
        return view('admin.groups.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'cn' => 'required|string|max:255|regex:/^[a-zA-Z0-9_-]+$/',
            'gidNumber' => 'required|integer|min:1000',
            'description' => 'nullable|string|max:255',
        ], [
            'cn.regex' => 'El nombre del grupo solo puede contener letras, números, guiones y guiones bajos',
            'gidNumber.min' => 'El GID debe ser mayor o igual a 1000',
        ]);

        try {
            Log::debug('Iniciando creación de grupo LDAP');
            Log::debug('Datos del grupo: ' . json_encode($request->all()));

            $config = config('ldap.connections.default');
            Log::debug('Configuración LDAP: ' . json_encode($config));

            // Crear el grupo directamente con LDAP nativo para mayor control
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
            
            Log::debug("Intentando bind con credenciales LDAP...");
            Log::debug("Username: " . $config['username']);
            
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

            // Verificar si el grupo ya existe
            $filter = "(cn={$request->cn})";
            $search = ldap_search($ldapConn, "ou=groups,dc=tierno,dc=es", $filter);
            
            if (!$search) {
                throw new Exception("Error al buscar grupo: " . ldap_error($ldapConn));
            }
            
            $entries = ldap_get_entries($ldapConn, $search);
            if ($entries['count'] > 0) {
                ldap_close($ldapConn);
                Log::warning('El grupo ya existe: ' . $request->cn);
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Ya existe un grupo con ese nombre');
            }

            // Verificar si el GID ya está en uso
            $gidFilter = "(gidNumber={$request->gidNumber})";
            $gidSearch = ldap_search($ldapConn, "ou=groups,dc=tierno,dc=es", $gidFilter);
            
            if ($gidSearch) {
                $gidEntries = ldap_get_entries($ldapConn, $gidSearch);
                if ($gidEntries['count'] > 0) {
                    ldap_close($ldapConn);
                    Log::warning('El GID ya está en uso: ' . $request->gidNumber);
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'El GID especificado ya está en uso');
                }
            }

            $dn = "cn={$request->cn},ou=groups,dc=tierno,dc=es";
            Log::debug('DN del nuevo grupo: ' . $dn);

            $groupCreated = false;
            $errorMessage = '';

            // Intento 1: Crear como posixGroup
            try {
                Log::debug('Intentando crear grupo como posixGroup');
                
                $entry = [
                    'objectclass' => ['top', 'posixGroup'],
                    'cn' => $request->cn,
                    'gidNumber' => $request->gidNumber
                ];
                
                // Añadir descripción si se proporcionó
                if ($request->description) {
                    $entry['description'] = $request->description;
                }
                
                $success = ldap_add($ldapConn, $dn, $entry);
                
                if (!$success) {
                    throw new Exception(ldap_error($ldapConn));
                }
                
                $groupCreated = true;
                Log::info('Grupo creado exitosamente como posixGroup: ' . $request->cn);
            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
                Log::debug('Fallo la creacion como posixGroup: ' . $errorMessage);

                // Si falla por Object class violation, intentamos con groupOfUniqueNames
                if (strpos($errorMessage, 'Object class violation') !== false) {
                    Log::debug('Intentando crear grupo como groupOfUniqueNames');
                    try {
                        $entry = [
                            'objectclass' => ['top', 'groupOfUniqueNames'],
                            'cn' => $request->cn,
                            'uniqueMember' => 'cn=nobody,dc=tierno,dc=es'
                        ];
                        
                        // Añadir descripción si se proporcionó
                        if ($request->description) {
                            $entry['description'] = $request->description;
                        }
                        
                        $success = ldap_add($ldapConn, $dn, $entry);
                        
                        if (!$success) {
                            throw new Exception(ldap_error($ldapConn));
                        }
                        
                        $groupCreated = true;
                        Log::info('Grupo creado exitosamente como groupOfUniqueNames: ' . $request->cn);
                    } catch (\Exception $e2) {
                        $errorMessage = "Fallo la creacion como posixGroup ('{$errorMessage}') y como groupOfUniqueNames ('{$e2->getMessage()}')";
                        Log::error($errorMessage);
                    }
                } else {
                    // Otro tipo de error, no es Object class violation con posixGroup
                    Log::error('Error al crear grupo LDAP (posixGroup falló por otra razón): ' . $errorMessage);
                }
            }

            ldap_close($ldapConn);

            if ($groupCreated) {
                Log::channel('activity')->info('Grupo LDAP creado', [
                    'action' => 'Crear Grupo',
                    'group' => $request->cn
                ]);
                
                return redirect()->route('admin.groups.index')->with('success', 'Grupo creado correctamente');
            } else {
                throw new Exception($errorMessage); // Lanzar el error si ninguno tuvo éxito
            }

        } catch (\Exception $e) {
            Log::channel('activity')->error('Error al crear grupo LDAP: ' . $e->getMessage(), [
                'action' => 'Error',
                'group' => $request->cn ?? 'desconocido'
            ]);
            return back()->with('error', 'Error al crear grupo: ' . $e->getMessage());
        }
    }

    public function edit($cn)
    {
        try {
            $config = config('ldap.connections.default');
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

            $connection->connect();

            $group = $connection->query()
                ->in('ou=groups,dc=tierno,dc=es')
                ->where('cn', '=', $cn)
                ->first();

            if (!$group) {
                return redirect()->route('admin.groups.index')
                    ->with('error', 'Grupo no encontrado');
            }

            $groupData = [
                'cn' => is_array($group['cn']) ? $group['cn'][0] : $group['cn'],
                'gidNumber' => is_array($group['gidnumber']) ? $group['gidnumber'][0] : $group['gidnumber'],
                'description' => isset($group['description']) ? (is_array($group['description']) ? $group['description'][0] : $group['description']) : '',
            ];

            return view('admin.groups.edit', ['groupData' => $groupData]);
        } catch (\Exception $e) {
            Log::error('Error al obtener grupo LDAP: ' . $e->getMessage());
            return redirect()->route('admin.groups.index')
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

            $connection->connect();

            $dn = "cn={$cn},ou=groups,dc=tierno,dc=es";
            
            $entry = [
                'gidNumber' => $request->gidNumber,
            ];

            if ($request->description) {
                $entry['description'] = $request->description;
            }

            $connection->query()->update($dn, $entry);

            Log::channel('activity')->info('Grupo LDAP actualizado', [
                'action' => 'Actualizar Grupo',
                'group' => $cn
            ]);
            
            return redirect()->route('admin.groups.index')->with('success', 'Grupo actualizado correctamente');
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

            $connection->connect();

            // Verificar si el grupo existe
            $group = $connection->query()
                ->in('ou=groups,dc=tierno,dc=es')
                ->where('cn', '=', $cn)
                ->first();

            if (!$group) {
                return redirect()->route('admin.groups.index')
                    ->with('error', 'Grupo no encontrado');
            }

            // Verificar si el grupo está protegido
            $protectedGroups = ['admin', 'ldapadmins', 'sudo'];
            if (in_array($cn, $protectedGroups)) {
                return redirect()->route('admin.groups.index')
                    ->with('error', 'No se puede eliminar un grupo protegido');
            }

            $dn = "cn={$cn},ou=groups,dc=tierno,dc=es";
            $connection->query()->delete($dn);

            Log::channel('activity')->info('Grupo LDAP eliminado', [
                'action' => 'Eliminar Grupo',
                'group' => $cn
            ]);
            
            return redirect()->route('admin.groups.index')->with('success', 'Grupo eliminado correctamente');
        } catch (\Exception $e) {
            Log::channel('activity')->error('Error al eliminar grupo LDAP: ' . $e->getMessage(), [
                'action' => 'Error',
                'group' => $cn
            ]);
            return back()->with('error', 'Error al eliminar grupo: ' . $e->getMessage());
        }
    }
} 