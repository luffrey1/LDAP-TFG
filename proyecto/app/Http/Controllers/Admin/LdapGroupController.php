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
                // Verificar si el objeto es un grupo y tiene los atributos básicos necesarios
                if (!isset($group['cn']) || !isset($group['objectclass'])) {
                    Log::debug('Objeto omitido por falta de atributos básicos: ' . json_encode($group));
                    continue; // Saltar objetos que no parecen grupos (como la OU)
                }
                
                Log::debug('Procesando posible grupo: ' . json_encode($group));
                
                try {
                    $gidNumber = null; // Inicializar la variable
                    $gidNumber = isset($group['gidnumber']) ? (is_array($group['gidnumber']) ? $group['gidnumber'][0] : $group['gidnumber']) : null;
                    $groupData[] = [
                        'cn' => is_array($group['cn']) ? $group['cn'][0] : $group['cn'],
                        'gidNumber' => $gidNumber,
                        'description' => isset($group['description']) ? (is_array($group['description']) ? $group['description'][0] : $group['description']) : '',
                        'member' => $group['member'] ?? [],
                    ];
                } catch (\Exception $e) {
                    Log::error('Error procesando grupo: ' . $e->getMessage());
                    Log::error('Datos del grupo: ' . json_encode($group));
                }
            }

            Log::debug('Grupos procesados: ' . json_encode($groupData));
            Log::debug('Renderizando vista con ' . count($groupData) . ' grupos');

            return view('admin.groups.index', ['groups' => $groupData]);
        } catch (\Exception $e) {
            Log::error('Error al obtener grupos LDAP: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return redirect()->back()->with('error', 'Error al obtener los grupos LDAP: ' . $e->getMessage());
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

            Log::debug('Conectando al servidor LDAP');
            $connection->connect();
            Log::debug('Conexión LDAP establecida');

            // Verificar si el grupo ya existe
            $existingGroup = $connection->query()
                ->in('ou=groups,dc=tierno,dc=es')
                ->where('cn', '=', $request->cn)
                ->first();

            if ($existingGroup) {
                Log::warning('El grupo ya existe: ' . $request->cn);
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Ya existe un grupo con ese nombre');
            }

            // Verificar si el GID ya está en uso (solo relevante para posixGroup)
            // No verificamos GID si vamos a intentar groupOfUniqueNames
            $existingGid = null;
            if (true) { // Siempre intentamos posixGroup primero, así que verificamos GID
                $existingGid = $connection->query()
                    ->in('ou=groups,dc=tierno,dc=es')
                    ->where('gidnumber', '=', $request->gidNumber)
                    ->first();

                if ($existingGid) {
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

            // Intento 1: Crear como posixGroup (mínimos atributos)
            try {
                Log::debug('Intentando crear grupo como posixGroup');
                $group = new \LdapRecord\Models\OpenLDAP\Group();
                $group->setDn($dn);
                $group->setAttribute('objectClass', ['top', 'posixGroup']);
                $group->setAttribute('cn', $request->cn);
                $group->setAttribute('gidNumber', (string)$request->gidNumber);
                // Añadir descripción en la misma operación de creación
                if ($request->description) {
                    $group->setAttribute('description', $request->description);
                }
                $group->save();
                $groupCreated = true;
                Log::info('Grupo creado exitosamente como posixGroup: ' . $request->cn);
            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
                Log::debug('Fallo la creacion como posixGroup: ' . $errorMessage);

                // Si falla por Object class violation, intentamos con groupOfUniqueNames
                if (strpos($errorMessage, 'Object class violation') !== false) {
                    Log::debug('Intentando crear grupo como groupOfUniqueNames');
                    // Intento 2: Crear como groupOfUniqueNames (requiere uniqueMember)
                    try {
                        $group = new \LdapRecord\Models\OpenLDAP\Group();
                        $group->setDn($dn);
                        $group->setAttribute('objectClass', ['top', 'groupOfUniqueNames']);
                        $group->setAttribute('cn', $request->cn);
                        // groupOfUniqueNames requiere al menos un uniqueMember
                        $dummyMemberDn = 'cn=nobody,dc=tierno,dc=es';
                        $group->setAttribute('uniqueMember', [$dummyMemberDn]);
                        // Añadir description si está presente (gidNumber no se añade aquí porque no está permitido en groupOfUniqueNames)
                        if ($request->description) {
                            $group->setAttribute('description', $request->description);
                        }

                        $group->save();
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

            if ($groupCreated) {
                 // Si se creó el grupo como groupOfUniqueNames, y se proporcionó descripción,
                 // podríamos intentar añadirla en una operación de modificación posterior si es necesario y permitido.
                 // Por ahora, solo continuamos.

                $connection->disconnect(); // Cerrar conexión después de operar

                Log::channel('activity')->info('Grupo LDAP creado', [
                    'action' => 'Crear Grupo',
                    'group' => $request->cn
                ]);
                
                return redirect()->route('admin.groups.index')->with('success', 'Grupo creado correctamente');
            } else {
                 $connection->disconnect(); // Cerrar conexión
                 throw new Exception($errorMessage); // Lanzar el error si ninguno tuvo éxito
            }

        } catch (\Exception $e) {
            Log::channel('activity')->error('Error al crear grupo LDAP: ' . $e->getMessage(), [
                'action' => 'Error',
                'group' => $request->cn
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