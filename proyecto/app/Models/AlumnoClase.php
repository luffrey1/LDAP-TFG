<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use LdapRecord\Connection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class AlumnoClase extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Tabla asociada al modelo
     */
    protected $table = 'alumno_clases';

    /**
     * Atributos que son asignables en masa
     */
    protected $fillable = [
        'nombre',
        'apellidos',
        'email',
        'dni',
        'numero_expediente',
        'fecha_nacimiento',
        'clase_grupo_id',
        'ldap_dn',
        'usuario_ldap',
        'cuenta_creada',
        'activo',
        'metadatos'
    ];

    /**
     * Atributos que deben ser convertidos
     */
    protected $casts = [
        'fecha_nacimiento' => 'date',
        'cuenta_creada' => 'boolean',
        'activo' => 'boolean',
        'metadatos' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Atributos virtuales para añadir al modelo
     */
    protected $appends = [
        'nombre_completo',
    ];

    /**
     * Obtener el grupo al que pertenece el alumno
     */
    public function grupo()
    {
        return $this->belongsTo(ClaseGrupo::class, 'clase_grupo_id');
    }

    /**
     * Obtener las actividades de telemetría del alumno
     */
    public function actividades()
    {
        return $this->hasMany(AlumnoActividad::class, 'alumno_clase_id');
    }

    /**
     * Obtener el nombre completo del alumno
     */
    public function getNombreCompletoAttribute()
    {
        return "{$this->nombre} {$this->apellidos}";
    }

    /**
     * Generar un nombre de usuario LDAP para el alumno
     */
    public function generarUsuarioLdap()
    {
        // Si ya tiene usuario, devolverlo
        if (!empty($this->usuario_ldap)) {
            return $this->usuario_ldap;
        }

        // Generar usuario basado en nombre y apellidos
        $nombre = Str::slug($this->nombre, '');
        $apellido = Str::slug(explode(' ', $this->apellidos)[0], '');
        $usuario = strtolower(substr($nombre, 0, 1) . $apellido);

        // Asegurar que el usuario sea único
        $contador = 1;
        $usuarioBase = $usuario;
        while (self::where('usuario_ldap', $usuario)->exists()) {
            $usuario = $usuarioBase . $contador;
            $contador++;
        }

        $this->usuario_ldap = $usuario;
        $this->save();

        return $usuario;
    }

    /**
     * Generar una contraseña aleatoria que cumpla con los requisitos LDAP
     */
    public static function generarPassword($longitud = 10)
    {
        $mayusculas = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $minusculas = 'abcdefghijklmnopqrstuvwxyz';
        $numeros = '0123456789';
        $especiales = '!@#$%^&*()_-+=<>?';
        
        // Garantizar al menos un carácter de cada tipo
        $password = [
            $mayusculas[rand(0, strlen($mayusculas) - 1)],
            $minusculas[rand(0, strlen($minusculas) - 1)],
            $numeros[rand(0, strlen($numeros) - 1)],
            $especiales[rand(0, strlen($especiales) - 1)]
        ];
        
        // Completar con caracteres aleatorios
        $allChars = $mayusculas . $minusculas . $numeros . $especiales;
        for ($i = 4; $i < $longitud; $i++) {
            $password[] = $allChars[rand(0, strlen($allChars) - 1)];
        }
        
        // Desordenar la contraseña
        shuffle($password);
        
        return implode('', $password);
    }

    /**
     * Crear cuenta LDAP para el alumno
     */
    public function crearCuentaLdap($password = null, $tipoImportacion = 'alumno')
    {
        // Si ya tiene cuenta, no crear otra
        if ($this->cuenta_creada) {
            return [
                'success' => false,
                'message' => 'El alumno ya tiene una cuenta LDAP creada'
            ];
        }

        try {
            // Usar la configuración LDAP desde el archivo config
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

            // Generar usuario LDAP si no existe
            $username = $this->generarUsuarioLdap();
            
            // Generar contraseña si no se proporcionó
            if (!$password) {
                $password = self::generarPassword();
            }

            // Crear el usuario en LDAP
            $peopleOu = "ou=people,{$config['base_dn']}";
            
            // Determinar el grupo según el tipo de importación
            $isProfesor = $tipoImportacion === 'profesor';
            $groupDn = $isProfesor ? 
                "cn=profesores,ou=groups,{$config['base_dn']}" : 
                "cn=alumnos,ou=groups,{$config['base_dn']}";
            
            // Datos del usuario LDAP
            $userData = [
                'objectClass' => ['inetOrgPerson', 'posixAccount', 'top'],
                'cn' => $this->nombre_completo,
                'sn' => $this->apellidos,
                'givenName' => $this->nombre,
                'uid' => $username,
                'uidNumber' => $this->getNextUidNumber($ldapConn),
                'gidNumber' => $isProfesor ? 501 : 500, // 501 para profesores, 500 para alumnos
                'homeDirectory' => "/home/{$username}",
                'loginShell' => '/bin/bash',
                'mail' => $this->email ?: "{$username}@centro.local",
                'userPassword' => $this->hashPassword($password)
            ];

            // DN del usuario
            $userDn = "uid={$username},{$peopleOu}";
            
            Log::debug("Intentando crear usuario LDAP: {$userDn}");
            
            // Crear el usuario
            if (!ldap_add($ldapConn, $userDn, $userData)) {
                throw new \Exception("Error al crear usuario LDAP: " . ldap_error($ldapConn));
            }
            
            Log::debug("Usuario LDAP creado exitosamente");
            
            // Añadir al grupo correspondiente
            $groupInfo = ldap_read($ldapConn, $groupDn, "(objectClass=*)", ['objectClass']);
            if (!$groupInfo) {
                throw new \Exception("Error al leer información del grupo: " . ldap_error($ldapConn));
            }
            
            $groupEntry = ldap_first_entry($ldapConn, $groupInfo);
            $groupAttrs = ldap_get_attributes($ldapConn, $groupEntry);
            
            if (in_array('posixGroup', $groupAttrs['objectClass'])) {
                // Grupo POSIX
                $modify = ['memberUid' => $username];
            } else {
                // Grupo estándar
                $modify = ['member' => $userDn];
            }
            
            if (!ldap_mod_add($ldapConn, $groupDn, $modify)) {
                Log::warning("Error al añadir usuario al grupo: " . ldap_error($ldapConn));
            }
            
            Log::debug("Usuario añadido al grupo " . ($isProfesor ? "profesores" : "alumnos"));
            
            // Cerrar conexión LDAP
            ldap_close($ldapConn);
            
            // Actualizar datos del alumno
            $this->ldap_dn = $userDn;
            $this->usuario_ldap = $username;
            $this->cuenta_creada = true;
            $this->save();
            
            return [
                'success' => true,
                'message' => 'Cuenta LDAP creada correctamente',
                'username' => $username,
                'password' => $password
            ];
            
        } catch (\Exception $e) {
            Log::error("Error al crear cuenta LDAP: " . $e->getMessage());
            Log::error("Stack trace: " . $e->getTraceAsString());
            
            return [
                'success' => false,
                'message' => 'Error al crear cuenta LDAP: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener el siguiente UID disponible
     */
    private function getNextUidNumber($ldapConn)
    {
        try {
            // Obtener el máximo UID actual
            $maxUid = 10000; // UID mínimo para usuarios
            
            // Buscar todos los usuarios posixAccount
            $filter = "(objectClass=posixAccount)";
            $baseDn = "ou=people," . config('ldap.connections.default.base_dn');
            $result = ldap_search($ldapConn, $baseDn, $filter, ['uidNumber']);
            
            if (!$result) {
                throw new \Exception("Error al buscar usuarios: " . ldap_error($ldapConn));
            }
            
            $entries = ldap_get_entries($ldapConn, $result);
            
            // Encontrar el UID más alto
            for ($i = 0; $i < $entries['count']; $i++) {
                if (isset($entries[$i]['uidnumber'][0])) {
                    $uid = (int)$entries[$i]['uidnumber'][0];
                    if ($uid > $maxUid) {
                        $maxUid = $uid;
                    }
                }
            }
            
            // Incrementar el UID más alto encontrado
            return $maxUid + 1;
            
        } catch (\Exception $e) {
            Log::error("Error al obtener siguiente UID: " . $e->getMessage());
            // En caso de error, devolver un UID por defecto
            return 10001;
        }
    }

    /**
     * Hash de contraseña para LDAP
     */
    private function hashPassword($password)
    {
        // Hash SSHA para OpenLDAP
        $salt = random_bytes(4);
        $hash = '{SSHA}' . base64_encode(sha1($password . $salt, true) . $salt);
        return $hash;
    }
}
