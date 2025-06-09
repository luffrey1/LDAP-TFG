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

Route::get('/ldap/groups/gid/{gid}', function ($gid) {
    try {
        $ldap = new \LdapRecord\Connection([
            'hosts'    => [env('LDAP_HOST', 'ldap')],
            'port'     => env('LDAP_PORT', 636),
            'base_dn'  => env('LDAP_BASE_DN', 'dc=tierno,dc=es'),
            'username' => env('LDAP_USERNAME', 'cn=admin,dc=tierno,dc=es'),
            'password' => env('LDAP_PASSWORD', 'admin'),
            'use_ssl'  => env('LDAP_SSL', true),
            'use_tls'  => env('LDAP_TLS', true),
        ]);

        $ldap->connect();

        $query = $ldap->query();
        $groups = $query->where('objectclass', '=', 'posixGroup')
                       ->where('gidNumber', '=', $gid)
                       ->get();

        if ($groups->count() > 0) {
            $group = $groups->first();
            return response()->json([
                'success' => true,
                'group' => $group->getFirstAttribute('cn')
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No se encontró ningún grupo con ese GID'
        ]);

    } catch (\Exception $e) {
        \Log::error('Error al buscar grupo por GID: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error al buscar el grupo: ' . $e->getMessage()
        ], 500);
    }
}); 