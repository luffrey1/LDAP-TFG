<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LdapUserController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\MensajeController;
use App\Http\Controllers\EventoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TestController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

// Ruta principal redirige al login
Route::get('/', function () {
    return redirect()->route('login');
});

// Rutas de autenticación
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');

// Ruta de prueba directo al controlador de documentos
Route::get('/test-documentos', [DocumentoController::class, 'index'])->name('test.documentos');
Route::get('/test-controller', [TestController::class, 'test'])->name('test.controller');



// Rutas protegidas que requieren autenticación
Route::middleware(['web', 'App\Http\Middleware\LdapAuthMiddleware'])->group(function () {
    // Dashboard principal
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    
    // Gestión documental
    Route::get('/gestion-documental', [DocumentoController::class, 'index'])->name('dashboard.gestion-documental');
    Route::post('/gestion-documental/subir', [DocumentoController::class, 'store'])->name('dashboard.gestion-documental.subir');
    Route::get('/gestion-documental/descargar/{id}', [DocumentoController::class, 'download'])->name('dashboard.gestion-documental.descargar');
    Route::get('/gestion-documental/{id}', [DocumentoController::class, 'show'])->name('dashboard.gestion-documental.ver');
    Route::delete('/gestion-documental/{id}', [DocumentoController::class, 'destroy'])->name('dashboard.gestion-documental.eliminar');
    
    // Mensajería interna
    Route::get('/mensajes', [MensajeController::class, 'index'])->name('dashboard.mensajes');
    Route::get('/mensajes/nuevo', [MensajeController::class, 'create'])->name('dashboard.mensajes.nuevo');
    Route::post('/mensajes/enviar', [MensajeController::class, 'store'])->name('dashboard.mensajes.enviar');
    Route::get('/mensajes/{id}', [MensajeController::class, 'show'])->name('dashboard.mensajes.ver');
    Route::delete('/mensajes/{id}', [MensajeController::class, 'destroy'])->name('dashboard.mensajes.eliminar');
    
    // Calendario y eventos
    Route::get('/calendario', [EventoController::class, 'index'])->name('dashboard.calendario');
    Route::post('/calendario/evento', [EventoController::class, 'store'])->name('dashboard.calendario.evento');
    Route::put('/calendario/evento/{id}', [EventoController::class, 'update'])->name('dashboard.calendario.actualizar');
    Route::delete('/calendario/evento/{id}', [EventoController::class, 'destroy'])->name('dashboard.calendario.eliminar');
});

// Rutas de administración de usuarios LDAP (solo para administradores)
Route::middleware(['web', 'App\Http\Middleware\LdapAuthMiddleware', 'App\Http\Middleware\AdminMiddleware'])->prefix('admin')->name('admin.')->group(function () {
    // Gestión de usuarios LDAP
    Route::get('/usuarios', [App\Http\Controllers\Admin\LdapUserController::class, 'index'])->name('users.index');
    Route::get('/usuarios/crear', [App\Http\Controllers\Admin\LdapUserController::class, 'create'])->name('users.create');
    Route::post('/usuarios', [App\Http\Controllers\Admin\LdapUserController::class, 'store'])->name('users.store');
    Route::get('/usuarios/{dn}', [App\Http\Controllers\Admin\LdapUserController::class, 'show'])->name('users.show');
    Route::get('/usuarios/{dn}/editar', [App\Http\Controllers\Admin\LdapUserController::class, 'edit'])->name('users.edit');
    Route::put('/usuarios/{dn}', [App\Http\Controllers\Admin\LdapUserController::class, 'update'])->name('users.update');
    Route::delete('/usuarios/{dn}', [App\Http\Controllers\Admin\LdapUserController::class, 'destroy'])->name('users.destroy');
    Route::post('/usuarios/{dn}/toggle-admin', [App\Http\Controllers\Admin\LdapUserController::class, 'toggleAdmin'])->name('users.toggle-admin');
    Route::post('/usuarios/{dn}/reset-password', [App\Http\Controllers\Admin\LdapUserController::class, 'resetPassword'])->name('users.reset-password');
    Route::get('/usuarios-exportar', [App\Http\Controllers\Admin\LdapUserController::class, 'exportExcel'])->name('users.export');
    
    // Logs de actividad LDAP
    Route::get('/logs', [App\Http\Controllers\Admin\LdapUserController::class, 'logs'])->name('logs');
});

// Ruta de prueba LDAP
Route::get('/ldap-test', function () {
    try {
        // Crear una conexión LDAP directamente usando la configuración del archivo de configuración
        $config = config('ldap.connections.default');
        
        $connection = new \LdapRecord\Connection($config);
        $connection->connect();
        
        if($connection->isConnected()) {
            $output = 'Conexión LDAP exitosa' . PHP_EOL;
            $query = $connection->query();
            
            // Buscar cualquier objeto en LDAP
            $results = $query->get();
            $output .= 'Objetos encontrados: ' . count($results) . PHP_EOL;
            
            foreach ($results as $object) {
                $output .= "- DN: " . $object['dn'] . PHP_EOL;
                
                if (isset($object['objectclass'])) {
                    $output .= "  Clases: " . implode(', ', $object['objectclass']) . PHP_EOL;
                }
                
                if (isset($object['cn'])) {
                    $output .= "  CN: " . implode(', ', $object['cn']) . PHP_EOL;
                }
            }
            
            // Buscar personas
            $personResults = $query->where('objectClass', 'person')->get();
            $output .= PHP_EOL . 'Personas encontradas: ' . count($personResults) . PHP_EOL;
            
            return nl2br($output);
        }
        
        return 'No se pudo conectar a LDAP';
    } catch (\Exception $e) {
        return 'Error de conexión LDAP: ' . $e->getMessage();
    }
});

