use LdapRecord\Models\Entry;
use LdapRecord\Models\ActiveDirectory\User as LdapUser; // Asegúrate de tener el alias correcto si usas un modelo específico
use LdapRecord\Connection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LdapUserController extends Controller
{
    protected $connection;
    protected $peopleOu;

    public function __construct()
    {
        // Configura la conexión LDAP. Asegúrate de que la configuración 'ldap.connections.default' esté correcta.
        $config = config('ldap.connections.default');
        $this->connection = new Connection($config);
        $this->peopleOu = 'ou=people,' . $config['base_dn'];
    }

    /**
     * Muestra el formulario para crear un nuevo usuario LDAP.
     */
    public function create()
    {
        // Aquí podrías cargar datos necesarios para el formulario, si los hubiera.
        // Por ahora, simplemente retornamos la vista.
        return view('admin.users.create');
    }

    /**
     * Almacena un nuevo usuario en LDAP.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_type' => 'required|in:profesor,alumno',
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'username' => 'required|string|max:50|unique_ldap:'.$this->peopleOu.',uid',
            'email' => 'required|email|max:100',
            // 'uid' no se valida directamente aquí ya que se genera automáticamente
            // 'gid' no se valida directamente aquí ya que se obtiene del tipo de usuario
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            // Conectar a LDAP
            $this->connection->connect();

            // Obtener el GID basado en el tipo de usuario
            $gidNumber = $this->getGroupId($request->user_type);
             if ($gidNumber === null) {
                return back()->withInput()->with('error', 'No se pudo obtener el GID para el tipo de usuario seleccionado.');
            }

            // Obtener el siguiente UID disponible
            $nextUid = $this->getNextUidNumber();

            // Construir el DN del nuevo usuario
            $userDn = 'uid=' . $request->username . ',' . $this->peopleOu;

            // Preparar datos del usuario LDAP
            $userData = [
                'objectclass' => ['top', 'person', 'organizationalPerson', 'inetOrgPerson', 'posixAccount'],
                'cn' => $request->nombre . ' ' . $request->apellido,
                'sn' => $request->apellido,
                'givenname' => $request->nombre,
                'uid' => $request->username,
                'mail' => $request->email,
                'userpassword' => $this->hashPassword($request->password), // Hash de la contraseña
                'homedirectory' => '/home/' . $request->username,
                'gidnumber' => $gidNumber,
                'uidnumber' => $nextUid,
                'loginshell' => '/bin/bash'
            ];

            // Crear la entrada del usuario
            $entry = new Entry();
            $entry->setDn($userDn);
            foreach ($userData as $attribute => $value) {
                $entry->setAttribute($attribute, $value);
            }

            // Añadir el usuario a LDAP
            $this->connection->add($entry);

            // Añadir al grupo correspondiente
            $groupName = $request->user_type === 'profesor' ? 'profesores' : 'alumnos';
            $groupDn = "cn={$groupName},ou=groups," . $this->connection->getBaseDn();

            $group = $this->connection->query()->find($groupDn);

            if ($group) {
                // Añadir el DN completo del usuario al atributo 'uniqueMember' del grupo
                $group->add(['uniqueMember' => $userDn]);
                $group->save();
                 Log::debug("Usuario '{$request->username}' añadido al grupo '{$groupName}'.");
            } else {
                 Log::warning("No se encontró el grupo LDAP '{$groupName}' para añadir al usuario '{$request->username}'.");
                // Opcional: manejar error si el grupo no existe
            }

            // Registrar la acción en logs
             // Asegúrate de tener un método getCurrentUsername() o similar
             // $adminUser = $this->getCurrentUsername(); 
             Log::info("Usuario LDAP creado: {$request->username}. Tipo: {$request->user_type}");

            return redirect()->route('admin.users.index')
                ->with('success', 'Usuario creado correctamente.');

        } catch (LdapRecord\Models\ModelNotFoundException $e) {
             Log::error('Error LDAP (Usuario/Grupo no encontrado): ' . $e->getMessage());
             return back()->withInput()->with('error', 'Error de configuración LDAP: ' . $e->getMessage());

        } catch (LdapRecord\Exceptions\LdapRecordException $e) {
            Log::error('Error de LdapRecord: ' . $e->getMessage());
            Log::error('Código de error LDAP: ' . $e->getCode());
            // Puedes agregar manejo de errores específicos por código LDAP aquí
            return back()->withInput()->with('error', 'Error al interactuar con LDAP: ' . $e->getMessage());

        } catch (Exception $e) {
            Log::error('Error general al crear usuario LDAP: ' . $e->getMessage());
            Log::error('Traza: ' . $e->getTraceAsString());
            return back()->withInput()->with('error', 'Error al crear el usuario: ' . $e->getMessage());
        }
    }

     /**
      * Busca el GID de un grupo por su nombre.
      *
      * @param string $groupName 'profesores' o 'alumnos'
      * @return int|null El GID o null si no se encuentra.
      */
     protected function getGroupId(string $groupName): ?int
     {
         try {
             $groupDn = "cn={$groupName},ou=groups," . $this->connection->getBaseDn();
             $group = $this->connection->query()->find($groupDn);
             if ($group && $group->hasAttribute('gidnumber')) {
                 return (int) $group->getFirstAttribute('gidnumber');
             }
         } catch (Exception $e) {
             Log::error("Error al buscar GID para el grupo '{$groupName}': " . $e->getMessage());
         }
         return null;
     }

     /**
      * Obtiene el siguiente UID disponible en LDAP.
      *
      * Esto es una implementación simple. En producción, se recomienda un método más robusto
      * para evitar colisiones en entornos concurrentes.
      *
      * @return int El siguiente UID disponible.
      */
     protected function getNextUidNumber(): int
     {
         // Buscar el usuario con el UID más alto actual
         $search = $this->connection->query()
             ->in($this->peopleOu)
             ->select('uidnumber')
             ->sortBy('uidnumber', 'desc')
             ->limit(1)
             ->first();

         if ($search && $search->hasAttribute('uidnumber')) {
             $lastUid = (int) $search->getFirstAttribute('uidnumber');
             // Asegúrate de que el siguiente UID sea al menos 10000 o un valor inicial adecuado
             return max($lastUid + 1, 10000); // Empezar desde 10000 si no hay usuarios o el último es menor
         } else {
             // Si no hay usuarios, empezar desde un valor base (ej. 10000 para usuarios normales)
             return 10000;
         }
     }

     /**
      * Hashea la contraseña para LDAP (usando SSHA).
      *
      * @param string $password La contraseña en texto plano.
      * @return string La contraseña hasheada en formato SSHA.
      */
     protected function hashPassword(string $password): string
     {
         // Genera un salt aleatorio de 8 bytes
         $salt = random_bytes(8);

         // Calcula el hash SHA-1 de la contraseña y el salt
         $hash = sha1($password . $salt, true);

         // Concatena el hash y el salt
         $hashAndSalt = $hash . $salt;

         // Codifica el resultado en base64 y añade el prefijo SSHA
         return '{SSHA}' . base64_encode($hashAndSalt);
     }

     // Asegúrate de tener métodos para getCurrentUsername() y addUserToGroup()
     // O ajusta la lógica para usar los métodos de LdapRecord add y save en grupos

     // Ejemplo de un método addUserToGroup usando LdapRecord (adaptar si tu esquema LDAP es diferente)
     protected function addUserToGroup(string $userDn, string $groupDn): void
     {
         $group = $this->connection->query()->find($groupDn);

         if ($group) {
             // Asumiendo que el atributo de miembro es 'uniqueMember' o 'member'
             // Verifica el esquema de tu servidor LDAP
             $memberAttribute = $group->hasAttribute('uniqueMember') ? 'uniqueMember' : 'member';

             $currentMembers = $group->getAttribute($memberAttribute) ?? [];

             if (!in_array($userDn, $currentMembers)) {
                 $group->add([$memberAttribute => $userDn]);
                 $group->save();
             }
         } else {
             throw new Exception("El grupo LDAP con DN '{$groupDn}' no fue encontrado.");
         }
     }
     
      // Método placeholder para obtener el nombre de usuario actual (adaptar según tu autenticación)
     protected function getCurrentUsername(): string
     {
         // Implementar lógica para obtener el usuario actualmente autenticado
         // Por ejemplo, si usas Laravel Auth:
         // return auth()->user()->username ?? 'system';
         return 'admin'; // Valor por defecto o placeholder
     }
} 