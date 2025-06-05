<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index()
    {
        $logs = ActivityLog::orderBy('created_at', 'desc')->paginate(50);
        return view('admin.users.logs', compact('logs'));
    }

    public function delete($count)
    {
        try {
            if ($count === 'all') {
                ActivityLog::truncate();
                return redirect()->route('admin.logs.index')->with('status', 'Todos los logs han sido eliminados.');
            }

            $count = (int) $count;
            if ($count <= 0) {
                throw new \Exception('Número de logs inválido');
            }

            $logs = ActivityLog::orderBy('created_at', 'desc')->take($count)->get();
            $deleted = $logs->count();
            $logs->each->delete();

            return redirect()->route('admin.logs')->with('status', "Se han eliminado {$deleted} logs.");
        } catch (\Exception $e) {
            return redirect()->route('admin.logs')->with('error', 'Error al eliminar los logs: ' . $e->getMessage());
        }
    }
} 