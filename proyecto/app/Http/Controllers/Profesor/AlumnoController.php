<?php

namespace App\Http\Controllers\Profesor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AlumnoClase;
use App\Models\ClaseGrupo;
use App\Models\AlumnoActividad;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use League\Csv\Reader;
use League\Csv\Statement;
use Exception;

class AlumnoController extends Controller
{
 

    /**
     * Mostrar lista de alumnos del profesor
     */
    public function index()
    {
        // Usar la variable de sesión en lugar de Auth::user()
        $user = session('auth_user');
        
        // Verificar que el usuario esté autenticado
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Si es admin, puede ver todos los alumnos
        if ($user['is_admin']) {
            $alumnos = AlumnoClase::with('grupo')->get();
        } else {
            // Obtener grupos del profesor
            $grupos = ClaseGrupo::where('profesor_id', $user['id'])->pluck('id');
            
            // Obtener alumnos de esos grupos
            $alumnos = AlumnoClase::whereIn('clase_grupo_id', $grupos)->with('grupo')->get();
        }
        
        return view('profesor.alumnos.index', compact('alumnos'));
    }

    /**
     * Mostrar formulario para crear alumno
     */
    public function create()
    {
        $user = session('auth_user');
        
        // Si es admin, puede ver todos los grupos
        if ($user['is_admin']) {
            $grupos = ClaseGrupo::where('activo', true)->get();
        } else {
            // Solo ver grupos asignados al profesor
            $grupos = ClaseGrupo::where('profesor_id', $user['id'])
                ->where('activo', true)
                ->get();
        }
        
        return view('profesor.alumnos.create', compact('grupos'));
    }

