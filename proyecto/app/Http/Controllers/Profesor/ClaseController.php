<?php

namespace App\Http\Controllers\Profesor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClaseGrupo;
use App\Models\AlumnoClase;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class ClaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Usar la variable de sesión en lugar de Auth::user()
        $user = session('auth_user');
        
        // Verificar que el usuario esté autenticado
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Los administradores pueden ver todos los grupos
        if ($user['is_admin']) {
            $grupos = ClaseGrupo::with('profesor')->get();
        } else {
            // Los profesores solo ven sus grupos asignados
            $grupos = ClaseGrupo::where('profesor_id', $user['id'])->get();
        }
        
        return view('profesor.clases.index', compact('grupos'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        try {
            // Método 1: Obtener profesores desde LDAP
            $profesores = [];
            
            try {
                // Crear una conexión LDAP usando la configuración
                $config = config('ldap.connections.default');
                Log::info("Configuración LDAP: " . json_encode($config));
                
                $connection = new \LdapRecord\Connection($config);
                $connection->connect();
                
                if ($connection->isConnected()) {
                    Log::info("Conexión LDAP establecida correctamente");
                    
                    // Intenta buscar todos los usuarios en LDAP
                    try {
                        Log::info("Buscando todos los usuarios en LDAP...");
                        $ldapUsers = $connection->query()
                            ->in('ou=people,dc=tierno,dc=es')
                            ->where('objectclass', '=', 'inetOrgPerson')
                            ->get();
                        
                        Log::info("Total de usuarios LDAP encontrados: " . count($ldapUsers));
                        
                        // Buscar el grupo de profesores para verificar membresía
                        $profesoresGroup = $connection->query()
                            ->in('ou=groups,dc=tierno,dc=es')
                            ->where('cn', '=', 'profesores')
                            ->first();
                            
                        $profesoresUids = [];
                        if ($profesoresGroup && isset($profesoresGroup['memberuid'])) {
                            $profesoresUids = is_array($profesoresGroup['memberuid']) ? 
                                $profesoresGroup['memberuid'] : [$profesoresGroup['memberuid']];
                            Log::info("Miembros del grupo profesores: " . json_encode($profesoresUids));
                        }
                        
                        // También verificar grupo de administradores
                        $adminGroup = $connection->query()
                            ->in('ou=groups,dc=tierno,dc=es')
                            ->where('cn', '=', 'ldapadmins')
                            ->first();
                            
                        $adminUids = [];
                        if ($adminGroup && isset($adminGroup['memberuid'])) {
                            $adminUids = is_array($adminGroup['memberuid']) ? 
                                $adminGroup['memberuid'] : [$adminGroup['memberuid']];
                            Log::info("Miembros del grupo admin: " . json_encode($adminUids));
                        }
                        
                        foreach ($ldapUsers as $ldapUser) {
                            try {
                                $uid = isset($ldapUser['uid']) ? 
                                    (is_array($ldapUser['uid']) ? $ldapUser['uid'][0] : $ldapUser['uid']) : '';
                                
                                if (!$uid) continue;
                                
                                // Verificar si es profesor o admin por grupo o username
                                $isProfesor = in_array($uid, $profesoresUids) || 
                                              in_array($uid, $adminUids) ||
                                              strtolower($uid) === 'ldap-admin' ||
                                              strtolower($uid) === 'ldapadmin' ||
                                              strpos(strtolower($uid), 'profesor') !== false;
                                
                                if (!$isProfesor) {
                                    // Si no es profesor o admin, ignorar
                                    continue;
                                }
                                
                                $name = isset($ldapUser['cn']) ? 
                                    (is_array($ldapUser['cn']) ? $ldapUser['cn'][0] : $ldapUser['cn']) : $uid;
                                
                                $email = isset($ldapUser['mail']) ? 
                                    (is_array($ldapUser['mail']) ? $ldapUser['mail'][0] : $ldapUser['mail']) : "$uid@test.tierno.es";
                                
                                // Buscar si ya existe en la BD
                                $dbUser = \App\Models\User::where('username', $uid)->first();
                                
                                if ($dbUser) {
                                    // Si existe en BD, usamos ese usuario
                                    $profesores[] = $dbUser;
                                    Log::info("Usuario LDAP encontrado en BD: $uid (ID: {$dbUser->id})");
                                } else {
                                    // Crear objeto User temporal (no guardado en BD)
                                    $tempUser = new \App\Models\User([
                                        'id' => 'ldap_' . $uid,
                                        'name' => $name,
                                        'username' => $uid,
                                        'email' => $email,
                                        'role' => in_array($uid, $adminUids) || strtolower($uid) === 'ldap-admin' ? 'admin' : 'profesor',
                                    ]);
                                    $profesores[] = $tempUser;
                                    Log::info("Usuario LDAP no encontrado en BD, creado objeto temporal: $uid");
                                }
                            } catch (\Exception $userEx) {
                                Log::error("Error al procesar usuario LDAP: " . $userEx->getMessage());
                            }
                        }
                    } catch (\Exception $usersEx) {
                        Log::error("Error al buscar todos los usuarios LDAP: " . $usersEx->getMessage());
                    }
                } else {
                    Log::error("No se pudo conectar a LDAP");
                }
            } catch (\Exception $e) {
                Log::error("Error al conectar con LDAP: " . $e->getMessage());
                Log::error("Stack trace: " . $e->getTraceAsString());
            }
            
            Log::info("Profesores encontrados en LDAP: " . count($profesores));
            
            // Método 2: Obtener usuarios locales con rol de profesor
            try {
                Log::info("Buscando profesores en la base de datos local");
                $bdProfesores = \App\Models\User::where(function($query) {
                    $query->where('role', 'profesor')
                          ->orWhere('is_admin', true)
                          ->orWhere('role', 'admin');
                })
                ->orderBy('name')
                ->get();
                
                Log::info('Profesores encontrados en BD: ' . $bdProfesores->count());
                
                // Extraer los usernames existentes en el array de profesores LDAP
                $existingUsernames = collect($profesores)->pluck('username')->toArray();
                
                // Añadir solo los que no estén ya en el array
                foreach ($bdProfesores as $profesor) {
                    if (!in_array($profesor->username, $existingUsernames)) {
                        $profesores[] = $profesor;
                        Log::info("Añadido profesor de BD: {$profesor->username} (ID: {$profesor->id})");
                    }
                }
            } catch (\Exception $dbEx) {
                Log::error("Error al buscar profesores en BD: " . $dbEx->getMessage());
            }
            
            // Ordenar profesores por nombre
            usort($profesores, function($a, $b) {
                return strcmp($a->name, $b->name);
            });
            
            Log::info("Total de profesores (LDAP + BD): " . count($profesores));
            
            // Si sigue sin haber profesores, mostrar todos los usuarios
            if (empty($profesores)) {
                Log::warning("No se encontraron profesores. Obteniendo todos los usuarios...");
                
                try {
                    $allUsers = \App\Models\User::where(function($query) {
                        $query->where('role', 'profesor')
                              ->orWhere('is_admin', true)
                              ->orWhere('role', 'admin');
                    })
                    ->orderBy('name')
                    ->get();
                    
                    Log::info('Total de usuarios obtenidos: ' . $allUsers->count());
                    $profesores = $allUsers;
                } catch (\Exception $allEx) {
                    Log::error("Error al obtener todos los usuarios: " . $allEx->getMessage());
                    
                    // Último recurso: crear un profesor por defecto
                    Log::warning("Creando profesor por defecto como último recurso");
                    $profesores = [
                        new \App\Models\User([
                            'id' => 1,
                            'name' => 'Usuario Temporal',
                            'username' => 'admin',
                            'email' => 'admin@example.com',
                            'role' => 'admin',
                        ])
                    ];
                }
            }
            
            $niveles = ['ESO', 'Bachillerato', 'FP Básica', 'FP Medio', 'FP Superior', 'Otro'];
            
            return view('profesor.clases.create', compact('profesores', 'niveles'));
        } catch (\Exception $e) {
            // Registrar error y redirigir con mensaje de error
            Log::error('Error al obtener profesores: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return redirect()->route('profesor.clases.index')
                ->with('error', 'Error al cargar el formulario: ' . $e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validación de datos del formulario
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:500',
            'nivel' => 'required|string|max:100',
            'curso' => 'required|integer|min:1|max:10',
            'seccion' => 'required|string|max:20',
            'profesor_id' => ['required', function ($attribute, $value, $fail) {
                if (!is_numeric($value) && !is_string($value)) {
                    $fail('El ID del profesor debe ser un número o un nombre de usuario válido.');
                }
                
                if (is_numeric($value)) {
                    // Verificar si existe en la base de datos
                    $exists = \App\Models\User::where('id', $value)
                        ->where(function($query) {
                            $query->where('role', 'profesor')
                                ->orWhere('role', 'admin')
                                ->orWhere('is_admin', true);
                        })
                        ->exists();
                    
                    if (!$exists) {
                        $fail('El profesor seleccionado no existe en la base de datos.');
                    }
                }
            }],
        ]);
        
        // Debug para ver los datos recibidos
        Log::debug("Datos para crear clase: " . json_encode($validated));
        
        try {
            DB::beginTransaction();
            
            // Verificar si es un ID numérico (BD) o un string (LDAP username)
            $profesorId = $validated['profesor_id'];
            $user = null;
            
            Log::debug("Procesando profesor: " . $profesorId);
            
            if (is_numeric($profesorId)) {
                // Es un ID de usuario en la BD
                Log::debug("Buscando profesor con ID: " . $profesorId);
                $user = User::find($profesorId);
                
                if (!$user) {
                    throw new \Exception("No se encontró el profesor con ID: " . $profesorId);
                }
            } else {
                // Es un username LDAP
                Log::debug("Buscando profesor en LDAP con UID: " . $profesorId);
                
                // Buscar si ya existe en la BD
                $user = User::where('username', $profesorId)->first();
                
                if (!$user) {
                    Log::info("Usuario LDAP $profesorId no existe en la BD, intentando crear");
                    
                    // Intentar obtener información del usuario desde LDAP
                    try {
                        // Crear una conexión LDAP usando la configuración
                        $config = config('ldap.connections.default');
                        Log::info("Configuración LDAP: " . json_encode($config));
                        
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
                        
                        // Intenta buscar el usuario en LDAP
                        $filter = "(uid={$profesorId})";
                        $search = ldap_search($ldapConn, "ou=people,dc=tierno,dc=es", $filter);
                        
                        if (!$search) {
                            throw new Exception("Error al buscar usuario: " . ldap_error($ldapConn));
                        }
                        
                        $entries = ldap_get_entries($ldapConn, $search);
                        
                        // Si no se encuentra en LDAP, crear usuario local
                        if ($entries['count'] == 0) {
                            Log::info("Usuario no encontrado en LDAP, creando usuario local");
                            
                            // Crear usuario local con rol de profesor
                            $user = new User();
                            $user->name = $profesorId; // Usar el username como nombre inicial
                            $user->username = $profesorId;
                            $user->email = $profesorId . '@test.tierno.es';
                            $user->password = bcrypt(Str::random(16));
                            $user->role = 'profesor';
                            $user->save();
                            
                            Log::info("Usuario local creado: {$user->id} - {$user->name}");
                        } else {
                            $ldapUser = $entries[0];
                            Log::info("Usuario LDAP encontrado: " . json_encode($ldapUser));
                            
                            // Determinar si es admin por grupo
                            $esAdmin = false;
                            try {
                                $adminFilter = "(cn=ldapadmins)";
                                $adminSearch = ldap_search($ldapConn, "ou=groups,dc=tierno,dc=es", $adminFilter);
                                
                                if ($adminSearch) {
                                    $adminEntries = ldap_get_entries($ldapConn, $adminSearch);
                                    if ($adminEntries['count'] > 0) {
                                        $adminGroup = $adminEntries[0];
                                        if (isset($adminGroup['memberuid'])) {
                                            $adminUids = is_array($adminGroup['memberuid']) ? 
                                                $adminGroup['memberuid'] : [$adminGroup['memberuid']];
                                            $esAdmin = in_array($profesorId, $adminUids);
                                        }
                                    }
                                }
                            } catch (\Exception $e) {
                                Log::error("Error al verificar grupo admin: " . $e->getMessage());
                            }
                            
                            // También es admin si el username es ldap-admin
                            if (strtolower($profesorId) === 'ldap-admin' || strtolower($profesorId) === 'ldapadmin') {
                                $esAdmin = true;
                            }
                            
                            // Extraer datos y crear usuario
                            $name = isset($ldapUser['cn']) ? 
                                (is_array($ldapUser['cn']) ? $ldapUser['cn'][0] : $ldapUser['cn']) : $profesorId;
                                
                            $email = isset($ldapUser['mail']) ? 
                                (is_array($ldapUser['mail']) ? $ldapUser['mail'][0] : $ldapUser['mail']) : "$profesorId@test.tierno.es";
                            
                            // Crear el usuario en la base de datos
                            $user = new User();
                            $user->name = $name;
                            $user->username = $profesorId;
                            $user->email = $email;
                            $user->password = bcrypt(Str::random(16)); // Contraseña aleatoria
                            $user->role = $esAdmin ? 'admin' : 'profesor';
                            $user->save();
                            
                            Log::info("Usuario creado en la BD: {$user->id} - {$user->name}");
                        }
                        
                        ldap_close($ldapConn);
                    } catch (\Exception $e) {
                        Log::error("Error al crear usuario desde LDAP: " . $e->getMessage());
                        throw new \Exception("Error al crear usuario desde LDAP: " . $e->getMessage());
                    }
                }
            }
            
            // Generar código único para la clase
            $codigo = $this->generarCodigoUnico($validated['nivel'], $validated['curso'], $validated['seccion']);
            
            // Crear el grupo de clase con el usuario obtenido
            $clase = new ClaseGrupo();
            $clase->nombre = $validated['nombre'];
            $clase->codigo = $codigo;
            $clase->descripcion = $validated['descripcion'];
            $clase->nivel = $validated['nivel'];
            $clase->curso = $validated['curso'];
            $clase->seccion = $validated['seccion'];
            $clase->profesor_id = $user->id;
            $clase->activo = true;
            $clase->save();
            
            DB::commit();
            
            return redirect()->route('profesor.clases.index')
                ->with('success', 'Clase creada correctamente.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al crear clase: " . $e->getMessage());
            
            return redirect()->route('profesor.clases.create')
                ->with('error', 'Error al crear la clase: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Genera un código único para la clase basado en el nivel, curso y sección
     */
    private function generarCodigoUnico($nivel, $curso, $seccion)
    {
        // Limpiar y formatear los componentes
        $nivelAbrev = strtoupper(preg_replace('/[^A-Za-z]/', '', $nivel));
        $cursoNum = $curso;
        $seccionLimpia = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $seccion));
        
        // Generar el código base
        $codigoBase = sprintf('%s%d%s', $nivelAbrev, $cursoNum, $seccionLimpia);
        
        // Verificar si el código ya existe y añadir un sufijo numérico si es necesario
        $codigo = $codigoBase;
        $contador = 1;
        
        while (ClaseGrupo::where('codigo', $codigo)->exists()) {
            $codigo = $codigoBase . '-' . $contador;
            $contador++;
        }
        
        return $codigo;
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $grupo = ClaseGrupo::with('profesor')->findOrFail($id);
        $alumnos = AlumnoClase::where('clase_grupo_id', $id)->get();
        
        // Verificar que el usuario tenga acceso a este grupo
        $user = session('auth_user');
        if (!$user['is_admin'] && $grupo->profesor_id != $user['id']) {
            abort(403, 'No tienes permiso para ver este grupo');
        }
        
        return view('profesor.clases.show', compact('grupo', 'alumnos'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $grupo = ClaseGrupo::findOrFail($id);
        
        // Verificar que el usuario tenga acceso a este grupo
        $user = session('auth_user');
        if (!$user['is_admin'] && $grupo->profesor_id != $user['id']) {
            abort(403, 'No tienes permiso para editar este grupo');
        }
        
        $profesores = User::where('role', 'profesor')->orWhere('role', 'admin')->get();
        $niveles = ['ESO', 'Bachillerato', 'FP Básica', 'FP Medio', 'FP Superior', 'Otro'];
        
        return view('profesor.clases.edit', compact('grupo', 'profesores', 'niveles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $grupo = ClaseGrupo::findOrFail($id);
        
        // Verificar que el usuario tenga acceso a este grupo
        $user = session('auth_user');
        if (!$user['is_admin'] && $grupo->profesor_id != $user['id']) {
            abort(403, 'No tienes permiso para actualizar este grupo');
        }
        
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'nivel' => 'required|string|max:50',
            'curso' => 'required|string|max:20',
            'seccion' => 'required|string|max:20',
            'profesor_id' => 'required|exists:users,id',
            'descripcion' => 'nullable|string|max:255',
            'activo' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $grupo->nombre = $request->nombre;
            $grupo->descripcion = $request->descripcion;
            $grupo->nivel = $request->nivel;
            $grupo->curso = $request->curso;
            $grupo->seccion = $request->seccion;
            $grupo->profesor_id = $request->profesor_id;
            $grupo->activo = $request->has('activo');
            $grupo->save();
            
            return redirect()->route('profesor.clases.index')
                ->with('success', 'Grupo actualizado correctamente');
        } catch (\Exception $e) {
            Log::error('Error al actualizar grupo de clase: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Error al actualizar el grupo: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Verificar que el usuario esté autenticado
        $user = session('auth_user');
        if (!$user) {
            return redirect()->route('login');
        }

        try {
            // Buscar la clase a eliminar
            $clase = ClaseGrupo::findOrFail($id);
            
            // Solo el creador o un administrador puede eliminar
            if ($clase->profesor_id !== $user['id'] && !$user['is_admin']) {
                return back()
                    ->with('error', 'No tienes permiso para eliminar esta clase.');
            }
            
            // Verificar si hay alumnos en la clase
            $alumnosCount = AlumnoClase::where('clase_grupo_id', $id)->count();
            if ($alumnosCount > 0) {
                // Eliminar las relaciones primero
                AlumnoClase::where('clase_grupo_id', $id)->delete();
            }
            
            // Eliminar la clase
            $clase->delete();
            
            return redirect()->route('profesor.clases.index')
                ->with('success', 'Clase eliminada correctamente.');
        } catch (\Exception $e) {
            Log::error('Error al eliminar clase: ' . $e->getMessage());
            return back()
                ->with('error', 'Ocurrió un error al eliminar la clase: ' . $e->getMessage());
        }
    }
    
    /**
     * Mostrar las clases del profesor autenticado
     */
    public function misClases()
    {
        // Usamos la información del usuario LDAP de la sesión
        $usuario = session('auth_user');
        Log::debug("Usuario en sesión para misClases: " . json_encode($usuario));
        
        // Verificar si tenemos el ID del usuario
        $userId = $usuario['id'] ?? null;
        $username = $usuario['username'] ?? null;
        
        Log::debug("Buscando clases para profesor - ID: {$userId}, Username: {$username}");
        
        // Consulta base para clases
        $query = ClaseGrupo::orderBy('created_at', 'desc');
        
        // Buscar clases por ID de usuario
        if ($userId) {
            // Si tenemos ID de usuario
            $query->where('profesor_id', $userId);
        } else if ($username) {
            // Si solo tenemos el nombre de usuario
            try {
                // Buscar el usuario en la base de datos
                $dbUser = \App\Models\User::where('username', $username)->first();
                
                if ($dbUser) {
                    Log::debug("Usuario encontrado en BD con ID: {$dbUser->id}");
                    $query->where('profesor_id', $dbUser->id);
                } else {
                    Log::error("No se encontró el usuario en la BD: {$username}");
                    return view('profesor.clases.mis-clases', ['misClases' => collect()]);
                }
            } catch (\Exception $e) {
                Log::error("Error al buscar usuario: " . $e->getMessage());
                return view('profesor.clases.mis-clases', ['misClases' => collect()]);
            }
        } else {
            // Si no tenemos identificadores, mostrar mensaje
            Log::error("No se pudo obtener identificación del profesor desde la sesión");
            return view('profesor.clases.mis-clases', ['misClases' => collect()]);
        }
        
        // Ejecutar la consulta
        $misClases = $query->get();
        
        Log::debug("Clases encontradas para el profesor: " . count($misClases));
        
        return view('profesor.clases.mis-clases', compact('misClases'));
    }
    
    /**
     * Ver detalles de una clase
     */
    public function verClase($id)
    {
        $clase = ClaseGrupo::findOrFail($id);
        
        // Verificar que la clase pertenece al profesor o es admin
        $usuario = session('auth_user');
        $userId = $usuario['id'] ?? null;
        $isAdmin = $usuario['is_admin'] ?? false;
        
        // Si no es admin, verificar que sea el profesor asignado
        if (!$isAdmin && $clase->profesor_id != $userId) {
            return redirect()->route('profesor.clases.mias')
                ->with('error', 'No tienes permiso para ver esta clase');
        }
        
        // Obtener los alumnos de la clase
        $alumnos = AlumnoClase::where('clase_grupo_id', $id)
                    ->where('activo', true)
                    ->orderBy('apellidos')
                    ->orderBy('nombre')
                    ->get();
        
        Log::debug("Alumnos encontrados para la clase ID {$id}: " . count($alumnos));
        
        return view('profesor.clases.ver', [
            'clase' => $clase,
            'alumnos' => $alumnos
        ]);
    }

    /**
     * Ver detalles de una clase específica del profesor como tutor
     */
    public function verMiClase($id)
    {
        $clase = ClaseGrupo::findOrFail($id);
        
        // Verificar que la clase pertenece al profesor actual
        $usuario = session('auth_user');
        $userId = $usuario['id'] ?? null;
        $isAdmin = $usuario['is_admin'] ?? false;
        
        // Solo el profesor asignado o un admin puede ver esta vista detallada
        if (!$isAdmin && $clase->profesor_id != $userId) {
            return redirect()->route('profesor.clases.mias')
                ->with('error', 'No tienes permiso para ver esta clase');
        }
        
        // Obtener los alumnos de la clase
        $alumnos = AlumnoClase::where('clase_grupo_id', $id)
                    ->where('activo', true)
                    ->orderBy('apellidos')
                    ->orderBy('nombre')
                    ->get();
        
        Log::debug("Alumnos encontrados para la clase ID {$id} (vista tutor): " . count($alumnos));
        
        return view('profesor.clases.ver-mi-clase', [
            'clase' => $clase,
            'alumnos' => $alumnos
        ]);
    }
}