Route::get('/check-key', function () {
    return env('APP_KEY');
});

// Ruta para diagnosticar problemas de permisos en el directorio de documentos
Route::get('/check-permissions', function() {
    $result = [
        'root_public' => is_writable(public_path()),
        'documentos_dir' => is_writable(public_path('documentos')),
        'php_user' => get_current_user(),
        'php_version' => phpversion(),
        'directories' => []
    ];
    
    $folders = ['general', 'programaciones', 'actas', 'horarios'];
    foreach ($folders as $folder) {
        $path = public_path('documentos/' . $folder);
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $result['directories'][$folder] = [
            'exists' => file_exists($path),
            'writable' => is_writable($path),
            'permissions' => substr(sprintf('%o', fileperms($path)), -4)
        ];
        
        // Intentar crear un archivo de prueba
        $testFile = $path . '/test.txt';
        $writeTest = @file_put_contents($testFile, 'Test write access');
        $result['directories'][$folder]['write_test'] = ($writeTest !== false);
        
        if ($writeTest !== false) {
            @unlink($testFile);
        }
    }
    
    return response()->json($result);
});

// Ruta para depuración de sesión
Route::get('/debug-session', function() {
    return view('debug_session');
})->name('debug.session');

// Ruta de prueba para verificar autenticación LDAP directamente
Route::get('/test-ldap-auth', function () {
    // Configurar conexión LDAP
    $ldapConfig = [
        'hosts' => [env('LDAP_HOST', 'openldap-osixia')],
        'port' => env('LDAP_PORT', 389),
        'base_dn' => env('LDAP_BASE_DN', 'dc=test,dc=tierno,dc=es'),
        'username' => env('LDAP_USERNAME', 'cn=admin,dc=test,dc=tierno,dc=es'),
        'password' => env('LDAP_PASSWORD', 'admin'),
        'use_ssl' => false,
        'use_tls' => false,
        'timeout' => 5,
        'options' => [
            LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_NEVER,
            LDAP_OPT_REFERRALS => 0,
        ],
    ];
    
    $username = 'ldap-admin';
    $password = 'admin';
    $userDn = "uid={$username},ou=people," . $ldapConfig['base_dn'];
    
    // Resultado final
    $result = [
        'success' => false,
        'native_php' => false,
        'ldaprecord' => false,
        'errors' => [],
        'user_info' => null
    ];
    
    // 1. Probar autenticación nativa PHP
    try {
        $ldapConn = ldap_connect($ldapConfig['hosts'][0], $ldapConfig['port']);
        ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);
        
        $bindResult = @ldap_bind($ldapConn, $userDn, $password);
        if ($bindResult) {
            $result['native_php'] = true;
        } else {
            $result['errors'][] = "Error PHP nativo: " . ldap_error($ldapConn);
        }
        
        // Buscar información del usuario
        if ($bindResult) {
            // Primero reconectar como admin para buscar
            ldap_unbind($ldapConn);
            $ldapConn = ldap_connect($ldapConfig['hosts'][0], $ldapConfig['port']);
            ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($ldapConn, LDAP_OPT_REFERRALS, 0);
            ldap_bind($ldapConn, $ldapConfig['username'], $ldapConfig['password']);
            
            // Buscar usuario
            $searchResult = ldap_search($ldapConn, $ldapConfig['base_dn'], "(uid=$username)");
            $entries = ldap_get_entries($ldapConn, $searchResult);
            
            if ($entries['count'] > 0) {
                $result['user_info'] = [
                    'dn' => $entries[0]['dn'],
                    'cn' => $entries[0]['cn'][0] ?? 'No disponible',
                    'uid' => $entries[0]['uid'][0] ?? 'No disponible',
                    'groups' => []
                ];
                
                // Buscar grupos
                $groupSearchResult = ldap_search($ldapConn, "ou=groups," . $ldapConfig['base_dn'], "(&(objectClass=posixGroup)(memberUid=$username))");
                $groups = ldap_get_entries($ldapConn, $groupSearchResult);
                
                for ($i = 0; $i < $groups['count']; $i++) {
                    $result['user_info']['groups'][] = $groups[$i]['cn'][0] ?? 'Grupo sin nombre';
                }
            }
        }
        
        ldap_unbind($ldapConn);
    } catch (\Exception $e) {
        $result['errors'][] = "Excepción PHP nativo: " . $e->getMessage();
    }
    
    // 2. Probar con LdapRecord
    try {
        $ldap = new LdapRecord\Connection($ldapConfig);
        $ldap->connect();
        
        if ($ldap->auth()->attempt($userDn, $password)) {
            $result['ldaprecord'] = true;
        } else {
            $result['errors'][] = "Error LdapRecord: autenticación fallida";
        }
    } catch (\Exception $e) {
        $result['errors'][] = "Excepción LdapRecord: " . $e->getMessage();
    }
    
    $result['success'] = $result['native_php'] || $result['ldaprecord'];
    
    return response()->json($result);
});