    /**
     * Almacenar un nuevo alumno
     */
    public function store(Request $request)
    {
        // Preparar datos para validación
        $requestData = $request->all();
        
        // Convertir checkbox a booleano
        $requestData['crear_cuenta_ldap'] = $request->has('crear_cuenta_ldap') ? true : false;
        
        // Validar datos del formulario
        $validator = Validator::make($requestData, [
            'nombre' => 'required|string|max:100',
            'apellidos' => 'required|string|max:200',
            'email' => 'nullable|email|max:255',
            'fecha_nacimiento' => 'nullable|date',
            'dni' => 'nullable|string|max:15',
            'clase_grupo_id' => 'required|exists:clase_grupos,id',
            'crear_cuenta_ldap' => 'boolean'
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        $validated = $validator->validated();
        
        // Debug para ver los datos recibidos
        Log::debug("Datos para crear alumno: " . json_encode($validated));
        
        try {
            DB::beginTransaction();
            
            // Crear el alumno en la base de datos
            $alumno = new AlumnoClase();
            $alumno->nombre = $validated['nombre'];
            $alumno->apellidos = $validated['apellidos'];
            $alumno->email = $validated['email'] ?? null;
            $alumno->fecha_nacimiento = $validated['fecha_nacimiento'] ?? null;
            $alumno->dni = $validated['dni'] ?? null;
            $alumno->clase_grupo_id = $validated['clase_grupo_id'];
            $alumno->save();
            
            Log::debug("Alumno creado con ID: " . $alumno->id);
            
            // Si se solicitó crear cuenta LDAP
            if ($validated['crear_cuenta_ldap']) {
                // Generar nombre de usuario basado en nombre y apellidos
                $nombreUsuario = strtolower(substr($alumno->nombre, 0, 1) . str_replace(' ', '', $alumno->apellidos));
                $nombreUsuario = $this->limpiarUsuarioLdap($nombreUsuario);
                
                // Verificar si ya existe ese usuario en LDAP
                $config = config('ldap.connections.default');
                Log::debug("Configurando conexión LDAP con los siguientes parámetros:", [
                    'hosts' => $config['hosts'],
                    'port' => 389,
                    'base_dn' => $config['base_dn'],
                    'username' => $config['username'],
                    'use_ssl' => false,
                    'use_tls' => false,
                    'timeout' => $config['timeout']
                ]);
                
                // Conexión simple sin TLS/SSL
                $ldapConn = ldap_connect("ldap://{$config['hosts'][0]}:389");
                if (!$ldapConn) {
                    throw new \Exception("No se pudo conectar al servidor LDAP");
                }
                
                Log::debug("Conexión LDAP establecida, configurando opciones...");
                
                // Configurar opciones básicas
                ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);
                
                Log::debug("Intentando bind con credenciales...");
                
                // Intentar bind
                if (!ldap_bind($ldapConn, $config['username'], $config['password'])) {
                    throw new \Exception("Error en bind LDAP: " . ldap_error($ldapConn));
                }
                
                Log::debug("Bind LDAP exitoso");
                
                // Verificar la conexión con una búsqueda simple
                $search = ldap_search($ldapConn, $config['base_dn'], "(objectclass=*)", ["dn"]);
                if (!$search) {
                    throw new \Exception("Error al realizar búsqueda LDAP: " . ldap_error($ldapConn));
                }
                
                $entries = ldap_get_entries($ldapConn, $search);
                Log::debug("Búsqueda LDAP exitosa, encontrados " . $entries['count'] . " resultados");
                
                // Preparar datos del usuario
                $userDn = "uid={$nombreUsuario},ou=people,{$config['base_dn']}";
                $userData = [
                    'objectclass' => ['top', 'person', 'organizationalPerson', 'inetOrgPerson', 'posixAccount', 'shadowAccount'],
                    'cn' => $alumno->nombre . ' ' . $alumno->apellidos,
                    'sn' => $alumno->apellidos,
                    'givenname' => $alumno->nombre,
                    'uid' => $nombreUsuario,
                    'uidnumber' => $this->getNextUidNumber($ldapConn),
                    'gidnumber' => 500,
                    'homedirectory' => "/home/{$nombreUsuario}",
                    'loginshell' => '/bin/bash',
                    'userpassword' => $this->hashLdapPassword(Str::random(10)),
                    'shadowLastChange' => floor(time() / 86400)
                ];
                
                if ($alumno->email) {
                    $userData['mail'] = $alumno->email;
                }
                
                Log::debug("Intentando crear usuario LDAP: {$userDn}");
                
                // Crear el usuario
                if (!ldap_add($ldapConn, $userDn, $userData)) {
                    throw new \Exception("Error al crear usuario LDAP: " . ldap_error($ldapConn));
                }
                
                Log::info("Usuario LDAP creado con éxito: {$nombreUsuario}");
                
                // Añadir usuario al grupo adecuado
                $tipoImportacion = $request->input('tipo_importacion', 'alumno');
                $grupoLdap = $tipoImportacion === 'profesor' ? 'profesores' : 'alumnos';
                $grupoDn = "cn={$grupoLdap},ou=groups,{$config['base_dn']}";
                
                Log::debug("Intentando añadir usuario al grupo: {$grupoDn}");
                
                // Verificar el tipo de grupo y añadir el usuario según corresponda
                $groupInfo = ldap_read($ldapConn, $grupoDn, "(objectClass=*)", ['objectClass']);
                if (!$groupInfo) {
                    throw new \Exception("Error al leer información del grupo: " . ldap_error($ldapConn));
                }
                
                $groupEntry = ldap_first_entry($ldapConn, $groupInfo);
                $groupAttrs = ldap_get_attributes($ldapConn, $groupEntry);
                
                if (in_array('posixGroup', $groupAttrs['objectClass'])) {
                    // Grupo POSIX
                    $modify = ['memberUid' => $nombreUsuario];
                } else {
                    // Grupo estándar
                    $modify = ['member' => $userDn];
                }
                
                if (!ldap_mod_add($ldapConn, $grupoDn, $modify)) {
                    throw new \Exception("Error al añadir usuario al grupo: " . ldap_error($ldapConn));
                }
                
                Log::info("Usuario añadido al grupo {$grupoLdap} con éxito");
                
                ldap_close($ldapConn);
                
                // Actualizar datos del alumno
                $alumno->ldap_dn = $userDn;
                $alumno->usuario_ldap = $nombreUsuario;
                $alumno->cuenta_creada = true;
                $alumno->save();
                
                // Guardar mensaje para mostrar al usuario
                $ldapCreado = [
                    'username' => $nombreUsuario,
                    'password' => $this->hashLdapPassword(Str::random(10))
                ];
                
                session()->flash('ldap_creado', $ldapCreado);
                Log::info("Cuenta LDAP creada con éxito para {$nombreUsuario}");
                
            }
            
            DB::commit();
            
            return redirect()->route('profesor.alumnos.index')
                ->with('success', 'Alumno creado correctamente');
                
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al crear alumno: " . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Error al crear el alumno: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Limpiar nombre de usuario para LDAP
     */
    private function limpiarUsuarioLdap($username)
    {
        // Eliminar acentos y caracteres especiales
        $username = iconv('UTF-8', 'ASCII//TRANSLIT', $username);
        
        // Eliminar caracteres no alfanuméricos
        $username = preg_replace('/[^a-zA-Z0-9]/', '', $username);
        
        return strtolower($username);
    }
    
    /**
     * Hashear contraseña para LDAP
     */
    private function hashLdapPassword($password)
    {
        // Generar hash SSHA (método recomendado para OpenLDAP)
        $salt = random_bytes(4);
        $hash = '{SSHA}' . base64_encode(sha1($password . $salt, true) . $salt);
        return $hash;
    }

    /**
     * Mostrar datos de un alumno
     */
    public function show(string $id)
    {
        $alumno = AlumnoClase::with('grupo')->findOrFail($id);
        
        // Verificar que el usuario tenga acceso a este alumno
        $user = session('auth_user');
        if (!$user['is_admin'] && $alumno->grupo->profesor_id != $user['id']) {
            abort(403, 'No tienes permiso para ver este alumno');
        }
        
        // Obtener actividades recientes (últimos 30 días)
        $actividades = AlumnoActividad::where('alumno_clase_id', $id)
            ->where('fecha_hora', '>=', now()->subDays(30))
            ->orderBy('fecha_hora', 'desc')
            ->get();
        
        return view('profesor.alumnos.show', compact('alumno', 'actividades'));
    }

    /**
     * Mostrar formulario para editar alumno
     */
    public function edit(string $id)
    {
        $alumno = AlumnoClase::findOrFail($id);
        
        // Verificar que el usuario tenga acceso a este alumno
        $user = session('auth_user');
        $grupo = ClaseGrupo::findOrFail($alumno->clase_grupo_id);
        
        if (!$user['is_admin'] && $grupo->profesor_id != $user['id']) {
            abort(403, 'No tienes permiso para editar este alumno');
        }
        
        // Obtener grupos disponibles
        if ($user['is_admin']) {
            $grupos = ClaseGrupo::where('activo', true)->get();
        } else {
            $grupos = ClaseGrupo::where('profesor_id', $user['id'])
                ->where('activo', true)
                ->get();
        }
        
        return view('profesor.alumnos.edit', compact('alumno', 'grupos'));
    }

    /**
     * Actualizar datos de un alumno
     */
    public function update(Request $request, string $id)
    {
        $alumno = AlumnoClase::findOrFail($id);
        
        // Verificar que el usuario tenga acceso a este alumno
        $user = session('auth_user');
        $grupoActual = ClaseGrupo::findOrFail($alumno->clase_grupo_id);
        
        if (!$user['is_admin'] && $grupoActual->profesor_id != $user['id']) {
            abort(403, 'No tienes permiso para actualizar este alumno');
        }
        
        // Validar datos del formulario
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'email' => 'nullable|email|max:100',
            'dni' => 'nullable|string|max:20',
            'numero_expediente' => 'nullable|string|max:50',
            'fecha_nacimiento' => 'nullable|date',
            'clase_grupo_id' => 'required|exists:clase_grupos,id',
            'activo' => 'sometimes',
            'crear_cuenta_ldap' => 'sometimes',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        // Si cambia de grupo, verificar que el usuario tenga acceso al nuevo grupo
        $nuevoGrupoId = $request->clase_grupo_id;
        if ($nuevoGrupoId != $alumno->clase_grupo_id) {
            $nuevoGrupo = ClaseGrupo::findOrFail($nuevoGrupoId);
            
            if (!$user['is_admin'] && $nuevoGrupo->profesor_id != $user['id']) {
                abort(403, 'No tienes permiso para mover el alumno a este grupo');
            }
        }

        try {
            DB::beginTransaction();
            
            // Actualizar datos del alumno
            $alumno->nombre = $request->nombre;
            $alumno->apellidos = $request->apellidos;
            $alumno->email = $request->email;
            $alumno->dni = $request->dni;
            $alumno->numero_expediente = $request->numero_expediente;
            $alumno->fecha_nacimiento = $request->fecha_nacimiento;
            $alumno->clase_grupo_id = $nuevoGrupoId;
            $alumno->activo = $request->has('activo') ? true : false;
            
            // Si se solicita crear cuenta LDAP y no tiene cuenta
            if ($request->has('crear_cuenta_ldap') && !$alumno->cuenta_creada) {
                $resultado = $alumno->crearCuentaLdap();
                
                if (!$resultado['success']) {
                    throw new \Exception('Error al crear cuenta LDAP: ' . $resultado['message']);
                }
            }
            
            $alumno->save();
            
            DB::commit();
            
            return redirect()->route('profesor.alumnos.index')
                ->with('success', 'Alumno actualizado correctamente');
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al actualizar alumno: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Error al actualizar el alumno: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Eliminar un alumno
     */
    public function destroy(string $id)
    {
        $alumno = AlumnoClase::findOrFail($id);
        
        // Verificar que el usuario tenga acceso a este alumno
        $user = session('auth_user');
        $grupo = ClaseGrupo::findOrFail($alumno->clase_grupo_id);
        
        if (!$user['is_admin'] && $grupo->profesor_id != $user['id']) {
            abort(403, 'No tienes permiso para eliminar este alumno');
        }
        
        try {
            $alumno->delete();
            return redirect()->route('profesor.alumnos.index')
                ->with('success', 'Alumno eliminado correctamente');
        } catch (\Exception $e) {
            Log::error('Error al eliminar alumno: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Error al eliminar el alumno: ' . $e->getMessage());
        }
    }
    
    /**
     * Mostrar formulario para importar alumnos desde CSV
     */
    public function importForm()
    {
        $user = session('auth_user');
        
        // Si es admin, puede ver todos los grupos
        if ($user['is_admin']) {
            $grupos = ClaseGrupo::where('activo', true)->get();
        } else {
            // Solo ver grupos asignados al profesor
            $grupos = ClaseGrupo::where('profesor_id', $user['id'])
                ->where('activo', true)
                ->get();
        }
        
        return view('profesor.alumnos.import', compact('grupos'));
    }
    
    /**
     * Procesar importación de alumnos desde CSV
     */
    public function importProcess(Request $request)
    {
        // Verificar que sea una petición POST
        if (!$request->isMethod('post')) {
            return redirect()->route('profesor.alumnos.import')
                ->with('error', 'Por favor, utiliza el formulario para importar alumnos.');
        }

        // Convertir checkbox a booleano antes de la validación
        $data = $request->all();
        $data['tiene_encabezados'] = $request->boolean('tiene_encabezados');
        $data['crear_cuentas_ldap'] = $request->boolean('crear_cuentas_ldap');
        // Decodificar alumnos_data si es string
        if (isset($data['alumnos_data']) && is_string($data['alumnos_data'])) {
            $decoded = json_decode($data['alumnos_data'], true);
            if (is_array($decoded)) {
                $data['alumnos_data'] = $decoded;
                $request->merge(['alumnos_data' => $decoded]);
            }
        }
        // Forzar confirmar_importacion a booleano
        if ($request->has('confirmar_importacion')) {
            $request->merge(['confirmar_importacion' => $request->boolean('confirmar_importacion')]);
        }
        
        // Validar formulario
        if ($request->has('confirmar_importacion')) {
            $validator = Validator::make($request->all(), [
                'clase_grupo_id' => 'required|exists:clase_grupos,id',
                'alumnos_data' => 'required|array',
                'confirmar_importacion' => 'boolean',
            ]);
        } else {
            $validator = Validator::make($request->all(), [
                'archivo_csv' => 'required|file|mimes:csv,txt|max:2048',
                'clase_grupo_id' => 'required|exists:clase_grupos,id',
                'crear_cuentas_ldap' => 'boolean',
                'separador' => 'required|string|size:1',
                'tiene_encabezados' => 'boolean',
            ]);
        }

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Si no es confirmación, mostrar previsualización
        if (!$request->has('confirmar_importacion')) {
            try {
                // Procesar archivo CSV
                $file = $request->file('archivo_csv');
                $csv = Reader::createFromPath($file->getPathname(), 'r');
                $csv->setDelimiter($request->separador);
                
                // Si tiene encabezados, saltamos la primera fila
                if ($request->boolean('tiene_encabezados')) {
                    $stmt = Statement::create()->offset(1);
                    $records = $stmt->process($csv);
                } else {
                    $records = $csv->getRecords();
                }
                
                $alumnosData = [];
                $errores = [];
                
                foreach ($records as $index => $record) {
                    // Verificar que tenga al menos nombre y apellidos
                    if (count($record) < 2 || empty($record[0]) || empty($record[1])) {
                        $errores[] = "Fila " . ($index + 1) . ": Faltan campos obligatorios (nombre y apellidos)";
                        continue;
                    }
                    
                    // Generar contraseña aleatoria
                    $password = AlumnoClase::generarPassword();
                    
                    // Preparar datos del alumno
                    $alumnoData = [
                        'nombre' => $record[0],
                        'apellidos' => $record[1],
                        'email' => count($record) > 2 ? $record[2] : null,
                        'dni' => count($record) > 3 ? $record[3] : null,
                        'numero_expediente' => count($record) > 4 ? $record[4] : null,
                        'fecha_nacimiento' => (count($record) > 5 && !empty($record[5])) ? 
                            date('Y-m-d', strtotime($record[5])) : null,
                        'password' => $password
                    ];
                    
                    $alumnosData[] = $alumnoData;
                }
                
                // Guardar datos en sesión para la confirmación
                session([
                    'import_preview' => [
                        'alumnos' => $alumnosData,
                        'grupo_id' => $request->clase_grupo_id,
                        'crear_ldap' => $request->has('crear_cuentas_ldap'),
                        'errores' => $errores
                    ]
                ]);
                
                return view('profesor.alumnos.import-preview', [
                    'alumnos' => $alumnosData,
                    'grupo' => ClaseGrupo::find($request->clase_grupo_id),
                    'errores' => $errores
                ]);
                
            } catch (\Exception $e) {
                Log::error('Error al procesar CSV: ' . $e->getMessage());
                return redirect()->back()
                    ->with('error', 'Error al procesar el archivo: ' . $e->getMessage())
                    ->withInput();
            }
        }
        
        // Si es confirmación, procesar la importación
        try {
            $importData = session('import_preview');
            // Si no hay datos en sesión, intenta leer del campo oculto
            if (!$importData && $request->has('alumnos_data')) {
                $importData = [
                    'alumnos' => json_decode($request->input('alumnos_data'), true),
                    'grupo_id' => $request->input('clase_grupo_id'),
                    'crear_ldap' => $request->boolean('crear_cuentas_ldap'),
                    'errores' => []
                ];
            }
            if (!$importData) {
                return redirect()->route('profesor.alumnos.import')->with('error', 'No hay datos de importación. Por favor, sube el archivo de nuevo.');
            }
            
            DB::beginTransaction();
            
            $alumnosImportados = 0;
            $errores = [];
            
            foreach ($importData['alumnos'] as $alumnoData) {
                try {
                    // Crear alumno
                    $alumno = new AlumnoClase();
                    $alumno->nombre = $alumnoData['nombre'];
                    $alumno->apellidos = $alumnoData['apellidos'];
                    $alumno->email = $alumnoData['email'];
                    $alumno->dni = $alumnoData['dni'];
                    $alumno->numero_expediente = $alumnoData['numero_expediente'];
                    $alumno->fecha_nacimiento = $alumnoData['fecha_nacimiento'];
                    $alumno->clase_grupo_id = $importData['grupo_id'];
                    $alumno->activo = true;
                    
                    // Si se solicita crear cuentas LDAP
                    if ($importData['crear_ldap']) {
                        // Pasar el tipo de importación al método crearCuentaLdap
                        $tipoImportacion = $request->input('tipo_importacion', 'alumno');
                        $resultado = $alumno->crearCuentaLdap($alumnoData['password'], $tipoImportacion);
                        
                        if (!$resultado['success']) {
                            $errores[] = "Error al crear cuenta LDAP para {$alumno->nombre_completo}: " . $resultado['message'];
                        }
                    }
                    
                    $alumno->save();
                    $alumnosImportados++;
                    
                } catch (\Exception $e) {
                    $errores[] = "Error al importar {$alumnoData['nombre']} {$alumnoData['apellidos']}: " . $e->getMessage();
                }
            }
            
            DB::commit();
            
            // Limpiar datos de sesión
            session()->forget('import_preview');
            
            if (count($errores) > 0) {
                $errorMsg = "Se importaron " . $alumnosImportados . " alumnos con " . count($errores) . " errores.";
                return redirect()->route('profesor.alumnos.index')
                    ->with('warning', $errorMsg)
                    ->with('importErrors', $errores);
            } else {
                return redirect()->route('profesor.alumnos.index')
                    ->with('success', "Se importaron " . $alumnosImportados . " alumnos correctamente");
            }
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al importar alumnos: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Error al importar alumnos: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Ver registro de actividades de un alumno
     */
    public function actividades(string $id)
    {
        $alumno = AlumnoClase::with('grupo')->findOrFail($id);
        
        // Verificar que el usuario tenga acceso a este alumno
        $user = session('auth_user');
        if (!$user['is_admin'] && $alumno->grupo->profesor_id != $user['id']) {
            abort(403, 'No tienes permiso para ver este alumno');
        }
        
        // Obtener todas las actividades
        $actividades = AlumnoActividad::where('alumno_clase_id', $id)
            ->orderBy('fecha_hora', 'desc')
            ->paginate(20);
        
        return view('profesor.alumnos.actividades', compact('alumno', 'actividades'));
    }

    /**
     * Buscar alumnos en LDAP y mostrarlos para su selección
     */
    public function buscarAlumnosLdap(Request $request)
    {
        $user = session('auth_user');
        
        // Verificar que el usuario tenga permisos para añadir alumnos
        if (!$user) {
            return redirect()->route('login');
        }
        
        try {
            // Obtener grupos que el profesor puede gestionar
            if ($user['is_admin']) {
                $grupos = ClaseGrupo::where('activo', true)->get();
            } else {
                $grupos = ClaseGrupo::where('profesor_id', $user['id'])
                    ->where('activo', true)
                    ->get();
            }
            
            // Verificar si hay un grupo preseleccionado en la URL
            $grupoSeleccionado = null;
            $grupoId = $request->input('clase_grupo_id');
            
            if ($grupoId) {
                $grupo = ClaseGrupo::find($grupoId);
                
                // Verificar que el profesor tenga acceso a este grupo
                if ($grupo && ($user['is_admin'] || $grupo->profesor_id == $user['id'])) {
                    $grupoSeleccionado = $grupo;
                }
            }
            
            $terminoBusqueda = $request->input('termino', '');
            $resultados = [];
            
            // Si hay un término de búsqueda, realizar la consulta en LDAP
            if (!empty($terminoBusqueda)) {
                Log::info("Buscando alumnos en LDAP con término: $terminoBusqueda");
                
                try {
                    // Usar la configuración del archivo config/ldap.php
                    $config = config('ldap.connections.default');
                    
                    Log::debug("Configurando conexión LDAP con los siguientes parámetros:", [
                        'hosts' => $config['hosts'],
                        'port' => 389,
                        'base_dn' => $config['base_dn'],
                        'username' => $config['username'],
                        'use_ssl' => false,
                        'use_tls' => false,
                        'timeout' => $config['timeout']
                    ]);
                    
                    // Conexión simple sin TLS/SSL
                    $ldapConn = ldap_connect("ldap://{$config['hosts'][0]}:389");
                    if (!$ldapConn) {
                        throw new \Exception("No se pudo conectar al servidor LDAP");
                    }
                    
                    Log::debug("Conexión LDAP establecida, configurando opciones...");
                    
                    // Configurar opciones básicas
                    ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
                    ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);
                    
                    Log::debug("Intentando bind con credenciales...");
                    
                    // Intentar bind
                    if (!ldap_bind($ldapConn, $config['username'], $config['password'])) {
                        throw new \Exception("Error en bind LDAP: " . ldap_error($ldapConn));
                    }
                    
                    Log::debug("Bind LDAP exitoso");
                    
                    // Intentar obtener usuarios del grupo alumnos
                    $alumnosDn = 'cn=alumnos,ou=groups,dc=tierno,dc=es';
                    
                    // Obtener miembros del grupo
                    try {
                        $grupo = ldap_read($ldapConn, $alumnosDn, "(objectClass=*)", ['objectClass']);
                        
                        if ($grupo && isset($grupo['uniquemember'])) {
                            $miembros = is_array($grupo['uniquemember']) 
                                ? $grupo['uniquemember'] 
                                : [$grupo['uniquemember']];
                            
                            Log::info("Miembros encontrados en grupo alumnos: " . count($miembros));
                            
                            // Para cada miembro, obtener sus datos si coincide con el término de búsqueda
                            foreach ($miembros as $miembroDn) {
                                // Extraer el uid del DN
                                if (preg_match('/uid=([^,]+)/', $miembroDn, $matches)) {
                                    $uid = $matches[1];
                                    
                                    try {
                                        // Buscar el usuario en LDAP
                                        $ldapUser = ldap_read($ldapConn, "ou=people,dc=tierno,dc=es", "(uid=$uid)", ['givenname', 'sn', 'mail']);
                                        
                                        if ($ldapUser) {
                                            $nombre = isset($ldapUser['givenname'][0]) ? 
                                                $ldapUser['givenname'][0] : '';
                                            
                                            $apellidos = isset($ldapUser['sn'][0]) ? 
                                                $ldapUser['sn'][0] : '';
                                            
                                            $email = isset($ldapUser['mail'][0]) ? 
                                                $ldapUser['mail'][0] : '';
                                            
                                            $nombreCompleto = trim($nombre . ' ' . $apellidos);
                                            
                                            // Si el término de búsqueda está en el nombre, apellidos o uid
                                            if (stripos($nombreCompleto, $terminoBusqueda) !== false || 
                                                stripos($uid, $terminoBusqueda) !== false ||
                                                stripos($email, $terminoBusqueda) !== false) {
                                                
                                                // Verificar si el alumno ya existe en el sistema
                                                $existente = AlumnoClase::where('usuario_ldap', $uid)->first();
                                                
                                                $resultados[] = [
                                                    'uid' => $uid,
                                                    'nombre' => $nombre,
                                                    'apellidos' => $apellidos,
                                                    'email' => $email,
                                                    'dn' => $miembroDn,
                                                    'ya_importado' => $existente ? true : false,
                                                    'clase_actual' => $existente ? $existente->grupo->nombre : null
                                                ];
                                            }
                                        }
                                    } catch (\Exception $userEx) {
                                        Log::error("Error al procesar usuario LDAP $uid: " . $userEx->getMessage());
                                    }
                                }
                            }
                        } else {
                            Log::warning("Grupo de alumnos no encontrado o sin miembros");
                        }
                    } catch (\Exception $groupEx) {
                        Log::error("Error al buscar grupo de alumnos: " . $groupEx->getMessage());
                    }
                    
                    // Si no hay resultados del grupo, intentar búsqueda general
                    if (empty($resultados)) {
                        Log::info("Realizando búsqueda general en LDAP");
                        
                        $ldapUsers = ldap_search($ldapConn, "ou=people,dc=tierno,dc=es", "(objectclass=inetOrgPerson)", ['givenname', 'sn', 'mail']);
                        $ldapEntries = ldap_get_entries($ldapConn, $ldapUsers);
                        
                        Log::info("Usuarios encontrados en búsqueda general: " . count($ldapEntries));
                        
                        foreach ($ldapEntries as $ldapUser) {
                            $uid = isset($ldapUser['uid'][0]) ? 
                                $ldapUser['uid'][0] : '';
                            
                            if (!empty($uid)) {
                                $nombre = isset($ldapUser['givenname'][0]) ? 
                                    $ldapUser['givenname'][0] : '';
                                
                                $apellidos = isset($ldapUser['sn'][0]) ? 
                                    $ldapUser['sn'][0] : '';
                                
                                $email = isset($ldapUser['mail'][0]) ? 
                                    $ldapUser['mail'][0] : '';
                                
                                // Verificar si el alumno ya existe en el sistema
                                $existente = AlumnoClase::where('usuario_ldap', $uid)->first();
                                
                                $resultados[] = [
                                    'uid' => $uid,
                                    'nombre' => $nombre,
                                    'apellidos' => $apellidos,
                                    'email' => $email,
                                    'dn' => "uid=$uid,ou=people,dc=tierno,dc=es",
                                    'ya_importado' => $existente ? true : false,
                                    'clase_actual' => $existente ? $existente->grupo->nombre : null
                                ];
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Error al conectar con LDAP: " . $e->getMessage());
                    Log::error("Stack trace: " . $e->getTraceAsString());
                }
                
                Log::info("Total de resultados encontrados: " . count($resultados));
            }
            
            return view('profesor.alumnos.ldap', compact('grupos', 'terminoBusqueda', 'resultados', 'grupoSeleccionado'));
        } catch (\Exception $e) {
            Log::error('Error al buscar alumnos en LDAP: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return redirect()->route('profesor.alumnos.index')
                ->with('error', 'Error al buscar alumnos en LDAP: ' . $e->getMessage());
        }
    }
    
    /**
     * Importar alumnos seleccionados desde LDAP
     */
    public function importarAlumnosLdap(Request $request)
    {
        // Validar formulario
        $validator = Validator::make($request->all(), [
            'alumnos' => 'required|array',
            'alumnos.*' => 'required|string',
            'clase_grupo_id' => 'required|exists:clase_grupos,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }
        
        // Verificar acceso al grupo
        $grupoId = $request->clase_grupo_id;
        $user = session('auth_user');
        $grupo = ClaseGrupo::findOrFail($grupoId);
        
        if (!$user['is_admin'] && $grupo->profesor_id != $user['id']) {
            abort(403, 'No tienes permiso para añadir alumnos a este grupo');
        }
        
        try {
            // Crear una conexión LDAP usando la configuración
            $config = config('ldap.connections.default');
            Log::debug("Configurando conexión LDAP con los siguientes parámetros:", [
                'hosts' => $config['hosts'],
                'port' => 389,
                'base_dn' => $config['base_dn'],
                'username' => $config['username'],
                'use_ssl' => false,
                'use_tls' => false,
                'timeout' => $config['timeout']
            ]);
            
            // Conexión simple sin TLS/SSL
            $ldapConn = ldap_connect("ldap://{$config['hosts'][0]}:389");
            if (!$ldapConn) {
                throw new \Exception("No se pudo conectar al servidor LDAP");
            }
            
            Log::debug("Conexión LDAP establecida, configurando opciones...");
            
            // Configurar opciones básicas
            ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);
            
            Log::debug("Intentando bind con credenciales...");
            
            // Intentar bind
            if (!ldap_bind($ldapConn, $config['username'], $config['password'])) {
                throw new \Exception("Error en bind LDAP: " . ldap_error($ldapConn));
            }
            
            Log::debug("Bind LDAP exitoso");
            
            DB::beginTransaction();
            
            $importados = 0;
            $errores = [];
            $alumnosUid = $request->alumnos;
            
            foreach ($alumnosUid as $uid) {
                // Verificar si ya existe en la base de datos
                $alumnoExistente = AlumnoClase::where('usuario_ldap', $uid)->first();
                
                if ($alumnoExistente) {
                    // Si existe, actualizar su grupo
                    $nombreAnterior = $alumnoExistente->grupo->nombre;
                    $alumnoExistente->clase_grupo_id = $grupoId;
                    $alumnoExistente->save();
                    
                    Log::info("Alumno LDAP $uid actualizado de grupo $nombreAnterior a {$grupo->nombre}");
                    $importados++;
                    continue;
                }
                
                // Buscar el usuario en LDAP
                $search = ldap_search($ldapConn, "ou=people,{$config['base_dn']}", "(uid=$uid)");
                if (!$search) {
                    $errores[] = "Error al buscar usuario LDAP con UID: $uid - " . ldap_error($ldapConn);
                    continue;
                }
                
                $entries = ldap_get_entries($ldapConn, $search);
                if ($entries['count'] === 0) {
                    $errores[] = "No se encontró el usuario LDAP con UID: $uid";
                    continue;
                }
                
                $ldapUser = $entries[0];
                
                // Extraer información
                $nombre = isset($ldapUser['givenname'][0]) ? $ldapUser['givenname'][0] : '';
                $apellidos = isset($ldapUser['sn'][0]) ? $ldapUser['sn'][0] : '';
                $email = isset($ldapUser['mail'][0]) ? $ldapUser['mail'][0] : '';
                
                // Crear nuevo alumno
                $alumno = new AlumnoClase();
                $alumno->nombre = $nombre;
                $alumno->apellidos = $apellidos;
                $alumno->email = $email;
                $alumno->clase_grupo_id = $grupoId;
                $alumno->usuario_ldap = $uid;
                $alumno->ldap_dn = "uid=$uid,ou=people,{$config['base_dn']}";
                $alumno->cuenta_creada = true;
                $alumno->activo = true;
                $alumno->save();
                
                Log::info("Alumno LDAP $uid importado correctamente al grupo {$grupo->nombre}");
                $importados++;
            }
            
            ldap_close($ldapConn);
            DB::commit();
            
            if (count($errores) > 0) {
                $errorMsg = "Se importaron $importados alumnos con " . count($errores) . " errores.";
                return redirect()->route('profesor.alumnos.index')
                    ->with('warning', $errorMsg)
                    ->with('importErrors', $errores);
            } else {
                return redirect()->route('profesor.alumnos.index')
                    ->with('success', "Se importaron $importados alumnos correctamente desde LDAP");
            }
            
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error al importar alumnos desde LDAP: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return redirect()->back()
                ->with('error', 'Error al importar alumnos: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Descargar plantilla CSV para importación de alumnos
     */
    public function downloadTemplate()
    {
        // Crear contenido de ejemplo para la plantilla
        $headers = ['Nombre', 'Apellidos', 'Email', 'DNI/Documento', 'Nº Expediente', 'Fecha Nacimiento'];
        $row1 = ['Juan', 'Pérez García', 'juan.perez@ejemplo.com', '12345678A', 'EXP001', '01/01/2000'];
        $row2 = ['María', 'González Rodríguez', 'maria.gonzalez@ejemplo.com', '87654321B', 'EXP002', '15/05/2001'];
        
        // Crear archivo CSV en memoria
        $output = fopen('php://temp', 'r+');
        fputcsv($output, $headers);
        fputcsv($output, $row1);
        fputcsv($output, $row2);
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        // Configurar respuesta para descargar
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla_alumnos.csv"',
        ];
        
        return response($csv, 200, $headers);
    }

    /**
     * Obtener el siguiente UID disponible
     */
    private function getNextUidNumber($ldapConn)
    {
        try {
            // Obtener el máximo UID actual
            $maxUid = 10000; // UID mínimo para usuarios
            
            $result = ldap_search($ldapConn, 'ou=people,' . config('ldap.connections.default.base_dn'), '(objectclass=posixAccount)');
            $entries = ldap_get_entries($ldapConn, $result);
            
            for ($i = 0; $i < $entries['count']; $i++) {
                if (isset($entries[$i]['uidnumber'][0])) {
                    $uid = (int)$entries[$i]['uidnumber'][0];
                    if ($uid > $maxUid) {
                        $maxUid = $uid;
                    }
                }
            }
            
            return $maxUid + 1;
            
        } catch (\Exception $e) {
            Log::warning("Error al obtener próximo UID: " . $e->getMessage());
            // En caso de error, generar un número aleatorio
            return rand(10000, 20000);
        }
    }
} 