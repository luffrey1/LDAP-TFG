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
use App\Http\Middleware\CheckModuleAccess;
use App\Http\Controllers\MonitorController;
use App\Events\TestBroadcast;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LdapGroupController;
use Illuminate\Support\Facades\Http;

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
    
    // --- RUTAS AJAX DE DETECCIÓN DE HOST ---
    Route::post('/monitor/detect-host', [App\Http\Controllers\MonitorController::class, 'detectHost'])->name('monitor.detect-host');
    Route::get('/monitor/detect-host', function() {
        abort(404, 'Este endpoint solo acepta peticiones POST AJAX.');
    });
    
    // Rutas del perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    
    // Gestión documental
    Route::middleware(CheckModuleAccess::class.':documentos')->group(function () {
        Route::get('/gestion-documental', [DocumentoController::class, 'index'])->name('dashboard.gestion-documental');
        Route::post('/gestion-documental/subir', [DocumentoController::class, 'store'])->name('dashboard.gestion-documental.subir');
        Route::get('/gestion-documental/descargar/{id}', [DocumentoController::class, 'download'])->name('dashboard.gestion-documental.descargar');
        Route::get('/gestion-documental/{id}', [DocumentoController::class, 'show'])->name('dashboard.gestion-documental.ver');
        Route::delete('/gestion-documental/{id}', [DocumentoController::class, 'destroy'])->name('dashboard.gestion-documental.eliminar');
    });
    
    // Mensajería interna
    Route::prefix('mensajes')->middleware(CheckModuleAccess::class.':mensajeria')->group(function () {
        Route::get('/', [MensajeController::class, 'index'])->name('dashboard.mensajes');
        Route::get('/nuevo', [MensajeController::class, 'create'])->name('dashboard.mensajes.nuevo');
        Route::post('/enviar', [MensajeController::class, 'store'])->name('dashboard.mensajes.enviar');
        Route::get('/{id}', [MensajeController::class, 'show'])->name('dashboard.mensajes.ver');
        Route::delete('/{id}', [MensajeController::class, 'destroy'])->name('dashboard.mensajes.eliminar');
        Route::post('/{id}/restaurar', [MensajeController::class, 'restore'])->name('dashboard.mensajes.restaurar');
        Route::post('/{id}/destacar', [MensajeController::class, 'toggleStarred'])->name('dashboard.mensajes.destacar');
        Route::post('/{id}/responder', [MensajeController::class, 'reply'])->name('dashboard.mensajes.responder');
        Route::post('/{id}/reenviar', [MensajeController::class, 'forward'])->name('dashboard.mensajes.reenviar');
    });
    
    // Calendario y eventos
    Route::prefix('calendario')->middleware(CheckModuleAccess::class.':calendario')->group(function () {
        Route::get('/', [EventoController::class, 'index'])->name('dashboard.calendario');
        Route::post('/evento', [EventoController::class, 'store'])->name('dashboard.calendario.evento');
        Route::put('/evento/{id}', [EventoController::class, 'update'])->name('dashboard.calendario.evento.update');
        Route::delete('/evento/{id}', [EventoController::class, 'destroy'])->name('dashboard.calendario.eliminar');
        Route::get('/eventos', [EventoController::class, 'getEvents'])->name('eventos.get');
    });
    
    // Monitoreo de Equipos
    Route::prefix('monitor')->name('monitor.')->middleware(CheckModuleAccess::class.':monitoreo')->group(function () {
        Route::get('/', [MonitorController::class, 'index'])->name('index');
        Route::get('/create', [MonitorController::class, 'create'])->name('create');
        Route::post('/', [MonitorController::class, 'store'])->name('store');
        Route::get('/{id}', [MonitorController::class, 'show'])->name('show')->where('id', '[0-9]+');
        Route::get('/{id}/edit', [MonitorController::class, 'edit'])->name('edit');
        Route::put('/{id}', [MonitorController::class, 'update'])->name('update');
        Route::delete('/{id}', [MonitorController::class, 'destroy'])->name('destroy');
        Route::post('/ping/{id}', [MonitorController::class, 'ping'])->name('ping');
        Route::post('/ping-all', [MonitorController::class, 'pingAll'])->name('ping-all');
        Route::get('/refresh-network', [MonitorController::class, 'refreshNetworkDevices'])->name('refresh-network');
        Route::get('/scan', [MonitorController::class, 'scanNetworkForm'])->name('scan');
        Route::post('/scan', [MonitorController::class, 'scanNetwork'])->name('scan.execute');
        Route::post('/check-network', [MonitorController::class, 'checkNetwork'])->name('check-network');
        Route::post('/update-status', [MonitorController::class, 'updateStatus'])->name('update-status');
        Route::post('/update-system-info', [MonitorController::class, 'updateSystemInfo'])->name('update-system-info');
        Route::post('/update-telemetry', [MonitorController::class, 'updateTelemetry'])->name('update-telemetry');
        Route::post('/{id}/command', [MonitorController::class, 'executeCommand'])->name('execute-command');
        
        // Ruta del proxy de telemetría
        Route::get('/api/telemetry-proxy/{host}', function ($host) {
            try {
                $response = Http::timeout(5)->get("http://{$host}:5001/telemetry");
                return $response->json();
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error al conectar con el agente: ' . $e->getMessage()
                ], 500);
            }
        })->name('telemetry-proxy');
        
        // Nuevas rutas para gestión de scripts
        Route::get('/scripts/available', [MonitorController::class, 'getAvailableScripts'])->name('scripts.available');
        Route::post('/scripts/transfer', [MonitorController::class, 'transferScript'])->name('scripts.transfer');
        
        // Nuevas rutas de Wake-on-LAN
        Route::get('/{id}/wol', [MonitorController::class, 'wakeOnLan'])->name('wol');
        
        // Rutas de grupos
        Route::get('/groups', [MonitorController::class, 'groupsIndex'])->name('groups.index');
        Route::get('/groups/create', [MonitorController::class, 'createGroup'])->name('groups.create');
        Route::post('/groups', [MonitorController::class, 'storeGroup'])->name('groups.store');
        Route::get('/groups/{id}', [MonitorController::class, 'showGroup'])->name('groups.show');
        Route::get('/groups/{id}/edit', [MonitorController::class, 'editGroup'])->name('groups.edit');
        Route::put('/groups/{id}', [MonitorController::class, 'updateGroup'])->name('groups.update');
        Route::delete('/groups/{id}', [MonitorController::class, 'destroyGroup'])->name('groups.destroy');
        Route::get('/groups/{id}/wol', [MonitorController::class, 'wakeOnLanGroup'])->name('groups.wol');
        Route::post('/group/{id}/clean', [MonitorController::class, 'deleteAllHostsInGroup'])
            ->name('group.clean');
    });
    
    // Gestión de usuarios LDAP - Accesible para todos los usuarios
    Route::prefix('gestion/usuarios')->name('admin.users.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\LdapUserController::class, 'index'])->name('index');
        Route::get('/crear', [App\Http\Controllers\Admin\LdapUserController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\LdapUserController::class, 'store'])->name('store');
        Route::get('/{dn}', [App\Http\Controllers\Admin\LdapUserController::class, 'show'])->name('show');
        Route::get('/{dn}/editar', [App\Http\Controllers\Admin\LdapUserController::class, 'edit'])->name('edit');
        Route::put('/{dn}', [App\Http\Controllers\Admin\LdapUserController::class, 'update'])->name('update');
        Route::delete('/{dn}', [App\Http\Controllers\Admin\LdapUserController::class, 'destroy'])->name('destroy');
        Route::post('/{dn}/reset-password', [App\Http\Controllers\Admin\LdapUserController::class, 'resetPassword'])->name('reset-password');
    });

    Route::prefix('admin/groups')->name('admin.groups.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\LdapGroupController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\Admin\LdapGroupController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\Admin\LdapGroupController::class, 'store'])->name('store');
        Route::get('/{cn}/edit', [App\Http\Controllers\Admin\LdapGroupController::class, 'edit'])->name('edit');
        Route::put('/{cn}', [App\Http\Controllers\Admin\LdapGroupController::class, 'update'])->name('update');
        Route::delete('/{cn}', [App\Http\Controllers\Admin\LdapGroupController::class, 'destroy'])->name('destroy');
    });
});

