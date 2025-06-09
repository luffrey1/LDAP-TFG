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

Route::post('/telemetry/update', [\App\Http\Controllers\MonitorController::class, 'updateTelemetry']);

// Ruta para obtener el intervalo de telemetría
Route::get('/config/telemetry-interval', function () {
    $interval = \App\Models\SistemaConfig::where('clave', 'telemetria_intervalo_minutos')
        ->value('valor') ?? 60;
    return response()->json(['interval' => (int)$interval]);
});

Route::get('/ldap/groups/gid/{gid}', [\App\Http\Controllers\Admin\LdapUserController::class, 'findGroupByGid']); 