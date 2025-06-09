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
        $config = [
            'hosts'    => [env('LDAP_HOST', 'ldap')],
            'port'     => env('LDAP_PORT', 636),
            'base_dn'  => env('LDAP_BASE_DN', 'dc=tierno,dc=es'),
            'username' => env('LDAP_USERNAME', 'cn=admin,dc=tierno,dc=es'),
            'password' => env('LDAP_PASSWORD', 'admin'),
            'use_ssl'  => env('LDAP_SSL', true),
            'use_tls'  => env('LDAP_TLS', true),
        ];

        \Log::info('Intentando conectar a LDAP con configuración:', $config);

        $ldap = new \LdapRecord\Connection($config);
        
        try {
            $ldap->connect();
            \Log::info('Conexión LDAP establecida correctamente');
        } catch (\Exception $e) {
            \Log::error('Error al conectar con LDAP: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al conectar con el servidor LDAP'
            ], 500);
        }

        try {
            $query = $ldap->query();
            \Log::info('Buscando grupo con GID: ' . $gid);
            
            $groups = $query->where('objectclass', '=', 'posixGroup')
                           ->where('gidNumber', '=', $gid)
                           ->get();

            \Log::info('Resultados de la búsqueda:', ['count' => $groups->count()]);

            if ($groups->count() > 0) {
                $group = $groups->first();
                $groupName = $group->getFirstAttribute('cn');
                \Log::info('Grupo encontrado:', ['name' => $groupName]);
                
                return response()->json([
                    'success' => true,
                    'group' => $groupName
                ]);
            }

            \Log::info('No se encontró ningún grupo con el GID: ' . $gid);
            return response()->json([
                'success' => false,
                'message' => 'No se encontró ningún grupo con ese GID'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al buscar grupo en LDAP: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al buscar el grupo en LDAP'
            ], 500);
        }

    } catch (\Exception $e) {
        \Log::error('Error general en la búsqueda de grupo por GID: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Error al procesar la solicitud'
        ], 500);
    }
}); 