<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use phpseclib3\Net\SSH2;

class RemoteExecutionService
{
    protected $sshHost;
    protected $sshUser;
    protected $sshPassword;
    protected $sshPort;
    protected $connected = false;
    protected $ssh;

    public function __construct()
    {
        // Cargar configuración desde .env
        $this->sshHost = env('SSH_EXECUTION_HOST', '127.0.0.1');
        $this->sshUser = env('SSH_EXECUTION_USER', 'executor');
        $this->sshPassword = env('SSH_EXECUTION_PASSWORD', '');
        $this->sshPort = env('SSH_EXECUTION_PORT', 22);
        
        // Inicializar conexión SSH bajo demanda
    }

    /**
     * Establece la conexión SSH con el nodo de ejecución
     * @return bool
     */
    protected function connect()
    {
        if ($this->connected) {
            return true;
        }
        
        try {
            $this->ssh = new SSH2($this->sshHost, $this->sshPort);
            if (!$this->ssh->login($this->sshUser, $this->sshPassword)) {
                Log::error("No se pudo conectar al nodo de ejecución SSH: {$this->sshHost}");
                return false;
            }
            
            $this->connected = true;
            return true;
        } catch (\Exception $e) {
            Log::error("Error conectando al nodo SSH: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ejecuta un comando ping a través del nodo Linux
     * @param string $ip La dirección IP a hacer ping
     * @return array Resultado con estado y detalles
     */
    public function ping($ip)
    {
        // Si no hay nodo de ejecución configurado, usar el ping local
        if (empty($this->sshHost) || empty($this->sshUser)) {
            return $this->localPing($ip);
        }
        
        if (!$this->connect()) {
            return [
                'success' => false,
                'message' => 'No se pudo conectar al nodo de ejecución',
                'output' => ''
            ];
        }
        
        try {
            $command = "ping -c 2 -W 2 $ip";
            $output = $this->ssh->exec($command);
            
            $isSuccess = false;
            // Verificar si hay respuesta positiva en la salida
            if (strpos($output, 'bytes from') !== false || 
                strpos($output, 'bytes de') !== false) {
                $isSuccess = true;
            }
            
            return [
                'success' => $isSuccess,
                'message' => $isSuccess ? 'Host en línea' : 'Host fuera de línea',
                'output' => $output
            ];
        } catch (\Exception $e) {
            Log::error("Error ejecutando ping remoto: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al ejecutar ping remoto: ' . $e->getMessage(),
                'output' => ''
            ];
        }
    }
    
    /**
     * Ejecuta un ping usando el sistema local (fallback)
     * @param string $ip La dirección IP a hacer ping
     * @return array Resultado con estado y detalles
     */
    protected function localPing($ip)
    {
        try {
            // Detectar si es un equipo de infraestructura (router, switch, etc.)
            $isNetworkDevice = $this->isNetworkInfrastructure($ip);
            
            // Configuración especial para dispositivos de red
            if ($isNetworkDevice) {
                Log::debug("Ping a dispositivo de red: $ip - Usando configuración especial");
                
                // Para dispositivos como routers, usar más intentos y mayor timeout
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    $process = new \Symfony\Component\Process\Process(['ping', '-n', '4', '-w', '5000', $ip]);
                } else {
                    $process = new \Symfony\Component\Process\Process(['ping', '-c', '4', '-W', '5', $ip]);
                }
                $process->setTimeout(10);
            } else {
                // Configuración normal para equipos regulares
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    $process = new \Symfony\Component\Process\Process(['ping', '-n', '2', '-w', '2000', $ip]);
                } else {
                    $process = new \Symfony\Component\Process\Process(['ping', '-c', '2', '-W', '2', $ip]);
                }
                $process->setTimeout(5);
            }
            
            $process->run();
            
            $output = $process->getOutput();
            $isSuccess = $process->isSuccessful();
            
            // Verificar si hay alguna respuesta parcial
            if (!$isSuccess && (
                strpos($output, 'bytes from') !== false || 
                strpos($output, 'bytes de') !== false ||
                strpos($output, 'Reply from') !== false || 
                strpos($output, 'Respuesta desde') !== false
            )) {
                $isSuccess = true;
            }
            
            // Para dispositivos de red, si hay al menos una respuesta, es considerado online
            if ($isNetworkDevice && !$isSuccess) {
                if (preg_match('/Recibidos = (\d+)/', $output, $matches) || 
                    preg_match('/Received = (\d+)/', $output, $matches)) {
                    if ((int)$matches[1] > 0) {
                        $isSuccess = true;
                        Log::debug("Dispositivo de red $ip considerado online con respuesta parcial");
                    }
                }
            }
            
            return [
                'success' => $isSuccess,
                'message' => $isSuccess ? 'Host en línea' : 'Host fuera de línea',
                'output' => $output
            ];
        } catch (\Exception $e) {
            Log::error("Error ejecutando ping local: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al ejecutar ping local: ' . $e->getMessage(),
                'output' => ''
            ];
        }
    }
    
    /**
     * Determina si una IP corresponde a un dispositivo de infraestructura de red
     * @param string $ip La dirección IP a evaluar
     * @return bool
     */
    private function isNetworkInfrastructure($ip)
    {
        // Lista de IPs conocidas de dispositivos de red
        $knownNetworkDevices = [
            '172.20.0.1',  // Router principal
            '172.20.0.2',  // DNS server
            '172.20.0.30', // Servidor departamental
        ];
        
        // Si es una IP conocida de infraestructura
        if (in_array($ip, $knownNetworkDevices)) {
            return true;
        }
        
        // Si termina en .1, .254, o similar (potencialmente un router/gateway)
        $parts = explode('.', $ip);
        if (count($parts) == 4) {
            $lastOctet = (int)$parts[3];
            if ($lastOctet == 1 || $lastOctet == 254 || $lastOctet == 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Ejecuta un comando en un host remoto a través de SSH
     * @param string $ip La dirección IP del host
     * @param string $command El comando a ejecutar
     * @param string $username Usuario SSH (opcional)
     * @param string $password Contraseña SSH (opcional)
     * @return array Resultado con estado y detalles
     */
    public function executeRemoteCommand($ip, $command, $username = null, $password = null)
    {
        try {
            // Conectar al host específico
            $ssh = new SSH2($ip);
            if ($username === null) {
                $username = env('DEFAULT_SSH_USER', 'admin');
            }
            if ($password === null) {
                $password = env('DEFAULT_SSH_PASSWORD', '');
            }
            
            if (!$ssh->login($username, $password)) {
                return [
                    'success' => false,
                    'message' => 'No se pudo conectar al host: ' . $ip,
                    'output' => ''
                ];
            }
            
            $output = $ssh->exec($command);
            
            return [
                'success' => true,
                'message' => 'Comando ejecutado correctamente',
                'output' => $output
            ];
        } catch (\Exception $e) {
            Log::error("Error ejecutando comando remoto: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al ejecutar comando remoto: ' . $e->getMessage(),
                'output' => ''
            ];
        }
    }
    
    /**
     * Ejecuta un comando simultáneamente en múltiples hosts
     * @param array $ips Lista de IPs
     * @param string $command El comando a ejecutar
     * @return array Resultados por IP
     */
    public function executeCommandOnMultipleHosts($ips, $command)
    {
        $results = [];
        
        foreach ($ips as $ip) {
            $results[$ip] = $this->executeRemoteCommand($ip, $command);
        }
        
        return $results;
    }

    /**
     * Verifica si el servicio está configurado para usar un nodo de ejecución remota
     * @return bool
     */
    public function isConfigured()
    {
        return !empty($this->sshHost) && !empty($this->sshUser);
    }

    /**
     * Ejecuta un comando arbitrario en el nodo de ejecución
     * @param string $command El comando a ejecutar
     * @return array Resultado con status y output
     */
    public function executeCommand($command)
    {
        // Si no hay nodo de ejecución configurado, ejecutar localmente
        if (!$this->isConfigured()) {
            return $this->executeLocalCommand($command);
        }
        
        if (!$this->connect()) {
            return [
                'success' => false,
                'message' => 'No se pudo conectar al nodo de ejecución',
                'output' => ''
            ];
        }
        
        try {
            $output = $this->ssh->exec($command);
            
            return [
                'success' => true,
                'message' => 'Comando ejecutado correctamente',
                'output' => $output
            ];
        } catch (\Exception $e) {
            Log::error("Error ejecutando comando remoto: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al ejecutar comando remoto: ' . $e->getMessage(),
                'output' => ''
            ];
        }
    }
    
    /**
     * Ejecuta un comando arbitrario en el sistema local (fallback)
     * @param string $command El comando a ejecutar
     * @return array Resultado con status y output
     */
    protected function executeLocalCommand($command)
    {
        try {
            $process = new \Symfony\Component\Process\Process(explode(' ', $command));
            $process->setTimeout(10);
            $process->run();
            
            return [
                'success' => $process->isSuccessful(),
                'message' => $process->isSuccessful() ? 'Comando ejecutado correctamente' : 'Error al ejecutar comando',
                'output' => $process->getOutput()
            ];
        } catch (\Exception $e) {
            Log::error("Error ejecutando comando local: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al ejecutar comando local: ' . $e->getMessage(),
                'output' => ''
            ];
        }
    }
} 