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
use App\Http\Controllers\SshTerminalController;
use App\Http\Controllers\WebSocketController;
use App\Events\TestBroadcast;

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
Route::get('/test-broadcast', function () {
    broadcast(new TestBroadcast('¡Funciona el WebSocket!'));
    return 'Evento emitido';
});

// Rutas protegidas que requieren autenticación
Route::middleware(['web', 'App\Http\Middleware\LdapAuthMiddleware'])->group(function () {
    // Dashboard principal
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    
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
        Route::post('/enviar', [MensajeController::class, 'enviar'])->name('dashboard.mensajes.enviar');
        Route::get('/{id}', [MensajeController::class, 'ver'])->name('dashboard.mensajes.ver');
        Route::delete('/{id}', [MensajeController::class, 'eliminar'])->name('dashboard.mensajes.eliminar');
        Route::post('/{id}/restaurar', [MensajeController::class, 'restore'])->name('dashboard.mensajes.restaurar');
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
        Route::get('/ping/{id}', [MonitorController::class, 'ping'])->name('ping');
        Route::get('/ping-all', [MonitorController::class, 'pingAll'])->name('ping-all');
        Route::get('/refresh-network', [MonitorController::class, 'refreshNetworkDevices'])->name('refresh-network');
        Route::get('/scan', [MonitorController::class, 'scanNetworkForm'])->name('scan');
        Route::post('/scan', [MonitorController::class, 'scanNetwork'])->name('scan.execute');
        Route::post('/check-network', [MonitorController::class, 'checkNetwork'])->name('check-network');
        Route::post('/update-status', [MonitorController::class, 'updateStatus'])->name('update-status');
        Route::post('/update-system-info', [MonitorController::class, 'updateSystemInfo'])->name('update-system-info');
        Route::post('/update-telemetry', [MonitorController::class, 'updateTelemetry'])->name('update-telemetry');
        Route::post('/{id}/command', [MonitorController::class, 'executeCommand'])->name('execute-command');
        
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
    });
    
    // Ruta para gestión de usuarios LDAP - acceso directo a admin users
    Route::get('/usuarios', [App\Http\Controllers\Admin\LdapUserController::class, 'index'])->name('ldap.users.index');
});

// Rutas de administración de usuarios LDAP (solo para administradores)
Route::middleware(['web', 'App\Http\Middleware\LdapAuthMiddleware', 'App\Http\Middleware\AdminMiddleware'])->prefix('admin')->name('admin.')->group(function () {
    // Gestión de usuarios LDAP - asegurando que los nombres de rutas sumen admin.users.* correctamente
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
    
    // Ruta para reparar usuario (UID no codificado)
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

// Endpoint para recibir actualizaciones de telemetría desde agentes
Route::post('/api/telemetry/update', [MonitorController::class, 'updateTelemetry'])->name('api.telemetry.update');

// Rutas para la gestión de scripts
Route::get('/monitor/scripts/available', [MonitorController::class, 'getAvailableScripts'])->name('monitor.scripts.available');
Route::post('/monitor/scripts/transfer', [MonitorController::class, 'transferScript'])->name('monitor.scripts.transfer');

// Rutas para terminal SSH - definidas directamente sin usar prefix para evitar problemas de ruta
Route::post('/api/terminal/connect', [SshTerminalController::class, 'connect'])
    ->middleware(['web', 'App\Http\Middleware\LdapAuthMiddleware'])
    ->name('ssh.connect');
Route::post('/api/terminal/disconnect', [SshTerminalController::class, 'disconnect'])
    ->middleware(['web', 'App\Http\Middleware\LdapAuthMiddleware'])
    ->name('ssh.disconnect');
Route::post('/api/terminal/send', [SshTerminalController::class, 'execute'])
    ->middleware(['web', 'App\Http\Middleware\LdapAuthMiddleware'])
    ->name('ssh.send');

// Rutas para WebSocket
Route::prefix('api/websocket')->middleware(['web', 'App\Http\Middleware\LdapAuthMiddleware'])->group(function () {
    Route::get('/status', [WebSocketController::class, 'checkStatus'])->name('websocket.status');
    Route::post('/start', [WebSocketController::class, 'startServer'])->name('websocket.start');
    Route::post('/command', [WebSocketController::class, 'receiveCommand'])->name('websocket.command');
});

// Ruta WebSocket para SSH (usada por el proxy WebSocket)
Route::get('/ssh-terminal/{sessionId}', function() {
    return response()->json([
        'error' => 'Esta ruta debe ser accedida a través de una conexión WebSocket, no HTTP'
    ], 400);
})->middleware(['App\Http\Middleware\WebSocketAuthentication']);

// Ruta para debug desde el frontend
Route::post('/api/debug/log', function(\Illuminate\Http\Request $request) {
    $message = $request->input('message');
    $level = $request->input('level', 'info');
    
    \Illuminate\Support\Facades\Log::$level("DEBUG-FRONTEND: " . $message);
    
    return response()->json(['success' => true]);
});

// Routes for SSH Terminal
// Route::post('/terminal/send', [App\Http\Controllers\MonitorController::class, 'terminalSend'])->name('terminal.send');


