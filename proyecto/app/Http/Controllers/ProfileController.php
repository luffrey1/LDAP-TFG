<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use LdapRecord\Container;
use LdapRecord\Connection;
use LdapRecord\Models\ActiveDirectory\User as LdapUser;
use Illuminate\Validation\Rule;
use App\Models\ClaseGrupo;

class ProfileController extends Controller
{
    /**
     * Muestra el formulario de edición del perfil.
     */
    public function edit(Request $request)
    {
        $user = $request->user();
        $groups = [];
        $ldapUid = '';
        $ldapGuid = '';
        $ldapCn = '';
        $fullName = $user->name;

        try {
            // Obtener la configuración LDAP
            $config = config('ldap.connections.default');
            
            // Crear conexión LDAP usando la configuración
            $ldap = new \LdapRecord\Connection([
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

            $ldap->connect();

            // Buscar el usuario por su nombre de usuario
            $ldapUser = $ldap->query()
                ->in('ou=people,' . config('ldap.connections.default.base_dn'))
                ->where('uid', '=', $user->username)
                ->first();

            if ($ldapUser) {
                // Obtener UID, GID y CN
                $ldapUid = is_array($ldapUser) ? ($ldapUser['uidnumber'][0] ?? '') : $ldapUser->getFirstAttribute('uidNumber');
                $ldapGuid = is_array($ldapUser) ? ($ldapUser['gidnumber'][0] ?? '') : $ldapUser->getFirstAttribute('gidNumber');
                $ldapCn = is_array($ldapUser) ? ($ldapUser['cn'][0] ?? '') : $ldapUser->getFirstAttribute('cn');
                $fullName = is_array($ldapUser) ? ($ldapUser['displayname'][0] ?? $ldapUser['cn'][0] ?? '') : ($ldapUser->getFirstAttribute('displayName') ?? $ldapUser->getFirstAttribute('cn'));

                Log::debug('UID numérico encontrado: ' . $ldapUid);
                Log::debug('GID encontrado: ' . $ldapGuid);
                Log::debug('CN encontrado: ' . $ldapCn);

                // Obtener grupos del usuario
                $allGroups = $ldap->query()
                    ->in('ou=groups,' . config('ldap.connections.default.base_dn'))
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
                        $userDn = is_array($ldapUser) ? $ldapUser['dn'] : $ldapUser->getDn();
                        if (in_array($userDn, $members)) {
                            $isMember = true;
                        }
                    }
                    
                    // También verificar memberUid para posixGroup
                    if (isset($group['memberuid'])) {
                        $memberUids = is_array($group['memberuid']) 
                            ? $group['memberuid'] 
                            : [$group['memberuid']];
                        
                        if (in_array($ldapUid, $memberUids)) {
                            $isMember = true;
                        }
                    }
                    
                    // Si el usuario es miembro por cualquier método, añadir el grupo
                    if ($isMember) {
                        $groups[] = [
                            'cn' => is_array($group) ? ($group['cn'][0] ?? '') : $group->getFirstAttribute('cn'),
                            'description' => is_array($group) ? ($group['description'][0] ?? '') : $group->getFirstAttribute('description'),
                            'nombre_completo' => is_array($group) ? ($group['cn'][0] ?? '') : $group->getFirstAttribute('cn')
                        ];
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('Error al obtener datos LDAP del usuario: ' . $e->getMessage());
        }

        return view('profile.edit', compact('user', 'groups', 'ldapUid', 'ldapGuid', 'ldapCn', 'fullName', 'ldapUser'));
    }

    /**
     * Actualiza el perfil del usuario.
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'current_password' => ['required_with:new_password'],
            'new_password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            // Obtener la configuración LDAP
            $config = config('ldap.connections.default');
            
            // Crear conexión LDAP usando la configuración
            $ldap = new \LdapRecord\Connection([
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

            $ldap->connect();

            // Buscar el usuario en LDAP
            $ldapUser = $ldap->query()
                ->in('ou=people,' . config('ldap.connections.default.base_dn'))
                ->where('uid', '=', $user->username)
                ->first();

            if ($ldapUser) {
                // Preparar datos para actualizar
                $updateData = [
                    'displayName' => $request->name,
                    'mail' => $request->email
                ];

                // Si se proporciona una nueva contraseña, actualizarla
                if ($request->filled('new_password')) {
                    // Verificar la contraseña actual
                    $currentPassword = $request->current_password;
                    $newPassword = $request->new_password;

                    // Obtener el DN del usuario
                    $userDn = is_array($ldapUser) ? $ldapUser['dn'] : $ldapUser->getDn();

                    // Intentar autenticar con la contraseña actual
                    $authLdap = new \LdapRecord\Connection([
                        'hosts' => $config['hosts'],
                        'port' => 636,
                        'base_dn' => $config['base_dn'],
                        'username' => $userDn,
                        'password' => $currentPassword,
                        'use_ssl' => true,
                        'use_tls' => false,
                        'options' => [
                            LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_NEVER,
                            LDAP_OPT_REFERRALS => 0,
                            LDAP_OPT_PROTOCOL_VERSION => 3,
                            LDAP_OPT_NETWORK_TIMEOUT => 5,
                        ],
                    ]);

                    try {
                        $authLdap->connect();
                        // Si llegamos aquí, la contraseña actual es correcta
                        $updateData['userPassword'] = $this->hashPassword($newPassword);
                        
                        // Actualizar la contraseña usando el admin
                        $adminLdap = new \LdapRecord\Connection([
                            'hosts' => $config['hosts'],
                            'port' => 636,
                            'base_dn' => $config['base_dn'],
                            'username' => $config['username'],
                            'password' => $config['password'],
                            'use_ssl' => true,
                            'use_tls' => false,
                            'options' => [
                                LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_NEVER,
                                LDAP_OPT_REFERRALS => 0,
                                LDAP_OPT_PROTOCOL_VERSION => 3,
                                LDAP_OPT_NETWORK_TIMEOUT => 5,
                            ],
                        ]);
                        
                        $adminLdap->connect();
                        Log::debug('Buscando usuario en LDAP con DN: ' . $userDn);
                        
                        // Buscar el usuario usando el DN directamente
                        $ldapEntry = $adminLdap->query()
                            ->in('ou=people,' . $config['base_dn'])
                            ->where('uid', '=', $user->username)
                            ->first();

                        if (!$ldapEntry) {
                            Log::error('No se encontró el usuario en LDAP para actualizar la contraseña: ' . $user->username);
                            return back()->with('error', 'No se pudo encontrar el usuario en LDAP para actualizar la contraseña');
                        }

                        Log::debug('Usuario encontrado en LDAP, actualizando contraseña');
                        
                        try {
                            // Obtener el DN del usuario
                            $userDn = is_array($ldapEntry) ? $ldapEntry['dn'] : $ldapEntry->getDn();
                            
                            // Crear una nueva conexión LDAP para la actualización
                            $updateLdap = new \LdapRecord\Connection([
                                'hosts' => $config['hosts'],
                                'port' => 636,
                                'base_dn' => $config['base_dn'],
                                'username' => $config['username'],
                                'password' => $config['password'],
                                'use_ssl' => true,
                                'use_tls' => false,
                                'options' => [
                                    LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_NEVER,
                                    LDAP_OPT_REFERRALS => 0,
                                    LDAP_OPT_PROTOCOL_VERSION => 3,
                                    LDAP_OPT_NETWORK_TIMEOUT => 5,
                                ],
                            ]);
                            
                            $updateLdap->connect();
                            
                            // Actualizar la contraseña usando el método correcto de LdapRecord
                            $entry = $updateLdap->query()
                                ->where('dn', '=', $userDn)
                                ->first();

                            if (!$entry) {
                                Log::error('No se encontró la entrada LDAP para actualizar la contraseña: ' . $userDn);
                                return back()->with('error', 'No se pudo encontrar la entrada LDAP para actualizar la contraseña');
                            }

                            $entry->setAttribute('userPassword', $this->hashPassword($newPassword));
                            $entry->save();
                            
                            Log::info('Contraseña actualizada correctamente para el usuario: ' . $user->username);
                            
                            // Verificar que la contraseña se actualizó correctamente
                            $verifyLdap = new \LdapRecord\Connection([
                                'hosts' => $config['hosts'],
                                'port' => 636,
                                'base_dn' => $config['base_dn'],
                                'username' => $userDn,
                                'password' => $newPassword,
                                'use_ssl' => true,
                                'use_tls' => false,
                                'options' => [
                                    LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_NEVER,
                                    LDAP_OPT_REFERRALS => 0,
                                    LDAP_OPT_PROTOCOL_VERSION => 3,
                                    LDAP_OPT_NETWORK_TIMEOUT => 5,
                                ],
                            ]);
                            
                            $verifyLdap->connect();
                            Log::info('Verificación de contraseña exitosa para el usuario: ' . $user->username);
                            
                        } catch (\Exception $e) {
                            Log::error('Error al actualizar la contraseña: ' . $e->getMessage());
                            return back()->with('error', 'Error al actualizar la contraseña: ' . $e->getMessage());
                        }
                            
                    } catch (\Exception $e) {
                        Log::error('Error al verificar contraseña actual: ' . $e->getMessage());
                        return back()->withErrors(['current_password' => 'La contraseña actual es incorrecta']);
                    }
                }

                // Actualizar el usuario en LDAP
                if (is_array($ldapUser)) {
                    // Si es un array, usar la conexión LDAP directamente
                    $dn = $ldapUser['dn'];
                    Log::debug('Buscando usuario en LDAP para actualizar datos con DN: ' . $dn);
                    
                    $entry = $ldap->query()
                        ->in('ou=people,' . $config['base_dn'])
                        ->where('uid', '=', $user->username)
                        ->first();

                    if (!$entry) {
                        Log::error('No se encontró el usuario en LDAP para actualizar los datos: ' . $user->username);
                        return back()->with('error', 'No se pudo encontrar el usuario en LDAP para actualizar los datos');
                    }

                    Log::debug('Usuario encontrado en LDAP, actualizando datos');
                    
                    // Actualizar los datos usando el método correcto según el tipo de resultado
                    if (is_array($entry)) {
                        $dn = $entry['dn'];
                        $ldapEntry = $ldap->query()
                            ->where('dn', '=', $dn)
                            ->first();
                        if ($ldapEntry) {
                            foreach ($updateData as $attribute => $value) {
                                if ($attribute !== 'userPassword') {
                                    $ldapEntry->setAttribute($attribute, $value);
                                }
                            }
                            $ldapEntry->save();
                        }
                    } else {
                        foreach ($updateData as $attribute => $value) {
                            if ($attribute !== 'userPassword') {
                                $entry->setAttribute($attribute, $value);
                            }
                        }
                        $entry->save();
                    }
                    
                    Log::info('Datos del usuario actualizados correctamente: ' . $user->username);
                } else {
                    // Si es un objeto, usar el método update
                    $updateDataWithoutPassword = array_diff_key($updateData, ['userPassword' => '']);
                    $ldapUser->update($updateDataWithoutPassword);
                    Log::info('Datos del usuario actualizados correctamente usando objeto LDAP');
                }

                // Actualizar el usuario local
                $user->name = $request->name;
                $user->email = $request->email;
                if ($request->filled('new_password')) {
                    $user->password = Hash::make($request->new_password);
                }
                $user->save();

                return back()->with('status', 'Perfil actualizado correctamente');
            }

            return back()->with('error', 'No se pudo encontrar el usuario en LDAP');

        } catch (\Exception $e) {
            Log::error('Error al actualizar perfil: ' . $e->getMessage());
            return back()->with('error', 'Error al actualizar el perfil: ' . $e->getMessage());
        }
    }

    /**
     * Genera un hash SSHA para la contraseña
     */
    protected function hashPassword($password)
    {
        $salt = random_bytes(4);
        $hash = sha1($password . $salt, true);
        return '{SSHA}' . base64_encode($hash . $salt);
    }
} 