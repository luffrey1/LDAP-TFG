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
        $ldapGuid = $user->guid;
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
                ->in(config('ldap.connections.default.base_dn'))
                ->rawFilter('(&(objectClass=posixAccount)(uid=' . $user->username . '))')
                ->first();

            if ($ldapUser) {
                $ldapUid = $ldapUser['uidnumber'][0] ?? $ldapUser['uid'][0] ?? $user->username;
                $ldapGuid = $ldapUser['gidnumber'][0] ?? $ldapUser['gid'][0] ?? $user->guid;
                $ldapCn = $ldapUser['dn'] ?? '';
                $fullName = $ldapUser['cn'][0] ?? $user->name;

                // Update the local user model's guid
                if ($user->guid !== $ldapGuid) {
                     $user->guid = $ldapGuid;
                     $user->save();
                }

                // Obtener todos los grupos disponibles
                $allGroups = $ldap->query()
                    ->in(config('ldap.connections.default.base_dn'))
                    ->rawFilter('(|(objectclass=groupOfUniqueNames)(objectclass=posixGroup))')
                    ->get();

                foreach ($allGroups as $group) {
                    $isMember = false;

                    // Verificar si el grupo tiene miembros (groupOfUniqueNames)
                    if (isset($group['uniquemember'])) {
                        $members = is_array($group['uniquemember']) 
                            ? $group['uniquemember'] 
                            : [$group['uniquemember']];
                        
                        if (in_array($ldapUser['dn'], $members)) {
                            $isMember = true;
                        }
                    }

                    // Verificar memberUid para posixGroup
                    if (isset($group['memberuid'])) {
                        $memberUids = is_array($group['memberuid']) 
                            ? $group['memberuid'] 
                            : [$group['memberuid']];
                        
                        if (in_array($ldapUid, $memberUids)) {
                            $isMember = true;
                        }
                    }

                    // Si el usuario es miembro, añadir el grupo
                    if ($isMember) {
                        $groups[] = (object)[
                            'nombre_completo' => $group['cn'][0] ?? '',
                            'gid' => $group['gidnumber'][0] ?? '',
                            'description' => $group['description'][0] ?? ''
                        ];
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('Error al obtener información de LDAP en ProfileController@edit: ' . $e->getMessage());
        }

        return view('profile.edit', [
            'user' => $user,
            'groups' => $groups,
            'ldapUid' => $ldapUid,
            'ldapGuid' => $ldapGuid,
            'ldapCn' => $ldapCn,
            'fullName' => $fullName
        ]);
    }

    /**
     * Actualiza el perfil del usuario.
     */
    public function update(Request $request)
    {
        $user = $request->user();
        
        // Validar los datos del formulario
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'current_password' => ['required_with:new_password'],
            'new_password' => ['nullable', 'min:8', 'confirmed'],
        ]);

        try {
            // Conectar a LDAP
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
                ->in(config('ldap.connections.default.base_dn'))
                ->rawFilter('(&(objectClass=posixAccount)(uid=' . $user->username . '))')
                ->first();

            if (!$ldapUser) {
                throw new \Exception('Usuario no encontrado en LDAP');
            }

            // Si se está cambiando la contraseña
            if (!empty($validated['new_password'])) {
                // Verificar la contraseña actual
                $authLdap = new \LdapRecord\Connection([
                    'hosts' => config('ldap.connections.default.hosts'),
                    'port' => config('ldap.connections.default.port'),
                    'base_dn' => config('ldap.connections.default.base_dn'),
                    'username' => $ldapUser['dn'],
                    'password' => $validated['current_password'],
                    'use_ssl' => config('ldap.connections.default.use_ssl'),
                    'use_tls' => config('ldap.connections.default.use_tls'),
                ]);

                try {
                    $authLdap->connect();
                } catch (\Exception $e) {
                    return back()->withErrors(['current_password' => 'La contraseña actual es incorrecta']);
                }

                // Cambiar la contraseña en LDAP
                $ldapUser->userPassword = $validated['new_password'];
                $ldapUser->save();

                // Actualizar la contraseña en la base de datos local
                $user->password = Hash::make($validated['new_password']);
            }

            // Actualizar el nombre y email en LDAP si es necesario
            if ($ldapUser['cn'][0] !== $validated['name']) {
                $ldapUser->cn = $validated['name'];
                $ldapUser->save();
            }

            // Actualizar el usuario local
            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->save();

            return redirect()->route('profile.edit')
                ->with('status', 'Perfil actualizado correctamente');

        } catch (\Exception $e) {
            Log::error('Error al actualizar perfil en LDAP: ' . $e->getMessage());
            return back()->withErrors(['error' => 'Error al actualizar el perfil: ' . $e->getMessage()]);
        }
    }
} 