// Rutas que requieren permisos de administrador
Route::middleware(['web', 'App\Http\Middleware\LdapAuthMiddleware', 'App\Http\Middleware\AdminMiddleware'])->prefix('admin')->name('admin.')->group(function () {
    // Operaciones exclusivas para administradores de usuarios LDAP
    Route::post('/usuarios/{dn}/toggle-admin', [App\Http\Controllers\Admin\LdapUserController::class, 'toggleAdmin'])->name('users.toggle-admin');
    Route::get('/usuarios-exportar', [App\Http\Controllers\Admin\LdapUserController::class, 'exportExcel'])->name('users.export');
    Route::post('/usuarios/reparar/{uid}', [App\Http\Controllers\Admin\LdapUserController::class, 'repairUser'])->name('users.repair');
    
    // Logs de actividad LDAP
    Route::get('/logs', [App\Http\Controllers\Admin\LdapUserController::class, 'logs'])->name('logs');

    // Nuevas rutas de configuración del sistema
    Route::get('/configuracion', [App\Http\Controllers\Admin\ConfiguracionController::class, 'index'])
        ->name('configuracion.index');
    Route::post('/configuracion', [App\Http\Controllers\Admin\ConfiguracionController::class, 'guardar'])
        ->name('configuracion.guardar');
});

