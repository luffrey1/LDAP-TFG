<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Notifications\StudentAccessAttempt;
use Illuminate\Support\Facades\Notification;
use LdapRecord\Container;
use App\Models\AccessAttempt;
use Illuminate\Support\Facades\DB;

class AccessLogController extends Controller
{
    public function logAccessAttempt(Request $request)
    {
        $user = session('auth_user');
        
        if (!$user) {
            return response()->json(['error' => 'No authenticated user'], 401);
        }

        // Obtener el hostname usando el macscanner
        $hostname = $this->getHostnameFromMacScanner($request->ip());

        // Guardar el intento de acceso en la base de datos
        AccessAttempt::create([
            'username' => $user['username'],
            'nombre' => $user['nombre'],
            'hostname' => $hostname,
            'ip' => $request->ip(),
            'created_at' => now()
        ]);

        return response()->json([
            'message' => 'Access attempt logged successfully',
            'hostname' => $hostname
        ]);
    }

    private function getHostnameFromMacScanner($ip)
    {
        try {
            $response = file_get_contents("http://localhost:5000/api/devices?ip=" . urlencode($ip));
            $data = json_decode($response, true);
            
            if ($data && isset($data['hostname'])) {
                return $data['hostname'];
            }
        } catch (\Exception $e) {
            \Log::error('Error getting hostname from macscanner: ' . $e->getMessage());
        }

        // Fallback a gethostbyaddr si el macscanner falla
        return gethostbyaddr($ip);
    }
} 