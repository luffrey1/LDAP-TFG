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
    protected $forceLinuxCommands = true; // Nueva propiedad para forzar comandos Linux

    public function __construct()
    {
        // Cargar configuración desde .env
        $this->sshHost = env('SSH_EXECUTION_HOST');
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
        // Si hay nodo de ejecución configurado, usarlo
        if (!empty($this->sshHost) && !empty($this->sshUser) && $this->connect()) {
            try {
                $command = "ping -c 2 -W 2 $ip";
                
                // Para dispositivos de red, usar más intentos y más timeout
                if ($this->isNetworkInfrastructure($ip)) {
                    $command = "ping -c 4 -W 5 $ip";
                    Log::debug("Ping remoto a dispositivo de red: $ip - Usando configuración especial");
                }
                
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
                // Si falla la ejecución remota, intentar localmente
                return $this->localPing($ip);
            }
        } else {
            // Si no hay nodo remoto, usar el ping local
            return $this->localPing($ip);
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
            $process = null;
            
            // Si estamos en Windows pero queremos forzar comandos estilo Linux
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' && $this->forceLinuxCommands) {
                Log::debug("Usando emulación de comandos Linux en Windows para IP: $ip");
                
                // Emulamos los parámetros de Linux con los equivalentes de Windows
                if ($isNetworkDevice) {
                    // Para dispositivos como routers, usar más intentos y mayor timeout
                    $process = new \Symfony\Component\Process\Process(['ping', '-n', '4', '-w', '5000', $ip]);
                    Log::debug("Ping local a dispositivo de red: $ip - Usando configuración especial (estilo Linux)");
                } else {
                    $process = new \Symfony\Component\Process\Process(['ping', '-n', '2', '-w', '2000', $ip]);
                }
            } 
            // Comportamiento normal por sistema operativo
            else {
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
            
            // Registrar la salida completa para diagnóstico
            if (!$isSuccess) {
                Log::debug("Ping fallido a $ip - Output: " . substr($output, 0, 300) . "...");
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
            // Configuración por defecto para conexiones SSH
            $username = $username ?? 'root';
            $password = $password ?? 'password'; // Contraseña configurada en ldap-setup.sh
            $port = 22;
            
            Log::debug("Ejecutando comando en {$ip} con usuario {$username}: {$command}");
            
            // Usar el método de fallback que funciona sin problemas 
            return $this->fallbackSshCommand($ip, $command, $username, $password, $port);
        } catch (\Exception $e) {
            Log::error("Error ejecutando comando remoto: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al ejecutar comando remoto: ' . $e->getMessage(),
                'output' => 'Error de conexión: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Método alternativo para ejecutar comando SSH usando shell_exec
     */
    private function fallbackSshCommand($ip, $command, $username, $password, $port = 22)
    {
        try {
            Log::debug("Intentando ejecutar comando SSH con shell_exec en {$ip}");
            
            // Escapar credenciales para seguridad
            $escapedPassword = escapeshellarg($password);
            $escapedCommand = escapeshellarg($command);
            
            // Construir comando sshpass para autenticación automática
            $sshCmd = "sshpass -p {$escapedPassword} ssh -o StrictHostKeyChecking=no -p {$port} {$username}@{$ip} {$escapedCommand} 2>&1";
            Log::debug("Comando SSH generado: " . preg_replace('/sshpass -p \'[^\']+\'/', 'sshpass -p [PROTECTED]', $sshCmd));
            
            // Ejecutar comando
            $output = shell_exec($sshCmd);
            
            // Verificar si hay errores comunes
            if (strpos($output, 'Permission denied') !== false) {
                Log::error("Error de autenticación SSH en {$ip}: Permiso denegado");
                return [
                    'success' => false,
                    'message' => 'Error de autenticación: Permiso denegado',
                    'output' => $output
                ];
            }
            
            if (strpos($output, 'Connection refused') !== false) {
                Log::error("Error de conexión SSH en {$ip}: Conexión rechazada");
                return [
                    'success' => false,
                    'message' => 'Error de conexión: Puerto cerrado o bloqueado',
                    'output' => $output
                ];
            }
            
            // Asumir éxito si llegamos aquí
            Log::debug("Comando ejecutado con éxito en {$ip} usando shell_exec");
            return [
                'success' => true,
                'message' => 'Comando ejecutado correctamente',
                'output' => $output
            ];
        } catch (\Exception $e) {
            Log::error("Error en fallbackSshCommand: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error al ejecutar comando remoto: ' . $e->getMessage(),
                'output' => 'Error en conexión SSH: ' . $e->getMessage()
            ];
        }
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
    public function executeLocalCommand($command)
    {
        // Si no hay nodo de ejecución configurado, ejecutar localmente
        if (!$this->isConfigured()) {
            return $this->executeSystemCommand($command);
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
    protected function executeSystemCommand($command)
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

    /**
     * Obtiene MAC de base de datos de conocidas
     * @param string $ip Dirección IP
     * @return string|null MAC si se encuentra
     */
    protected function getKnownMac($ip)
    {
        // Solo MACs reales conocidas confirmadas
        $knownMacs = [
            // Servidores y dispositivos con MAC verificada
            '172.20.0.1' => '00:1a:8c:5d:60:a1',  // Router principal
            '172.20.0.2' => '00:1a:8c:5d:60:a2',  // Servidor DNS
            '172.20.0.6' => '00:1a:8c:5d:60:c1',  // Host-172-20-0-6
            // Añadir aquí solo MACs verificadas, eliminando las ficticias
        ];
        
        return $knownMacs[$ip] ?? null;
    }
    
    /**
     * Obtiene la dirección MAC para una IP dada utilizando múltiples técnicas
     * @param string $ip Dirección IP del dispositivo
     * @return string|null Dirección MAC si se encuentra, null en caso contrario
     */
    public function getMacAddress($ip)
    {
        Log::debug("Intentando obtener dirección MAC para IP: $ip");

        // 1. Base de datos de MACs conocidas
        $mac = $this->getKnownMac($ip);
        if ($mac) {
            Log::info("MAC verificada para $ip: $mac");
            return $mac;
        }

        // 2. Forzar actualización de ARP con ping
        $this->forceArpUpdate($ip);

        // 3. ip neigh
        $mac = $this->getMacFromIpNeigh($ip);
        if ($mac) {
            Log::info("MAC encontrada con ip neigh para $ip: $mac");
            return $mac;
        }

        // 4. arp
        $mac = $this->getMacFromArp($ip);
        if ($mac) {
            Log::info("MAC encontrada en tabla ARP para $ip: $mac");
            return $mac;
        }

        // 5. arp-scan (si está instalado)
        $mac = $this->getMacWithArpScan($ip);
        if ($mac) {
            Log::info("MAC encontrada con arp-scan para $ip: $mac");
            return $mac;
        }

        // 6. nmap (si está instalado)
        $mac = $this->getMacWithNmap($ip);
        if ($mac) {
            Log::info("MAC encontrada con nmap para $ip: $mac");
            return $mac;
        }

        // 7. DHCP leases
        $mac = $this->getMacFromDhcpLeases($ip);
        if ($mac) {
            Log::info("MAC encontrada en DHCP leases para $ip: $mac");
            return $mac;
        }

        Log::warning("No se pudo obtener la MAC para $ip con ninguna técnica.");
        return null;
    }

    protected function forceArpUpdate($ip)
    {
        try {
            $process = new \Symfony\Component\Process\Process(['ping', '-c', '2', '-W', '1', $ip]);
            $process->setTimeout(3);
            $process->run();
            Log::debug("Ping enviado a $ip para forzar actualización ARP.");
        } catch (\Exception $e) {
            Log::debug("Error forzando ARP con ping: " . $e->getMessage());
        }
    }

    protected function getMacFromIpNeigh($ip)
    {
        try {
            $process = new \Symfony\Component\Process\Process(['ip', 'neigh', 'show', $ip]);
            $process->setTimeout(2);
            $process->run();
            $output = $process->getOutput();
            if (preg_match('/([0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2})/', $output, $matches)) {
                return strtolower($matches[1]);
            }
        } catch (\Exception $e) {
            Log::debug("Error usando ip neigh: " . $e->getMessage());
        }
        return null;
    }

    protected function getMacFromArp($ip)
    {
        try {
            $process = new \Symfony\Component\Process\Process(['arp', '-n', $ip]);
            $process->setTimeout(2);
            $process->run();
            $output = $process->getOutput();
            if (preg_match('/([0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2})/', $output, $matches)) {
                return strtolower($matches[1]);
            }
        } catch (\Exception $e) {
            Log::debug("Error usando arp: " . $e->getMessage());
        }
        return null;
    }

    protected function getMacWithArpScan($ip)
    {
        try {
            $check = new \Symfony\Component\Process\Process(['which', 'arp-scan']);
            $check->run();
            if (!$check->isSuccessful()) {
                return null;
            }
            // Cambia eth0 por tu interfaz si es necesario
            $process = new \Symfony\Component\Process\Process(['sudo', 'arp-scan', '--interface=eth0', $ip]);
            $process->setTimeout(5);
            $process->run();
            $output = $process->getOutput();
            if (preg_match('/([0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2})/', $output, $matches)) {
                return strtolower($matches[1]);
            }
        } catch (\Exception $e) {
            Log::debug("Error usando arp-scan: " . $e->getMessage());
        }
        return null;
    }

    protected function getMacWithNmap($ip)
    {
        try {
            $check = new \Symfony\Component\Process\Process(['which', 'nmap']);
            $check->run();
            if (!$check->isSuccessful()) {
                return null;
            }
            $process = new \Symfony\Component\Process\Process(['sudo', 'nmap', '-sn', $ip]);
            $process->setTimeout(10);
            $process->run();
            $output = $process->getOutput();
            if (preg_match('/MAC Address: ([0-9A-Fa-f:]{17})/', $output, $matches)) {
                return strtolower($matches[1]);
            }
        } catch (\Exception $e) {
            Log::debug("Error usando nmap: " . $e->getMessage());
        }
        return null;
    }

    protected function getMacFromDhcpLeases($ip)
    {
        $leaseFiles = [
            '/var/lib/dhcp/dhclient.leases',
            '/var/lib/dhcp/dhcpd.leases',
            '/var/lib/dhcpd/dhcpd.leases',
            '/var/lib/NetworkManager/dhclient-*.lease',
            '/var/lib/dhcp3/dhclient.leases',
            '/var/lib/NetworkManager/internal-*.lease',
            '/var/db/dhclient.leases.*',
            '/var/lib/systemd/network/leases'
        ];
        foreach ($leaseFiles as $pattern) {
            foreach (glob($pattern) ?: [] as $file) {
                if (!file_exists($file)) continue;
                $contents = file_get_contents($file);
                if (preg_match('/lease\s+' . preg_quote($ip) . '\s+{.*?hardware\s+ethernet\s+([0-9a-fA-F:]{17})/s', $contents, $matches)) {
                    return strtolower($matches[1]);
                }
            }
        }
        return null;
    }

    /**
     * Transfiere un archivo al sistema remoto
     *
     * @param string $ip Dirección IP del host remoto
     * @param string $localPath Ruta local del archivo
     * @param string $remotePath Ruta remota donde guardar el archivo
     * @return array Resultado de la operación
     */
    public function transferFile($ip, $localPath, $remotePath)
    {
        try {
            // Leer el contenido del archivo local
            if (!file_exists($localPath)) {
                return ['success' => false, 'output' => "El archivo $localPath no existe"];
            }
            
            $content = file_get_contents($localPath);
            if ($content === false) {
                return ['success' => false, 'output' => "No se pudo leer el archivo $localPath"];
            }
            
            // Usar la función putFileContent para transferir el archivo
            $result = $this->putFileContent($ip, $remotePath, $content);
            
            // Si es un script en Linux, hacerlo ejecutable
            if ($result['success'] && pathinfo($remotePath, PATHINFO_EXTENSION) === 'sh') {
                $chmodCmd = "chmod +x " . escapeshellarg($remotePath);
                $this->executeCommand($ip, $chmodCmd);
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Error en transferencia de archivo: ' . $e->getMessage());
            return ['success' => false, 'output' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Escribe contenido en un archivo remoto
     *
     * @param string $ip Dirección IP del host remoto
     * @param string $remotePath Ruta remota del archivo
     * @param string $content Contenido a escribir
     * @return array Resultado de la operación
     */
    public function putFileContent($ip, $remotePath, $content)
    {
        try {
            // Obtener host de la base de datos para credenciales
            $host = \App\Models\MonitorHost::where('ip_address', $ip)->first();
            
            if (!$host) {
                Log::warning("Host no encontrado en la base de datos para IP: $ip");
                return ['success' => false, 'output' => 'Host no encontrado en la base de datos'];
            }
            
            $username = $host->ssh_user ?? 'root';
            $password = $host->ssh_password ?? '';
            $sshPort = $host->ssh_port ?? 22;
            
            // Determinar tipo de conexión (Windows/Linux)
            $isWindows = (strpos($remotePath, '\\') !== false || strpos($remotePath, 'C:') === 0);

            // Determinar tipo de conexión (Windows/Linux)
            if ($isWindows) {
                // Para Windows: usar PowerShell a través de una conexión SSH
                $tempLocalFile = tempnam(sys_get_temp_dir(), 'script_');
                file_put_contents($tempLocalFile, $content);
                
                // Crear un comando para copiar y verificar archivos en Windows
                $sshCommand = "sshpass -p " . escapeshellarg($password) . " scp -P $sshPort -o StrictHostKeyChecking=no $tempLocalFile $username@$ip:" . escapeshellarg($remotePath);
                
                Log::debug("Ejecutando comando SCP para Windows: " . $sshCommand);
                $output = shell_exec($sshCommand . " 2>&1");
                
                unlink($tempLocalFile);
                
                if (strpos($output, 'error') !== false || strpos($output, 'failed') !== false) {
                    Log::error("Error al transferir archivo a Windows: $output");
                    return ['success' => false, 'output' => "Error al transferir archivo: $output"];
                }
                
                return ['success' => true, 'output' => "Archivo transferido exitosamente a $remotePath"];
            } else {
                // Para Linux: usar un comando echo a través de una conexión SSH
                // Codificar el contenido para evitar problemas con caracteres especiales
                $encodedContent = base64_encode($content);
                
                // Crear comando para decodificar y escribir el archivo en el sistema remoto
                $remoteCommand = "mkdir -p $(dirname " . escapeshellarg($remotePath) . ") && " .
                                "echo " . escapeshellarg($encodedContent) . " | base64 -d > " . escapeshellarg($remotePath);
                
                // Ejecutar comando SSH
                $sshCommand = "sshpass -p " . escapeshellarg($password) . " ssh -p $sshPort -o StrictHostKeyChecking=no $username@$ip " . escapeshellarg($remoteCommand);
                
                Log::debug("Ejecutando comando SSH para Linux: " . $sshCommand);
                $output = shell_exec($sshCommand . " 2>&1");
                
                if (strpos($output, 'error') !== false || strpos($output, 'failed') !== false) {
                    Log::error("Error al transferir archivo a Linux: $output");
                    return ['success' => false, 'output' => "Error al transferir archivo: $output"];
                }
                
                return ['success' => true, 'output' => "Archivo transferido exitosamente a $remotePath"];
            }
        } catch (\Exception $e) {
            Log::error('Error al escribir archivo remoto: ' . $e->getMessage());
            return ['success' => false, 'output' => 'Error: ' . $e->getMessage()];
        }
    }
} 