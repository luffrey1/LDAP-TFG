<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Rutas para el terminal SSH
Route::prefix('terminal')->middleware('auth:sanctum')->group(function () {
    Route::post('/connect', [App\Http\Controllers\SshTerminalController::class, 'connect']);
    Route::post('/send', [App\Http\Controllers\SshTerminalController::class, 'execute']);
    Route::post('/disconnect', [App\Http\Controllers\SshTerminalController::class, 'disconnect']);
}); 