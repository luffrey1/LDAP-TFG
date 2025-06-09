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
        $ldapUid = $user->username;
        $ldapGuid = '';
        $ldapCn = '';
        $fullName = $user->name;

        try {
            $ldap = new \LdapRecord\Connection([
                'hosts' => config('ldap.connections.default.hosts'),
                'port' => config('ldap.connections.default.port'),
                'base_dn' => config('ldap.connections.default.base_dn'),
                'username' => config('ldap.connections.default.username'),
                'password' => config('ldap.connections.default.password'),
                'use_ssl' => config('ldap.connections.default.use_ssl'),
                'use_tls' => config('ldap.connections.default.use_tls'),
                'timeout' => config('ldap.connections.default.timeout'),
                'options' => [
                    LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_NEVER,
                    LDAP_OPT_REFERRALS => 0,
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
                $ldapUid = $ldapUser->getFirstAttribute('uid');
                $ldapGuid = $ldapUser->getFirstAttribute('gidNumber');
                $ldapCn = $ldapUser->getFirstAttribute('cn');
                $fullName = $ldapUser->getFirstAttribute('displayName') ?? $ldapUser->getFirstAttribute('cn');

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
                        if (in_array($ldapUser->getDn(), $members)) {
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
                            'cn' => $group->getFirstAttribute('cn'),
                            'description' => $group->getFirstAttribute('description'),
                            'nombre_completo' => $group->getFirstAttribute('cn')
                        ];
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('Error al obtener datos LDAP del usuario: ' . $e->getMessage());
        }

        return view('profile.edit', compact('user', 'groups', 'ldapUid', 'ldapGuid', 'ldapCn', 'fullName'));
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
            'current_password' => ['required_with:new_password', 'current_password'],
            'new_password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $ldap = new \LdapRecord\Connection([
                'hosts' => config('ldap.connections.default.hosts'),
                'port' => config('ldap.connections.default.port'),
                'base_dn' => config('ldap.connections.default.base_dn'),
                'username' => config('ldap.connections.default.username'),
                'password' => config('ldap.connections.default.password'),
                'use_ssl' => config('ldap.connections.default.use_ssl'),
                'use_tls' => config('ldap.connections.default.use_tls'),
                'timeout' => config('ldap.connections.default.timeout'),
                'options' => [
                    LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_NEVER,
                    LDAP_OPT_REFERRALS => 0,
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

                    // Intentar autenticar con la contraseña actual
                    $authLdap = new \LdapRecord\Connection([
                        'hosts' => config('ldap.connections.default.hosts'),
                        'port' => config('ldap.connections.default.port'),
                        'base_dn' => config('ldap.connections.default.base_dn'),
                        'username' => $ldapUser->getDn(),
                        'password' => $currentPassword,
                        'use_ssl' => config('ldap.connections.default.use_ssl'),
                        'use_tls' => config('ldap.connections.default.use_tls'),
                    ]);

                    try {
                        $authLdap->connect();
                        // Si llegamos aquí, la contraseña actual es correcta
                        $updateData['userPassword'] = $this->hashPassword($newPassword);
                    } catch (\Exception $e) {
                        return back()->withErrors(['current_password' => 'La contraseña actual es incorrecta']);
                    }
                }

                // Actualizar el usuario en LDAP
                $ldapUser->update($updateData);

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