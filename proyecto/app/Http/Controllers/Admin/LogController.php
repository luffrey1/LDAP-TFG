<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $type = $request->get('type', 'all');
        $search = $request->get('search', '');

        // Obtener logs de activity_logs
        $activityLogs = DB::table('activity_logs')
            ->select('id', 'user', 'action', 'description', 'created_at', 'level', 'details')
            ->when($search, function($query) use ($search) {
                return $query->where('user', 'like', "%{$search}%")
                           ->orWhere('action', 'like', "%{$search}%")
                           ->orWhere('description', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($log) {
                return (object) [
                    'id' => $log->id,
                    'user' => $log->user,
                    'action' => $log->action,
                    'description' => $log->description,
                    'created_at' => Carbon::parse($log->created_at),
                    'level' => $log->level,
                    'type' => $this->getLogType($log->action),
                    'details' => json_decode($log->details ?? '{}', true)
                ];
            });

        // Obtener logs de access_attempts
        $accessLogs = DB::table('access_attempts')
            ->select('id', 'username as user', DB::raw("'Intento de acceso' as action"), 
                    DB::raw("CONCAT('Desde ', hostname, ' (', ip, ')') as description"), 
                    'created_at', DB::raw("'WARNING' as level"),
                    DB::raw("JSON_OBJECT('hostname', hostname, 'ip', ip) as details"))
            ->when($search, function($query) use ($search) {
                return $query->where('username', 'like', "%{$search}%")
                           ->orWhere('hostname', 'like', "%{$search}%")
                           ->orWhere('ip', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($log) {
                return (object) [
                    'id' => $log->id,
                    'user' => $log->user,
                    'action' => $log->action,
                    'description' => $log->description,
                    'created_at' => Carbon::parse($log->created_at),
                    'level' => $log->level,
                    'type' => 'access',
                    'details' => json_decode($log->details, true)
                ];
            });

        // Combinar y filtrar por tipo
        $allLogs = $activityLogs->concat($accessLogs)
            ->when($type !== 'all', function($collection) use ($type) {
                return $collection->filter(function($log) use ($type) {
                    return $log->type === $type;
                });
            })
            ->sortByDesc('created_at')
            ->values();

        // Crear una colección paginada
        $page = $request->get('page', 1);
        $perPage = 15;
        $paginatedLogs = new \Illuminate\Pagination\LengthAwarePaginator(
            $allLogs->forPage($page, $perPage),
            $allLogs->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        if ($request->ajax()) {
            return response()->json([
                'html' => view('admin.users.logs_table', ['logs' => $paginatedLogs])->render(),
                'total' => $allLogs->count()
            ]);
        }

        return view('admin.users.logs', ['logs' => $paginatedLogs]);
    }

    public function show($id)
    {
        // Buscar el log en activity_logs
        $log = DB::table('activity_logs')
            ->where('id', $id)
            ->first();

        if (!$log) {
            // Si no está en activity_logs, buscar en access_attempts
            $log = DB::table('access_attempts')
                ->where('id', $id)
                ->first();

            if ($log) {
                $log = (object) [
                    'id' => $log->id,
                    'user' => $log->username,
                    'action' => 'Intento de acceso',
                    'description' => "Intento de acceso desde {$log->hostname} ({$log->ip})",
                    'created_at' => Carbon::parse($log->created_at),
                    'level' => 'WARNING',
                    'type' => 'access',
                    'details' => [
                        'hostname' => $log->hostname,
                        'ip' => $log->ip
                    ]
                ];
            }
        } else {
            $log = (object) [
                'id' => $log->id,
                'user' => $log->user,
                'action' => $log->action,
                'description' => $log->description,
                'created_at' => Carbon::parse($log->created_at),
                'level' => $log->level,
                'type' => $this->getLogType($log->action),
                'details' => json_decode($log->details ?? '{}', true)
            ];
        }

        if (!$log) {
            return response()->json(['error' => 'Log no encontrado'], 404);
        }

        return response()->json($log);
    }

    private function getLogType($action)
    {
        $action = strtolower($action);
        
        // Detección de acciones de usuario
        if (strpos($action, 'usuario') !== false || 
            strpos($action, 'user') !== false || 
            strpos($action, 'crear usuario') !== false ||
            strpos($action, 'actualizar usuario') !== false ||
            strpos($action, 'eliminar usuario') !== false ||
            strpos($action, 'editar usuario') !== false ||
            strpos($action, 'reset password') !== false ||
            strpos($action, 'cambiar contraseña') !== false ||
            strpos($action, 'reparar usuario') !== false ||
            strpos($action, 'toggle admin') !== false ||
            strpos($action, 'cambiar permisos') !== false ||
            strpos($action, 'modificar usuario') !== false ||
            strpos($action, 'update user') !== false ||
            strpos($action, 'delete user') !== false ||
            strpos($action, 'create user') !== false) {
            return 'users';
        }
        
        // Detección de acciones de grupo
        if (strpos($action, 'grupo') !== false || 
            strpos($action, 'group') !== false || 
            strpos($action, 'crear grupo') !== false ||
            strpos($action, 'actualizar grupo') !== false ||
            strpos($action, 'eliminar grupo') !== false ||
            strpos($action, 'editar grupo') !== false ||
            strpos($action, 'añadir miembro') !== false ||
            strpos($action, 'eliminar miembro') !== false ||
            strpos($action, 'add member') !== false ||
            strpos($action, 'remove member') !== false ||
            strpos($action, 'create group') !== false ||
            strpos($action, 'update group') !== false ||
            strpos($action, 'delete group') !== false ||
            strpos($action, 'modificar grupo') !== false ||
            strpos($action, 'memberuid') !== false ||
            strpos($action, 'uniquemember') !== false ||
            strpos($action, 'member') !== false) {
            return 'groups';
        }
        
        // Detección de intentos de acceso
        if (strpos($action, 'acceso') !== false || 
            strpos($action, 'access') !== false || 
            strpos($action, 'login') !== false ||
            strpos($action, 'logout') !== false ||
            strpos($action, 'intento') !== false ||
            strpos($action, 'autenticación') !== false ||
            strpos($action, 'authentication') !== false ||
            strpos($action, 'failed login') !== false ||
            strpos($action, 'successful login') !== false) {
            return 'access';
        }
        
        return 'other';
    }

    public function delete($count)
    {
        try {
            if ($count === 'all') {
                DB::table('activity_logs')->truncate();
                DB::table('access_attempts')->truncate();
                return redirect()->route('admin.logs')->with('status', 'Todos los logs han sido eliminados.');
            }

            $count = (int) $count;
            if ($count <= 0) {
                throw new \Exception('Número de logs inválido');
            }

            // Eliminar logs de activity_logs
            $activityLogs = DB::table('activity_logs')
                ->orderBy('created_at', 'desc')
                ->take($count)
                ->delete();

            // Eliminar logs de access_attempts
            $accessLogs = DB::table('access_attempts')
                ->orderBy('created_at', 'desc')
                ->take($count)
                ->delete();

            $totalDeleted = $activityLogs + $accessLogs;

            return redirect()->route('admin.logs')->with('status', "Se han eliminado {$totalDeleted} logs.");
        } catch (\Exception $e) {
            return redirect()->route('admin.logs')->with('error', 'Error al eliminar los logs: ' . $e->getMessage());
        }
    }
} 