// Rutas para la gestión de alumnos
Route::middleware(['App\Http\Middleware\LdapAuthMiddleware'])->prefix('profesor')->name('profesor.')->group(function () {
    // Rutas para gestión de clases
    Route::get('clases', [App\Http\Controllers\Profesor\ClaseController::class, 'index'])->name('clases.index');
    Route::get('clases/create', [App\Http\Controllers\Profesor\ClaseController::class, 'create'])->name('clases.create');
    Route::post('clases', [App\Http\Controllers\Profesor\ClaseController::class, 'store'])->name('clases.store');
    Route::get('clases/{id}', [App\Http\Controllers\Profesor\ClaseController::class, 'show'])->name('clases.show');
    Route::get('clases/{id}/edit', [App\Http\Controllers\Profesor\ClaseController::class, 'edit'])->name('clases.edit');
    Route::put('clases/{id}', [App\Http\Controllers\Profesor\ClaseController::class, 'update'])->name('clases.update');
    Route::delete('clases/{id}', [App\Http\Controllers\Profesor\ClaseController::class, 'destroy'])->name('clases.destroy');
    
    // Rutas para clases de tutores
    Route::get('mis-clases', [App\Http\Controllers\Profesor\ClaseController::class, 'misClases'])->name('clases.mias');
    Route::get('mis-clases/{id}', [App\Http\Controllers\Profesor\ClaseController::class, 'verMiClase'])->name('clases.mias.ver');
    
    // Rutas para gestión de alumnos
    Route::get('alumnos', [App\Http\Controllers\Profesor\AlumnoController::class, 'index'])->name('alumnos.index');
    Route::get('alumnos/create', [App\Http\Controllers\Profesor\AlumnoController::class, 'create'])->name('alumnos.create');
    Route::post('alumnos', [App\Http\Controllers\Profesor\AlumnoController::class, 'store'])->name('alumnos.store');
    Route::get('alumnos/{id}', [App\Http\Controllers\Profesor\AlumnoController::class, 'show'])->name('alumnos.show');
    Route::get('alumnos/{id}/edit', [App\Http\Controllers\Profesor\AlumnoController::class, 'edit'])->name('alumnos.edit');
    Route::put('alumnos/{id}', [App\Http\Controllers\Profesor\AlumnoController::class, 'update'])->name('alumnos.update');
    Route::delete('alumnos/{id}', [App\Http\Controllers\Profesor\AlumnoController::class, 'destroy'])->name('alumnos.destroy');
    Route::get('alumnos/{id}/actividades', [App\Http\Controllers\Profesor\AlumnoController::class, 'actividades'])->name('alumnos.actividades');
    
    // Rutas para importar alumnos
    Route::get('alumnos/import/form', [App\Http\Controllers\Profesor\AlumnoController::class, 'importForm'])->name('alumnos.import');
    Route::post('alumnos/import/process', [App\Http\Controllers\Profesor\AlumnoController::class, 'importProcess'])->name('alumnos.import.process');
    Route::get('alumnos/import/template', [App\Http\Controllers\Profesor\AlumnoController::class, 'downloadTemplate'])->name('alumnos.template');
    
    // Rutas para LDAP alumnos
    Route::get('alumnos/ldap/buscar', [App\Http\Controllers\Profesor\AlumnoController::class, 'buscarAlumnosLdap'])->name('alumnos.ldap.buscar');
    Route::post('alumnos/ldap/importar', [App\Http\Controllers\Profesor\AlumnoController::class, 'importarAlumnosLdap'])->name('alumnos.ldap.importar');
});

// Rutas para la gestión de scripts
Route::get('/monitor/scripts/available', [MonitorController::class, 'getAvailableScripts'])->name('monitor.scripts.available');
Route::post('/monitor/scripts/transfer', [MonitorController::class, 'transferScript'])->name('monitor.scripts.transfer');


// Ruta para debug desde el frontend
Route::post('/api/debug/log', function(\Illuminate\Http\Request $request) {
    $message = $request->input('message');
    $level = $request->input('level', 'info');
    
    \Illuminate\Support\Facades\Log::$level("DEBUG-FRONTEND: " . $message);
    
    return response()->json(['success' => true]);
});

// Routes for SSH Terminal
// Route::post('/terminal/send', [App\Http\Controllers\MonitorController::class, 'terminalSend'])->name('terminal.send');

Route::get('/monitor/estado-global', [App\Http\Controllers\MonitorController::class, 'healthStatus'])->name('monitor.health');


