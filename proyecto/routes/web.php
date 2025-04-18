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
});


