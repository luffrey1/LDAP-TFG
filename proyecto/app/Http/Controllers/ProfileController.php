<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use LdapRecord\Connection;
use LdapRecord\Container;
use Illuminate\Support\Facades\Log;
use LdapRecord\Models\OpenLDAP\User as LdapUser;

class ProfileController extends Controller
{
    /**
     * Muestra el formulario de edición del perfil.
     */
    public function edit()
    {
        return view('profile.edit');
    }

    /**
     * Actualiza el perfil del usuario.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ];

        // Si se proporciona una nueva contraseña, validar
        if ($request->filled('password')) {
            $rules['current_password'] = ['required', 'string', function ($attribute, $value, $fail) use ($user) {
                // Obtener configuración LDAP
                $config = Container::getDefaultConnection()->getConfiguration();
                $hosts = $config->get('hosts');
                $basedn = $config->get('base_dn');
                
                // Construir DN de usuario
                $userDn = "uid={$user->username},ou=people," . $basedn;
                
                // Crear conexión manual para el usuario
                $connection = new Connection([
                    'hosts' => $hosts,
                    'base_dn' => $basedn,
                    'username' => $userDn,
                    'password' => $value,
                    'port' => $config->get('port', 389),
                    'use_ssl' => $config->get('use_ssl', false),
                    'use_tls' => $config->get('use_tls', false),
                    'timeout' => $config->get('timeout', 5),
                    'options' => [
                        LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_NEVER,
                        LDAP_OPT_REFERRALS => 0,
                    ],
                ]);
                
                try {
                    $connection->connect();
                } catch (\Exception $e) {
                    $fail('La contraseña actual es incorrecta.');
                }
            }];
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        $validated = $request->validate($rules);

        // Actualizar datos básicos
        $user->name = $validated['name'];
        $user->email = $validated['email'];

        // Actualizar contraseña si se proporcionó una nueva
        if ($request->filled('password')) {
            try {
                // Obtener configuración LDAP
                $config = Container::getDefaultConnection()->getConfiguration();
                $hosts = $config->get('hosts');
                $basedn = $config->get('base_dn');
                
                Log::debug("Intentando actualizar contraseña para usuario: {$user->username}");
                Log::debug("Configuración LDAP: hosts=" . json_encode($hosts) . ", base_dn={$basedn}");
                
                // Buscar el usuario LDAP usando el modelo
                $ldapUser = LdapUser::where('uid', $user->username)->first();
                
                if (!$ldapUser) {
                    Log::error("Usuario no encontrado en LDAP: {$user->username}");
                    throw new \Exception("Usuario no encontrado en LDAP");
                }
                
                Log::debug("Usuario encontrado en LDAP: " . json_encode($ldapUser));
                
                // Generar hash SSHA
                $salt = random_bytes(4);
                $hash = '{SSHA}' . base64_encode(sha1($validated['password'] . $salt, true) . $salt);
                
                // Actualizar contraseña en LDAP
                $ldapUser->userPassword = $hash;
                $ldapUser->save();
                
                Log::debug("Contraseña actualizada en LDAP");
                
                // Actualizar contraseña en base de datos local
                $user->password = Hash::make($validated['password']);
                $user->save();
                
                Log::info("Contraseña actualizada exitosamente para usuario: {$user->username}");
            } catch (\Exception $e) {
                Log::error("Error al actualizar contraseña: " . $e->getMessage());
                Log::error("Traza: " . $e->getTraceAsString());
                return redirect()->route('profile.edit')
                    ->with('error', 'Error al actualizar la contraseña. Por favor, inténtelo de nuevo.');
            }
        } else {
            $user->save();
        }

        return redirect()->route('profile.edit')
            ->with('success', 'Perfil actualizado correctamente.');
    }
} 