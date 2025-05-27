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
        $ldapUid = $user->username; // Default to local username
        $ldapGuid = $user->guid; // Default to local guid
        $ldapCn = ''; // Default empty DN
        $fullName = $user->name; // Default to local name

        Log::info('ProfileController@edit: User authenticated', ['user_id' => $user->id, 'username' => $user->username]);

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
            Log::info('ProfileController@edit: LDAP connected.');

            // Buscar el usuario por su nombre de usuario
            $ldapUser = $ldap->query()
                ->in(config('ldap.connections.default.base_dn'))
                ->rawFilter('(&(objectClass=posixAccount)(uid=' . $user->username . '))')
                ->first();

            if ($ldapUser) {
                Log::info('ProfileController@edit: LDAP user found', ['dn' => $ldapUser['dn'] ?? '']);
                
                // Obtener todos los atributos para debug
                $allAttributes = $ldapUser;
                Log::info('ProfileController@edit: All LDAP attributes', ['attributes' => $allAttributes]);

                // Obtener UID numérico
                $ldapUid = $ldapUser['uidnumber'][0] ?? $ldapUser['uid'][0] ?? $user->username;
                Log::info('ProfileController@edit: Found uidNumber', ['uidNumber' => $ldapUid]);
                
                // Obtener GID numérico
                $ldapGuid = $ldapUser['gidnumber'][0] ?? $ldapUser['gid'][0] ?? $user->guid;
                Log::info('ProfileController@edit: Found gidNumber', ['gidNumber' => $ldapGuid]);
                
                // Obtener DN completo para CN
                $ldapCn = $ldapUser['dn'] ?? '';
                Log::info('ProfileController@edit: Found dn', ['dn' => $ldapCn]);

                // Obtener nombre completo
                $fullName = $ldapUser['cn'][0] ?? $user->name;
                Log::info('ProfileController@edit: Found full name', ['name' => $fullName]);

                // Update the local user model's guid
                if ($user->guid !== $ldapGuid) {
                     $user->guid = $ldapGuid;
                     $user->save();
                     Log::info('ProfileController@edit: Updated local user guid', ['new_guid' => $user->guid]);
                }

                // Obtener todos los grupos disponibles
                $allGroups = $ldap->query()
                    ->in(config('ldap.connections.default.base_dn'))
                    ->rawFilter('(|(objectclass=groupOfUniqueNames)(objectclass=posixGroup))')
                    ->get();

                Log::info('ProfileController@edit: Found ' . count($allGroups) . ' groups in LDAP.');

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
                Log::info('ProfileController@edit: User belongs to ' . count($groups) . ' LDAP groups.');

            } else {
                Log::warning('ProfileController@edit: LDAP user not found for username', ['username' => $user->username]);
            }

        } catch (\Exception $e) {
            Log::error('Error al obtener información de LDAP en ProfileController@edit: ' . $e->getMessage());
            Log::error('Trace: ' . $e->getTraceAsString());
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
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'current_password' => ['nullable', 'required_with:new_password', 'current_password'],
            'new_password' => ['nullable', 'min:8', 'confirmed'],
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (isset($validated['new_password'])) {
            $user->password = Hash::make($validated['new_password']);
        }

        $user->save();

        return redirect()->route('profile.edit')->with('status', 'profile-updated');
    }
} 