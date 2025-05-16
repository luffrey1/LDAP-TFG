<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Host;
use App\Events\TerminalOutputReceived;
use phpseclib3\Net\SSH2;

class SshTerminalController extends Controller
{
    /**
     * Conectar a un host mediante SSH
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function connect(Request $request)
    {
        $request->validate([
            'host_id' => 'required|exists:hosts,id',
            'username' => 'required|string',
        ]);
        
        $hostId = $request->input('host_id');
        $username = $request->input('username');
        $password = $request->input('password'); // Opcional, no siempre es necesario si se usa clave SSH
        
        try {
            // Obtener información del host
            $host = Host::findOrFail($hostId);
            
            // Generar ID único para la sesión
            $sessionId = Str::uuid()->toString();
            
            // Conectar vía SSH
            $ssh = new SSH2($host->ip_address);
            
            // Intentar autenticar con clave SSH primero (si está configurada)
            $authenticatedWithKey = false;
            
            if (file_exists(storage_path('app/ssh/id_rsa')) && file_exists(storage_path('app/ssh/id_rsa.pub'))) {
                try {
                    $key = \phpseclib3\Crypt\PublicKeyLoader::load(file_get_contents(storage_path('app/ssh/id_rsa')));
                    if ($ssh->login($username, $key)) {
                        $authenticatedWithKey = true;
                        Log::info("Conexión SSH establecida con {$host->hostname} usando clave SSH");
                    }
                } catch (\Exception $e) {
                    Log::warning("Error al autenticar con clave SSH: " . $e->getMessage());
                }
            }
            
            // Si la autenticación con clave falló y se proporcionó contraseña, intentar con contraseña
            if (!$authenticatedWithKey && $password) {
                if (!$ssh->login($username, $password)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Error de autenticación SSH: Credenciales incorrectas'
                    ], 401);
                }
                
                Log::info("Conexión SSH establecida con {$host->hostname} usando contraseña");
            } elseif (!$authenticatedWithKey && !$password) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de autenticación SSH: Se requiere contraseña o clave SSH'
                ], 401);
            }
            
            // Almacenar la sesión SSH en la caché de Laravel
            cache()->put("ssh_session:{$sessionId}", [
                'ssh' => $ssh,
                'host_id' => $hostId,
                'username' => $username,
                'ip_address' => $host->ip_address,
                'hostname' => $host->hostname,
                'active' => true,
                'current_directory' => '~'
            ], now()->addHours(4)); // La sesión expira después de 4 horas
            
            // Devolver ID de sesión para futura referencia
            return response()->json([
                'success' => true,
                'message' => 'Conexión SSH establecida',
                'sessionId' => $sessionId,
                'hostname' => $host->hostname,
                'ip_address' => $host->ip_address,
                'username' => $username
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error al conectar SSH: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al conectar SSH: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Desconectar sesión SSH
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function disconnect(Request $request)
    {
        $request->validate([
            'sessionId' => 'required|string'
        ]);
        
        $sessionId = $request->input('sessionId');
        $sessionKey = "ssh_session:{$sessionId}";
        
        if (!cache()->has($sessionKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Sesión SSH no encontrada'
            ], 404);
        }
        
        try {
            $session = cache()->get($sessionKey);
            
            // Cerrar la conexión SSH
            if (isset($session['ssh']) && $session['ssh'] instanceof SSH2) {
                $session['ssh']->disconnect();
            }
            
            // Eliminar la sesión de la caché
            cache()->forget($sessionKey);
            
            return response()->json([
                'success' => true,
                'message' => 'Sesión SSH cerrada correctamente'
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error al desconectar SSH: " . $e->getMessage());
            
            // Intentar eliminar la sesión de la caché de todos modos
            cache()->forget($sessionKey);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al desconectar SSH: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Ejecutar comando en la sesión SSH
     * Este método es llamado directamente por WebSocket
     *
     * @param  string  $sessionId
     * @param  string  $command
     * @return array
     */
    public function executeCommand($sessionId, $command)
    {
        $sessionKey = "ssh_session:{$sessionId}";
        
        if (!cache()->has($sessionKey)) {
            return [
                'success' => false,
                'message' => 'Sesión SSH no encontrada'
            ];
        }
        
        try {
            $session = cache()->get($sessionKey);
            
            if (!isset($session['ssh']) || !($session['ssh'] instanceof SSH2) || !$session['ssh']->isConnected()) {
                return [
                    'success' => false,
                    'message' => 'La conexión SSH se ha perdido'
                ];
            }
            
            // Ejecutar comando
            $output = $session['ssh']->exec($command);
            
            // Si es un comando que cambia el directorio, necesitamos actualizar el directorio actual
            if (Str::startsWith($command, 'cd ')) {
                $currentDirectory = $session['ssh']->exec('pwd');
                $currentDirectory = trim($currentDirectory);
                
                $session['current_directory'] = $currentDirectory;
                cache()->put($sessionKey, $session, now()->addHours(4));
            }
            
            // Transmitir la salida a través de WebSockets
            event(new TerminalOutputReceived(
                $sessionId, 
                $output, 
                $session['current_directory'] ?? '~'
            ));
            
            return [
                'success' => true,
                'output' => $output,
                'currentDirectory' => $session['current_directory'] ?? '~'
            ];
            
        } catch (\Exception $e) {
            Log::error("Error al ejecutar comando SSH: " . $e->getMessage());
            
            // Transmitir el error a través de WebSockets
            event(new TerminalOutputReceived(
                $sessionId, 
                "Error: " . $e->getMessage(),
                $session['current_directory'] ?? '~'
            ));
            
            return [
                'success' => false,
                'message' => 'Error al ejecutar comando: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Endpoint HTTP para ejecutar comandos (fallback)
     * Este método debe usarse solo si WebSockets no funciona
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function execute(Request $request)
    {
        $request->validate([
            'sessionId' => 'required|string',
            'command' => 'required|string'
        ]);
        
        $sessionId = $request->input('sessionId');
        $command = $request->input('command');
        
        $result = $this->executeCommand($sessionId, $command);
        
        return response()->json($result, $result['success'] ? 200 : 500);
    }
} 