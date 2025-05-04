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
    Route::middleware(CheckModuleAccess::class.':calendario')->group(function () {
        Route::get('/calendario', [EventoController::class, 'index'])->name('dashboard.calendario');
        Route::post('/calendario/evento', [EventoController::class, 'store'])->name('dashboard.calendario.evento');
        Route::put('/calendario/evento/{id}', [EventoController::class, 'update'])->name('dashboard.calendario.actualizar');
        Route::delete('/calendario/evento/{id}', [EventoController::class, 'destroy'])->name('dashboard.calendario.eliminar');
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
    
    // Logs de actividad LDAP
    Route::get('/logs', [App\Http\Controllers\Admin\LdapUserController::class, 'logs'])->name('logs');

    // Nuevas rutas de configuración del sistema
    Route::get('/configuracion', [App\Http\Controllers\Admin\ConfiguracionController::class, 'index'])
        ->name('configuracion.index');
    Route::post('/configuracion', [App\Http\Controllers\Admin\ConfiguracionController::class, 'guardar'])
        ->name('configuracion.guardar');
});


