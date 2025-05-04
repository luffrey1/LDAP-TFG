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
    public function crearCuentaLdap($password = null)
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

            // Conectar al servidor LDAP
            $connection->connect();

            // Generar usuario LDAP si no existe
            $username = $this->generarUsuarioLdap();
            
            // Generar contraseña si no se proporcionó
            if (!$password) {
                $password = self::generarPassword();
            }

            // Crear el usuario en LDAP
            $peopleOu = 'ou=people,dc=test,dc=tierno,dc=es';
            $alumnosGroupDn = 'cn=alumnos,ou=groups,dc=test,dc=tierno,dc=es';
            
            // Datos del usuario LDAP
            $userData = [
                'objectClass' => ['inetOrgPerson', 'posixAccount', 'top'],
                'cn' => $this->nombre_completo,
                'sn' => $this->apellidos,
                'givenName' => $this->nombre,
                'uid' => $username,
                'uidNumber' => $this->getNextUidNumber($connection),
                'gidNumber' => 500, // GID de alumnos
                'homeDirectory' => "/home/{$username}",
                'loginShell' => '/bin/bash',
                'mail' => $this->email ?: "{$username}@centro.local",
                'userPassword' => $this->hashPassword($password)
            ];

            // DN del usuario
            $userDn = "uid={$username},{$peopleOu}";
            
            // Crear el usuario
            $ldap = ldap_connect($config['hosts'][0], $config['port']);
            ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
            ldap_set_option($ldap, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
            
            // Autenticar con el servidor LDAP
            $bind = ldap_bind($ldap, $config['username'], $config['password']);
            if (!$bind) {
                throw new \Exception("No se pudo autenticar con el servidor LDAP: " . ldap_error($ldap));
            }
            
            // Crear el usuario usando la función nativa de PHP
            $add = ldap_add($ldap, $userDn, $userData);
            if (!$add) {
                throw new \Exception("Error al crear usuario LDAP: " . ldap_error($ldap));
            }
            
            // Añadir al grupo de alumnos
            $alumnosGroup = $connection->query()->find($alumnosGroupDn);
            if ($alumnosGroup) {
                $members = isset($alumnosGroup['uniquemember']) ? $alumnosGroup['uniquemember'] : [];
                if (!is_array($members)) {
                    $members = [$members];
                }
                $members[] = $userDn;
                
                // Usar ldap_modify en lugar de $connection->modify
                $ldapConn = ldap_connect($config['hosts'][0], $config['port']);
                ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
                ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);
                ldap_set_option($ldapConn, LDAP_OPT_X_TLS_REQUIRE_CERT, LDAP_OPT_X_TLS_NEVER);
                
                // Autenticar con el servidor LDAP
                $bind = ldap_bind($ldapConn, $config['username'], $config['password']);
                if (!$bind) {
                    throw new \Exception("No se pudo autenticar con el servidor LDAP para modificar grupo: " . ldap_error($ldapConn));
                }
                
                // Modificar el grupo para añadir al usuario
                $result = ldap_modify($ldapConn, $alumnosGroupDn, ['uniquemember' => $members]);
                if (!$result) {
                    Log::warning("Error al añadir usuario al grupo de alumnos: " . ldap_error($ldapConn));
                }
                
                ldap_close($ldapConn);
            }
            
            // Actualizar el modelo
            $this->ldap_dn = $userDn;
            $this->cuenta_creada = true;
            $this->save();
            
            return [
                'success' => true,
                'message' => 'Cuenta LDAP creada correctamente',
                'username' => $username,
                'password' => $password,
                'dn' => $userDn
            ];
            
        } catch (\Exception $e) {
            Log::error("Error al crear cuenta LDAP: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al crear cuenta LDAP: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener el siguiente UID disponible
     */
    private function getNextUidNumber($connection)
    {
        try {
            // Obtener el máximo UID actual
            $maxUid = 10000; // UID mínimo para usuarios
            
            $users = $connection->query()
                ->in('ou=people,dc=test,dc=tierno,dc=es')
                ->where('objectclass', '=', 'posixAccount')
                ->get();
                
            foreach ($users as $user) {
                if (isset($user['uidnumber']) && is_array($user['uidnumber'])) {
                    $uid = (int)$user['uidnumber'][0];
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
