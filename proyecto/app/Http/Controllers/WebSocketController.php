<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Artisan;

class WebSocketController extends Controller
{
    /**
     * Verificar el estado del servidor WebSocket
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkStatus()
    {
        // Verificar si el servidor Reverb está en ejecución
        $running = $this->isReverbRunning();
        
        return response()->json([
            'running' => $running,
            'message' => $running ? 'El servidor WebSocket está ejecutándose' : 'El servidor WebSocket no está en ejecución'
        ]);
    }
    
    /**
     * Iniciar el servidor WebSocket
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function startServer(Request $request)
    {
        // Verificar si ya está en ejecución
        if ($this->isReverbRunning()) {
            return response()->json([
                'success' => true,
                'message' => 'El servidor WebSocket ya está en ejecución'
            ]);
        }
        
        try {
            // En entornos Windows, el proceso debe iniciarse de manera diferente
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $this->startReverbOnWindows();
            } else {
                $this->startReverbOnUnix();
            }
            
            // Esperar un momento para dar tiempo a que el servidor se inicie
            sleep(2);
            
            // Verificar si el servidor se inició correctamente
            if ($this->isReverbRunning()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Servidor WebSocket iniciado correctamente'
                ]);
            } else {
                Log::error('No se pudo iniciar el servidor Reverb. El servidor no está respondiendo después del inicio.');
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo iniciar el servidor WebSocket. Inténtalo manualmente ejecutando: php artisan reverb:start'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error al iniciar Reverb: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al iniciar el servidor WebSocket: ' . $e->getMessage(),
                'instructions' => 'Ejecuta manualmente: php artisan reverb:start'
            ]);
        }
    }
    
    /**
     * Recibir comando desde WebSocket
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function receiveCommand(Request $request)
    {
        $sessionId = $request->input('sessionId');
        $command = $request->input('command');
        
        if (!$sessionId || !$command) {
            return response()->json([
                'success' => false,
                'message' => 'Falta sessionId o comando'
            ], 400);
        }
        
        try {
            // Registrar el comando recibido
            Log::info("Comando recibido via WebSocket para sesión {$sessionId}: {$command}");
            
            // Enviar evento a través de Reverb si está disponible
            if ($this->isReverbRunning()) {
                // Lanzar un trabajo que enviará el comando a través del canal privado
                event(new \App\Events\CommandReceived($sessionId, $command));
                
                return response()->json([
                    'success' => true,
                    'message' => 'Comando enviado al canal WebSocket'
                ]);
            } else {
                // Si el servidor WebSocket no está disponible, informar error
                return response()->json([
                    'success' => false,
                    'message' => 'El servidor WebSocket no está en ejecución'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error al procesar comando WebSocket: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el comando: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Verificar si el servidor Reverb está en ejecución
     *
     * @return bool
     */
    private function isReverbRunning()
    {
        // Intentar comprobar mediante conexión a puerto de Reverb
        $reverbPort = env('REVERB_PORT', 8080);
        $reverbHost = env('REVERB_HOST', '127.0.0.1');
        
        $connection = @fsockopen($reverbHost, $reverbPort, $errno, $errstr, 1);
        
        if (is_resource($connection)) {
            fclose($connection);
            return true;
        }
        
        return false;
    }
    
    /**
     * Iniciar Reverb en entornos Windows
     *
     * @return void
     * @throws \Exception
     */
    private function startReverbOnWindows()
    {
        // Crear un archivo batch temporal para ejecutar Reverb en segundo plano
        $batchFile = storage_path('app/start_reverb.bat');
        
        $content = "@echo off\r\n";
        $content .= "cd " . base_path() . "\r\n";
        $content .= "start /B php artisan reverb:start\r\n";
        
        file_put_contents($batchFile, $content);
        
        // Ejecutar el archivo batch
        $process = new Process(['cmd', '/c', $batchFile]);
        $process->start();
        
        // Esperar un momento para que el proceso se inicie
        sleep(1);
    }
    
    /**
     * Iniciar Reverb en entornos Unix/Linux
     *
     * @return void
     * @throws \Exception
     */
    private function startReverbOnUnix()
    {
        // En Unix/Linux podemos iniciar el proceso en segundo plano directamente
        $command = 'cd ' . base_path() . ' && php artisan reverb:start > /dev/null 2>&1 &';
        exec($command);
    }
} 