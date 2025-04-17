<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MensajesController;

Route::middleware(['auth', 'ldap'])->group(function () {
    // Listado de mensajes
    Route::get('/mensajes', [MensajesController::class, 'index'])->name('mensajes.index');
    
    // Crear nuevo mensaje
    Route::get('/mensajes/nuevo', [MensajesController::class, 'create'])->name('mensajes.create');
    Route::post('/mensajes', [MensajesController::class, 'store'])->name('mensajes.store');
    
    // Ver mensaje
    Route::get('/mensajes/{id}', [MensajesController::class, 'show'])->name('mensajes.show');
    
    // Responder mensaje
    Route::post('/mensajes/{id}/responder', [MensajesController::class, 'reply'])->name('mensajes.reply');
    
    // Reenviar mensaje
    Route::post('/mensajes/{id}/reenviar', [MensajesController::class, 'forward'])->name('mensajes.forward');
    
    // Eliminar mensaje
    Route::delete('/mensajes/{id}', [MensajesController::class, 'destroy'])->name('mensajes.destroy');
    
    // Marcar mensaje como leído
    Route::patch('/mensajes/{id}/leido', [MensajesController::class, 'markAsRead'])->name('mensajes.mark-read');
    
    // Marcar mensaje como destacado
    Route::patch('/mensajes/{id}/destacado', [MensajesController::class, 'toggleStarred'])->name('mensajes.toggle-starred');
    
    // Mover mensaje a papelera
    Route::patch('/mensajes/{id}/papelera', [MensajesController::class, 'moveToTrash'])->name('mensajes.trash');
    
    // Restaurar mensaje de papelera
    Route::patch('/mensajes/{id}/restaurar', [MensajesController::class, 'restore'])->name('mensajes.restore');
}); 