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
        
        // Técnica 0: Para el caso de MACs verificadas en base de datos
        $mac = $this->getKnownMac($ip);
        if ($mac) {
            Log::info("MAC verificada para $ip: $mac");
            return $mac;
        }
        
        // Técnica 1: Tabla ARP (básica y ya implementada)
        $mac = $this->getMacFromArp($ip);
        if ($mac) {
            Log::info("MAC real encontrada para $ip usando ARP: $mac");
            return $mac;
        }
        
        // Técnica 2: Forzar comunicación primero y luego consultar ARP
        $mac = $this->getMacWithForcedArp($ip);
        if ($mac) {
            Log::info("MAC real encontrada para $ip usando ARP forzado: $mac");
            return $mac;
        }
        
        // Técnica 3: Para dispositivos de red, usar técnicas específicas
        if ($this->isNetworkInfrastructure($ip)) {
            Log::debug("Intentando técnicas específicas para dispositivo de red: $ip");
            $mac = $this->getNetworkDeviceMac($ip);
            if ($mac) {
                Log::info("MAC real encontrada para dispositivo de red $ip: $mac");
                return $mac;
            }
        }
        
        // Técnica 4: En Ubuntu, comprobar si el archivo /var/lib/dhcp/dhclient.leases contiene la MAC
        $mac = $this->getMacFromDhcpLeases($ip);
        if ($mac) {
            Log::info("MAC real encontrada en dhclient.leases para $ip: $mac");
            return $mac;
        }
        
        // Técnica adicional: Intentar métodos más avanzados
        $mac = $this->getAdvancedMacDetection($ip);
        if ($mac) {
            Log::info("MAC real encontrada con métodos avanzados para $ip: $mac");
            return $mac;
        }
        
        Log::debug("No se pudo obtener MAC real para $ip con ninguna técnica");
        return null;
    }
    
    /**
     * Obtiene la dirección MAC desde la tabla ARP
     * @param string $ip Dirección IP
     * @return string|null MAC si se encuentra
     */
    protected function getMacFromArp($ip)
    {
        try {
            Log::debug("Consultando tabla ARP para IP: $ip");
            
            // Si hay nodo remoto configurado, usarlo
            if ($this->isConfigured() && $this->connect()) {
                // En Ubuntu/Linux, probamos varios comandos
                $commands = [
                    "arp -n " . $ip,
                    "ip neigh show " . $ip,
                    "cat /proc/net/arp | grep " . $ip,
                    // Bypass 1: Comandos con mayor privilegio
                    "sudo arp -n " . $ip,
                    "sudo ip neigh show " . $ip
                ];
                
                foreach ($commands as $command) {
                    $output = $this->ssh->exec($command);
                    Log::debug("Resultado comando remoto para $ip: " . substr($output, 0, 200));
                    
                    // Extraer MAC de la salida
                    if (preg_match('/([0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2})/', $output, $matches)) {
                        return strtolower($matches[1]);
                    }
                }
            }
            
            // Método local (fallback)
            // Adaptado especialmente para Ubuntu/Linux
            if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                $commands = [
                    ['ip', 'neigh', 'show', $ip],
                    ['arp', '-n', $ip],
                    ['sh', '-c', 'cat /proc/net/arp | grep ' . $ip],
                    // Bypass 2: Comandos avanzados con privilegios
                    ['sh', '-c', 'sudo ip neigh show ' . $ip],
                    ['sh', '-c', 'sudo arp -n ' . $ip],
                    // Bypass 3: Consulta completa de ARP
                    ['sh', '-c', 'arp -n | grep ' . $ip],
                    // Bypass 4: Operador de red específico
                    ['sh', '-c', 'ip -4 neigh show ' . $ip]
                ];
                
                foreach ($commands as $cmdArray) {
                    try {
                        $process = new \Symfony\Component\Process\Process($cmdArray);
                        $process->setTimeout(3);
                        $process->run();
                        $output = $process->getOutput();
                        
                        Log::debug("Resultado comando local para $ip: " . substr($output, 0, 200));
                        
                        if (preg_match('/([0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2})/', $output, $matches)) {
                            return strtolower($matches[1]);
                        }
                    } catch (\Exception $e) {
                        // Si falla un comando, intentamos el siguiente
                        Log::debug("Comando falló, intentando el siguiente: " . $e->getMessage());
                        continue;
                    }
                }
            }
            // Para Windows
            else {
                $command = 'arp -a ' . $ip;
                $process = new \Symfony\Component\Process\Process(explode(' ', $command));
                $process->setTimeout(3);
                $process->run();
                $output = $process->getOutput();
                
                Log::debug("Resultado ARP local para $ip: " . substr($output, 0, 200));
                
                if (preg_match('/([0-9a-fA-F]{2}-[0-9a-fA-F]{2}-[0-9a-fA-F]{2}-[0-9a-fA-F]{2}-[0-9a-fA-F]{2}-[0-9a-fA-F]{2})/', $output, $matches)) {
                    return str_replace('-', ':', strtolower($matches[1]));
                }
            }
        } catch (\Exception $e) {
            Log::error("Error obteniendo MAC desde ARP: " . $e->getMessage());
        }
        
        return null;
    }
    
    /**
     * Fuerza entrada en la tabla ARP enviando ping y luego consulta
     * @param string $ip Dirección IP
     * @return string|null MAC si se encuentra
     */
    protected function getMacWithForcedArp($ip)
    {
        try {
            Log::debug("Forzando entrada ARP para IP: $ip");
            
            // En Ubuntu, podemos usar arping si está disponible
            if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                try {
                    // Primero intentamos con arping que es más efectivo
                    $commands = [
                        // Determinar la interfaz automáticamente (puede variar según la versión)
                        ['sh', '-c', "arping -c 4 -w 3 $ip 2>/dev/null"],
                        ['sh', '-c', "arping -c 4 -I $(ip route get $ip | grep -oP 'dev \\K\\S+') $ip 2>/dev/null"],
                        // Bypass 1: Forzar con ARP agresivo
                        ['sh', '-c', "sudo arping -c 6 -w 5 $ip 2>/dev/null"],
                        // Bypass 2: Usar interfaz específica para mejor alcance
                        ['sh', '-c', "for iface in $(ls /sys/class/net/ | grep -v lo); do sudo arping -c 3 -I \$iface $ip 2>/dev/null; done"],
                        // Bypass 3: Forzar comunicación con diferentes protocolos
                        ['sh', '-c', "ping -c 2 $ip >/dev/null && nc -z -v $ip 22 2>/dev/null && nc -z -v $ip 80 2>/dev/null && arping -c 2 $ip 2>/dev/null"]
                    ];
                    
                    foreach ($commands as $cmdArray) {
                        $process = new \Symfony\Component\Process\Process($cmdArray);
                        $process->setTimeout(8); // Aumentamos timeout para comandos más complejos
                        $process->run();
                        $output = $process->getOutput();
                        
                        Log::debug("Resultado arping para $ip: " . substr($output, 0, 200));
                        
                        // Normalmente arping muestra la MAC directamente
                        if (preg_match('/([0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2})/', $output, $matches)) {
                            return strtolower($matches[1]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::debug("Error con arping, usando ping estándar: " . $e->getMessage());
                }
            }
            
            // Bypass 4: Intentar técnicas múltiples de forzado ARP
            try {
                // Escanear puertos comunes para forzar comunicación
                $portsToScan = [22, 80, 443, 8080, 3389];
                foreach ($portsToScan as $port) {
                    $process = new \Symfony\Component\Process\Process(['sh', '-c', "nc -z -v -w1 $ip $port 2>/dev/null"]);
                    $process->setTimeout(2);
                    $process->run();
                }
                
                // Intentar ping con diferentes parámetros
                $pingParams = [
                    "-c 3 -W 2",
                    "-c 5 -W 3 -s 1472", // MTU grande
                    "-c 2 -W 1 -s 32"    // MTU pequeño
                ];
                
                foreach ($pingParams as $params) {
                    $pingCmd = "ping $params $ip > /dev/null 2>&1";
                    $process = new \Symfony\Component\Process\Process(['sh', '-c', $pingCmd]);
                    $process->setTimeout(6);
                    $process->run();
                }
            } catch (\Exception $e) {
                Log::debug("Error en técnicas de forzado ARP: " . $e->getMessage());
            }
            
            // Pequeña pausa para dar tiempo a que se actualice la tabla ARP
            usleep(800000); // 800ms
            
            // Ahora intentar obtener la MAC
            return $this->getMacFromArp($ip);
        } catch (\Exception $e) {
            Log::error("Error en getMacWithForcedArp: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Obtiene la dirección MAC para dispositivos de red específicos
     * @param string $ip Dirección IP del dispositivo
     * @return string|null MAC si se encuentra
     */
    protected function getNetworkDeviceMac($ip)
    {
        try {
            // Para dispositivos de red, probamos técnicas específicas
            
            // En Ubuntu/Linux podemos usar comandos más avanzados
            if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                // Comandos locales específicos para Ubuntu
                $commands = [
                    ['sh', '-c', "sudo nmap -sP $ip | grep -i mac"],
                    ['sh', '-c', "ping -c 4 -W 3 $ip && ip neigh show $ip"],
                    ['sh', '-c', "ip -s -s neigh flush $ip && ping -c 4 -W 3 $ip && ip neigh show $ip"],
                    // Bypass 1: Escaneo agresivo con nmap
                    ['sh', '-c', "sudo nmap -PR -sn $ip | grep -i mac"],
                    ['sh', '-c', "sudo nmap -sP -PE $ip | grep -i mac"],
                    // Bypass 2: Intentar consultar las tablas de ruta
                    ['sh', '-c', "ip route get $ip | cat"],
                    // Bypass 3: Técnicas para routers específicos
                    ['sh', '-c', "snmpget -v2c -c public $ip IP-MIB::ipNetToMediaPhysAddress.* 2>/dev/null"],
                    // Bypass 4: Forzar comunicación UDP
                    ['sh', '-c', "sudo hping3 -2 -p 53 -c 3 $ip 2>/dev/null && ip neigh show $ip"],
                    // Bypass 5: Para dispositivos específicos
                    ['sh', '-c', "echo -e '\n\n\nno\n' | telnet $ip 2>/dev/null && ip neigh show $ip"]
                ];
                
                foreach ($commands as $cmdArray) {
                    try {
                        $process = new \Symfony\Component\Process\Process($cmdArray);
                        $process->setTimeout(10); // Mayor timeout para escaneos
                        $process->run();
                        $output = $process->getOutput();
                        
                        Log::debug("Resultado consulta de ruta para $ip: " . substr($output, 0, 200));
                        
                        if (preg_match('/([0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2})/', $output, $matches)) {
                            return strtolower($matches[1]);
                        }
                    } catch (\Exception $e) {
                        Log::debug("Error en comando para dispositivo de red: " . $e->getMessage());
                        continue;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Error en getNetworkDeviceMac: " . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Obtiene la dirección MAC desde archivos DHCP
     * @param string $ip Dirección IP
     * @return string|null MAC si se encuentra
     */
    protected function getMacFromDhcpLeases($ip)
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            return null; // Solo funciona en Linux
        }
        
        try {
            $dhcpFiles = [
                '/var/lib/dhcp/dhclient.leases',
                '/var/lib/dhcp/dhcpd.leases',
                '/var/lib/dhcpd/dhcpd.leases',
                '/var/lib/NetworkManager/dhclient-*.lease',
                // Bypass 1: Ubicaciones adicionales de archivos DHCP
                '/var/lib/dhcp3/dhclient.leases',
                '/var/lib/NetworkManager/internal-*.lease',
                '/var/db/dhclient.leases.*',
                '/var/lib/systemd/network/leases'
            ];
            
            foreach ($dhcpFiles as $filePattern) {
                try {
                    // Usar glob para manejar patrones de archivos
                    $files = glob($filePattern);
                    if (empty($files)) continue;
                    
                    foreach ($files as $file) {
                        if (!file_exists($file)) continue;
                        
                        $contents = file_get_contents($file);
                        if (preg_match('/lease\s+' . preg_quote($ip) . '\s+{.*?hardware\s+ethernet\s+([0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2})/s', $contents, $matches)) {
                            return strtolower($matches[1]);
                        }
                        
                        // Bypass 2: Buscar patrones alternativos
                        if (preg_match('/fixed-address\s+' . preg_quote($ip) . '.*?hardware\s+ethernet\s+([0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2})/s', $contents, $matches)) {
                            return strtolower($matches[1]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::debug("Error leyendo archivo DHCP $filePattern: " . $e->getMessage());
                    continue;
                }
            }
            
            // Bypass 3: Buscar en archivos de configuración de red
            try {
                $cmd = ['sh', '-c', "grep -r $ip /etc/netplan/ /etc/NetworkManager/ /etc/network/ 2>/dev/null | grep -i mac"];
                $process = new \Symfony\Component\Process\Process($cmd);
                $process->setTimeout(5);
                $process->run();
                $output = $process->getOutput();
                
                if (preg_match('/([0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2})/', $output, $matches)) {
                    return strtolower($matches[1]);
                }
            } catch (\Exception $e) {
                Log::debug("Error buscando en archivos de configuración: " . $e->getMessage());
            }
        } catch (\Exception $e) {
            Log::error("Error en getMacFromDhcpLeases: " . $e->getMessage());
        }
        
        return null;
    }

    /**
     * Métodos avanzados para detectar MAC en entornos difíciles
     * @param string $ip Dirección IP
     * @return string|null MAC si se encuentra
     */
    protected function getAdvancedMacDetection($ip)
    {
        try {
            // Intentar solo con herramientas avanzadas reales que puedan obtener MACs reales
            if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                // Scan avanzado con nmap en modo privilegiado
                $commands = [
                    ['sh', '-c', "sudo nmap -sS -PR -n $ip && sudo arp -n $ip"],
                    ['sh', '-c', "sudo nmap --privileged -sn $ip && ip neigh show $ip"],
                    // Ataques ARP específicos para forzar respuesta
                    ['sh', '-c', "sudo arping -I $(ip route | grep default | awk '{print $5}') -c 5 $ip"],
                    // Tcpdump para capturar paquetes durante un ping
                    ['sh', '-c', "sudo timeout 3 tcpdump -i any -c 10 -n host $ip and arp 2>/dev/null & ping -c 3 $ip > /dev/null"]
                ];
                
                foreach ($commands as $cmdArray) {
                    try {
                        $process = new \Symfony\Component\Process\Process($cmdArray);
                        $process->setTimeout(15); // Mayor timeout para escaneos privilegiados
                        $process->run();
                        $output = $process->getOutput();
                        
                        if (preg_match('/([0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2})/', $output, $matches)) {
                            return strtolower($matches[1]);
                        }
                    } catch (\Exception $e) {
                        Log::debug("Error en comando avanzado: " . $e->getMessage());
                        continue;
                    }
                }
                
                // Si hay herramientas específicas instaladas
                try {
                    $installCheck = new \Symfony\Component\Process\Process(['sh', '-c', "which arp-scan 2>/dev/null"]);
                    $installCheck->run();
                    if ($installCheck->isSuccessful()) {
                        // Usar arp-scan que es más efectivo para detección real
                        $arpScan = new \Symfony\Component\Process\Process(['sh', '-c', "sudo arp-scan --localnet | grep $ip"]);
                        $arpScan->setTimeout(10);
                        $arpScan->run();
                        $output = $arpScan->getOutput();
                        
                        if (preg_match('/([0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2}:[0-9a-fA-F]{2})/', $output, $matches)) {
                            return strtolower($matches[1]);
                        }
                    }
                } catch (\Exception $e) {
                    Log::debug("Error verificando arp-scan: " . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            Log::error("Error en getAdvancedMacDetection: " . $e->getMessage());
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
            $host = \App\Models\Host::where('ip_address', $ip)->first();
            
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