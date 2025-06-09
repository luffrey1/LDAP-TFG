<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Notifications\StudentAccessAttempt;
use Illuminate\Support\Facades\Notification;
use LdapRecord\Container;
use App\Models\AccessAttempt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class AccessLogController extends Controller
{
    public function logAccessAttempt(Request $request)
    {
        Log::debug('AccessLogController: Iniciando registro de intento de acceso', [
            'request_data' => $request->all(),
            'session_user' => session('auth_user')
        ]);

        $user = session('auth_user');
        
        if (!$user) {
            Log::warning('AccessLogController: No hay usuario autenticado en la sesión');
            return response()->json(['error' => 'No authenticated user'], 401);
        }

        // Obtener el hostname usando el macscanner
        $hostname = $this->getHostnameFromMacScanner($request->ip());
        Log::debug('AccessLogController: Hostname obtenido', ['hostname' => $hostname]);

        try {
            // Guardar el intento de acceso en la base de datos
            $attempt = AccessAttempt::create([
                'username' => $user['username'],
                'nombre' => $user['nombre'],
                'hostname' => $hostname,
                'ip' => $request->ip(),
                'created_at' => now()
            ]);

            Log::info('AccessLogController: Intento de acceso registrado exitosamente', [
                'attempt_id' => $attempt->id,
                'username' => $user['username']
            ]);

            return response()->json([
                'message' => 'Access attempt logged successfully',
                'hostname' => $hostname
            ]);
        } catch (\Exception $e) {
            Log::error('AccessLogController: Error al registrar intento de acceso', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Error logging access attempt'], 500);
        }
    }

    private function getHostnameFromMacScanner($ip)
    {
        try {
            $baseUrl = env('MACSCANNER_URL', 'http://localhost:5000');
            $response = Http::timeout(2)->get("{$baseUrl}/api/devices", ['ip' => $ip]);
            
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['hostname'])) {
                    return $data['hostname'];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Error al obtener hostname del macscanner: ' . $e->getMessage());
        }

        // Fallback a gethostbyaddr si el macscanner falla
        return gethostbyaddr($ip) ?: $ip;
    }
} 