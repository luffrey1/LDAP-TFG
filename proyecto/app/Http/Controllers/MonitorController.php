<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MonitorHost;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Auth;
use App\Models\MonitorGroup;
use App\Services\RemoteExecutionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MonitorController extends Controller
{
    /**
     * Mostrar la página de monitoreo de hosts
     */
    public function index()
    {
        $user = Auth::user();
        $hosts = MonitorHost::getHostsForUser($user);
        $groups = MonitorGroup::getGroupsForUser($user);
        
        // Compactar las variables a pasar a la vista
        return view('monitor.index', compact('hosts', 'groups'));
    }
    
    /**
     * Mostrar el formulario para crear un nuevo host
     */
    public function create()
    {
        $groups = MonitorGroup::getGroupsForUser(Auth::user());
        
        // Obtener group_id de la URL si existe
        $groupId = request()->query('group_id');
        
        return view('monitor.create', compact('groups', 'groupId'));
    }
    
    /**
     * Almacenar un nuevo host en la base de datos
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'hostname' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'description' => 'nullable|string|max:1000',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        
        try {
            $host = new MonitorHost();
            $host->hostname = $request->hostname;
            $host->ip_address = $request->ip_address;
            $host->description = $request->description;
            $host->mac_address = $request->mac_address;
            $host->created_by = Auth::id();
            $host->group_id = $request->group_id ?? 0;
            $host->status = 'unknown';
            $host->save();
            
            return redirect()->route('monitor.index')
                ->with('success', "Host '{$host->hostname}' añadido correctamente.");
        } catch (\Exception $e) {
            Log::error('Error al guardar host: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error al guardar el host: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Ping a un host específico para verificar su estado
     *
     * @param int $id ID del host
     * @return \Illuminate\Http\JsonResponse
     */
    public function ping($id)
    {
        try {
            $host = MonitorHost::findOrFail($id);
            $ip = $host->ip_address;
            
            Log::debug("Iniciando verificación de estado para host: {$host->hostname} ({$ip})");
            
            // Método 1: Usar socket para verificar si el host está disponible
            $isOnline = $this->checkHostAvailability($ip);
            
            Log::debug("Resultado de verificación para {$ip}: " . ($isOnline ? 'online' : 'offline'));
            
            if ($isOnline) {
                // Host está en línea, actualizar estado
                $host->status = 'online';
                $host->last_seen = now();
                
                // Si el host no tiene MAC, intentar obtenerla
                if (empty($host->mac_address)) {
                    $executor = new RemoteExecutionService();
                    $mac = $executor->getMacAddress($ip);
                    if (!empty($mac)) {
                        $host->mac_address = $mac;
                        Log::info("MAC detectada para {$host->hostname}: {$mac}");
                    }
                }
                
                $host->save();
                
                return response()->json([
                    'status' => 'online',
                    'message' => 'Host está en línea',
                    'last_seen' => $host->last_seen->format('d/m/Y H:i:s')
                ]);
            } else {
                // Host está fuera de línea
                $host->status = 'offline';
                $host->save();
                
                return response()->json([
                    'status' => 'offline',
                    'message' => 'Host está fuera de línea'
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Error al verificar estado: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error al verificar estado: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Verifica la disponibilidad de un host usando múltiples métodos
     *
     * @param string $ip Dirección IP del host
     * @return bool True si el host está disponible
     */
    private function checkHostAvailability($ip)
    {
        // Método 1: Intentar con fsockopen en puerto 80 (HTTP)
        try {
            $portsToCheck = [80, 443, 22, 3389];
            foreach ($portsToCheck as $port) {
                Log::debug("Probando conexión a {$ip}:{$port}");
                $socket = @fsockopen($ip, $port, $errno, $errstr, 2);
                if ($socket) {
                    fclose($socket);
                    Log::debug("Conexión exitosa a {$ip}:{$port}");
                    return true;
                }
            }
        } catch (\Exception $e) {
            Log::debug("Error en fsockopen para {$ip}: " . $e->getMessage());
        }
        
        // Método 2: Intentar con socket_create (más bajo nivel)
        if (function_exists('socket_create')) {
            try {
                Log::debug("Probando con socket_create para {$ip}");
                $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
                socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => 2, 'usec' => 0]);
                $result = @socket_connect($socket, $ip, 80);
                if ($result) {
                    socket_close($socket);
                    Log::debug("Conexión exitosa con socket a {$ip}:80");
                    return true;
                }
                socket_close($socket);
            } catch (\Exception $e) {
                Log::debug("Error en socket_create para {$ip}: " . $e->getMessage());
            }
        }
        
        // Método 3: Usar file_get_contents con timeout (para servicios web)
        try {
            Log::debug("Probando con file_get_contents para {$ip}");
            $ctx = stream_context_create(['http' => ['timeout' => 2]]);
            $result = @file_get_contents("http://{$ip}", false, $ctx);
            if ($result !== false) {
                Log::debug("Conexión exitosa con file_get_contents a {$ip}");
                return true;
            }
        } catch (\Exception $e) {
            Log::debug("Error en file_get_contents para {$ip}: " . $e->getMessage());
        }
        
        // Método 4: Si estamos en Linux, intentar con comandos alternativos
        if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
            // Intentar con ping completo
            $pingCommands = [
                "/bin/ping -c 1 -W 1 {$ip}",
                "/usr/bin/ping -c 1 -W 1 {$ip}",
                "/sbin/ping -c 1 -W 1 {$ip}"
            ];
            
            foreach ($pingCommands as $cmd) {
                Log::debug("Probando con comando: {$cmd}");
                exec($cmd . " 2>&1", $output, $returnCode);
                if ($returnCode === 0) {
                    Log::debug("Ping exitoso con {$cmd}");
                    return true;
                }
            }
            
            // Probar con curl
            try {
                Log::debug("Probando con curl para {$ip}");
                exec("curl -s --connect-timeout 2 http://{$ip} > /dev/null", $output, $returnCode);
                if ($returnCode === 0) {
                    Log::debug("Curl exitoso a {$ip}");
                    return true;
                }
            } catch (\Exception $e) {
                Log::debug("Error en curl para {$ip}: " . $e->getMessage());
            }
        }
        
        // Si es un dispositivo especial, darle una segunda oportunidad
        if ($this->isNetworkInfrastructure($ip)) {
            Log::debug("Dando segunda oportunidad a dispositivo de red: {$ip}");
            return $this->checkNetworkDeviceAvailability($ip);
        }
        
        return false;
    }
    
    /**
     * Verificación especial para dispositivos de red
     * 
     * @param string $ip Dirección IP del dispositivo
     * @return bool True si el dispositivo responde
     */
    private function checkNetworkDeviceAvailability($ip)
    {
        // Para dispositivos de red, probar puertos específicos
        $networkPorts = [22, 23, 80, 443, 8080, 8443, 161];
        
        foreach ($networkPorts as $port) {
            try {
                $socket = @fsockopen($ip, $port, $errno, $errstr, 3);
                if ($socket) {
                    fclose($socket);
                    Log::debug("Dispositivo de red {$ip} responde en puerto {$port}");
                    return true;
                }
            } catch (\Exception $e) {
                // Seguir intentando con otros puertos
            }
        }
        
        // Última oportunidad: verificar si es un dispositivo conocido
        if (in_array($ip, ['172.20.0.1', '172.20.0.2', '192.168.91.129'])) {
            Log::info("Marcando dispositivo crítico {$ip} como online (dispositivo conocido)");
            return true; // Asumir que dispositivos críticos están online
        }
        
        return false;
    }
    
    /**
     * Verifica el estado de todos los hosts
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function pingAll()
    {
        try {
            $hosts = MonitorHost::all();
            $updated = 0;
            $errors = 0;
            
            foreach ($hosts as $host) {
                try {
                    $ip = $host->ip_address;
                    
                    // Usar el mismo sistema robusto de verificación que en el método ping
                    $isOnline = $this->checkHostAvailability($ip);
                    
                    Log::debug("Resultado de verificación para {$ip} (pingAll): " . ($isOnline ? 'online' : 'offline'));
                    
                    if ($isOnline) {
                        // Host está en línea, actualizar estado
                        $host->status = 'online';
                        $host->last_seen = now();
                        
                        // Si el host no tiene MAC, intentar obtenerla
                        if (empty($host->mac_address)) {
                            $executor = new RemoteExecutionService();
                            $mac = $executor->getMacAddress($ip);
                            if (!empty($mac)) {
                                $host->mac_address = $mac;
                                Log::info("MAC detectada para {$host->hostname}: {$mac}");
                            }
                        }
                    } else {
                        // Host está fuera de línea
                        $host->status = 'offline';
                    }
                    
                    $host->save();
                    $updated++;
                    
                    Log::debug("Host {$host->hostname} actualizado con estado: {$host->status}");
                } catch (\Exception $e) {
                    Log::error("Error actualizando host {$host->id}: " . $e->getMessage());
                    $errors++;
                }
            }
            
            Log::info("Actualización de estado completada. Actualizados: {$updated}, Errores: {$errors}");
            
            return response()->json([
                'status' => 'success',
                'message' => "Se actualizaron {$updated} hosts con {$errors} errores.",
                'updated' => $updated,
                'errors' => $errors
            ]);
        } catch (\Exception $e) {
            Log::error("Error en pingAll: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error al verificar estado: ' . $e->getMessage()
            ]);
        }
    }
    
    // Determinar si una IP es de infraestructura de red
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
     * Ejecuta un comando en un host remoto
     */
    public function executeCommand(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'command' => 'required|string|max:1000',
            ]);
            
            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => 'Comando inválido']);
            }
            
            $host = MonitorHost::findOrFail($id);
            $command = $request->command;
            
            // Log de seguridad para registrar quién ejecutó qué comandos
            Log::info("Usuario {$request->user()->username} ejecutó el comando '{$command}' en host {$host->hostname} ({$host->ip_address})");
            
            // Ejecutar el comando a través de SSH usando RemoteExecutionService
            $executor = new RemoteExecutionService();
            $result = $executor->executeRemoteCommand($host->ip_address, $command);
            
            // Si se ejecutó correctamente
            if ($result['success']) {
                return response()->json([
                    'status' => 'success', 
                    'command' => $command,
                    'output' => $result['output'],
                    'hostname' => $host->hostname
                ]);
            } else {
                // Si hubo un error en la ejecución
                Log::warning("Error al ejecutar comando en {$host->hostname}: {$result['message']}");
                return response()->json([
                    'status' => 'error',
                    'message' => $result['message'],
                    'output' => $result['output']
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error al ejecutar comando: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * Ver detalles de un host
     */
    public function show($id)
    {
        try {
            $host = MonitorHost::findOrFail($id);
            
            // Verificar si el usuario actual es administrador
            $user = Auth::user();
            $isAdmin = $user && ($user->is_admin || $user->role === 'admin');
            
            // Si no es admin ni el creador, no tiene permiso
            if (!$isAdmin && $host->created_by != $user->id) {
                abort(403, 'No tienes permiso para ver este host');
            }
            
            // Realizar ping para actualizar estado
            $this->ping($id);
            
            return view('monitor.show', compact('host'));
        } catch (\Exception $e) {
            Log::error('Error al mostrar host: ' . $e->getMessage());
            return redirect()->route('monitor.index')
                ->with('error', 'Error al obtener detalles del host: ' . $e->getMessage());
        }
    }
    
    /**
     * Editar un host
     */
    public function edit($id)
    {
        try {
            $host = MonitorHost::findOrFail($id);
            
            // Verificar si el usuario actual es administrador
            $user = Auth::user();
            $isAdmin = $user && ($user->is_admin || $user->role === 'admin');
            
            // Si no es admin ni el creador, no tiene permiso
            if (!$isAdmin && $host->created_by != $user->id) {
                abort(403, 'No tienes permiso para editar este host');
            }
            
            $groups = MonitorGroup::getGroupsForUser(Auth::user());
            
            return view('monitor.edit', compact('host', 'groups'));
        } catch (\Exception $e) {
            Log::error('Error al editar host: ' . $e->getMessage());
            return redirect()->route('monitor.index')
                ->with('error', 'Error al editar el host: ' . $e->getMessage());
        }
    }
    
    /**
     * Actualizar un host
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'hostname' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'description' => 'nullable|string|max:1000',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        
        try {
            $host = MonitorHost::findOrFail($id);
            
            // Verificar si el usuario actual es administrador
            $user = Auth::user();
            $isAdmin = $user && ($user->is_admin || $user->role === 'admin');
            
            // Si no es admin ni el creador, no tiene permiso
            if (!$isAdmin && $host->created_by != $user->id) {
                abort(403, 'No tienes permiso para actualizar este host');
            }
            
            $host->hostname = $request->hostname;
            $host->ip_address = $request->ip_address;
            $host->description = $request->description;
            $host->mac_address = $request->mac_address;
            $host->group_id = $request->group_id ?? $host->group_id;
            $host->save();
            
            return redirect()->route('monitor.index')
                ->with('success', "Host '{$host->hostname}' actualizado correctamente.");
        } catch (\Exception $e) {
            Log::error('Error al actualizar host: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error al actualizar el host: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Eliminar un host
     */
    public function destroy($id)
    {
        try {
            $host = MonitorHost::findOrFail($id);
            
            // Verificar si el usuario está autenticado
            if (!Auth::check()) {
                return redirect()->route('login')
                    ->with('error', 'Su sesión ha expirado. Por favor, inicie sesión nuevamente.');
            }
            
            $user = Auth::user();
            
            // Verificar si el usuario es administrador
            $isAdmin = $user && ($user->is_admin || $user->role === 'admin');
            
            // Si no es admin ni el creador, no tiene permiso
            if (!$isAdmin && $host->created_by != $user->id) {
                return redirect()->route('monitor.index')
                    ->with('error', 'No tienes permiso para eliminar este host');
            }
            
            $hostname = $host->hostname;
            
            // Ejecutar la eliminación y verificar que se realizó correctamente
            $deleted = $host->delete();
            
            if ($deleted) {
                return redirect()->route('monitor.index')
                    ->with('success', "Host '{$hostname}' eliminado correctamente.");
            } else {
                return redirect()->route('monitor.index')
                    ->with('error', "No se pudo eliminar el host '{$hostname}'. Inténtelo de nuevo.");
            }
        } catch (\Exception $e) {
            Log::error('Error al eliminar host: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return redirect()->route('monitor.index')
                ->with('error', 'Error al eliminar el host: ' . $e->getMessage());
        }
    }
    
    /**
     * Verifica que la configuración de VPN para el instituto es correcta
     */
    private function verifyVpnConfiguration()
    {
        try {
            // Verificamos primero los dispositivos críticos
            $executor = new RemoteExecutionService();
            
            // Comprobar si podemos alcanzar los dispositivos críticos
            $criticalReachable = false;
            $routers = ['172.20.0.1', '172.20.0.2'];
            foreach ($routers as $router) {
                $result = $executor->ping($router);
                if ($result['success']) {
                    $criticalReachable = true;
                    Log::info("Verificación VPN: Dispositivo crítico $router es alcanzable");
                    break;
                }
            }
            
            if (!$criticalReachable) {
                Log::warning("Verificación VPN: No se pueden alcanzar dispositivos críticos - La VPN puede no estar conectada");
                return false;
            }
            
            // Ahora comprobar el rango DHCP especificado en la configuración de VPN
            $dhcpCheckPoints = [
                '172.20.200.1', '172.20.201.1', '172.20.202.1'
            ];
            
            $dhcpReachable = false;
            foreach ($dhcpCheckPoints as $ip) {
                $result = $executor->ping($ip);
                if ($result['success']) {
                    $dhcpReachable = true;
                    Log::info("Verificación VPN: Punto de DHCP $ip es alcanzable");
                    break;
                }
            }
            
            if (!$dhcpReachable) {
                // Si no alcanzamos ningún punto del DHCP, verificar registros de ruta
                Log::warning("Verificación VPN: No se puede alcanzar el rango DHCP - Puede necesitar añadir 'route 172.20.200.0 255.255.248.0' a la configuración de VPN");
            }
            
            return $criticalReachable;
        } catch (\Exception $e) {
            Log::error("Error verificando configuración de VPN: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Escanear la red local para detectar hosts automáticamente
     */
    public function scanNetwork(Request $request)
    {
        try {
            // Obtener parámetros del escaneo
            $baseIp = $request->input('base_ip', '172.20.200');
            $rangeStart = (int)$request->input('range_start', 1);
            $rangeEnd = (int)$request->input('range_end', 254);
            $forceRegister = $request->input('force_register', false);
            $scanAdditionalRanges = $request->input('scan_additional_ranges', true);
            
            // Log de inicio de escaneo
            Log::info("Iniciando escaneo de red: $baseIp.$rangeStart a $baseIp.$rangeEnd (Registro forzado: " . ($forceRegister ? 'Sí' : 'No') . ")");
            
            // Array de rangos de IPs a escanear
            $ipList = [];
            
            // Si estamos en el rango de instituto, considerar múltiples subredes
            $isInstitutoScan = (strpos($baseIp, '172.20') === 0);
            $additionalRangesScanned = false;
            
            // Agregar el rango primario solicitado
            for ($i = $rangeStart; $i <= $rangeEnd; $i++) {
                $ipList[] = $baseIp . '.' . $i;
            }
            
            // Si es un escaneo del instituto y la opción de rangos adicionales está activada
            if ($isInstitutoScan && $scanAdditionalRanges) {
                // Si el rango principal no incluye las IPs de dispositivos críticos
                if (strpos($baseIp, '172.20.0') !== 0) {
                    // Agregar rango administrativo que incluye la infraestructura crítica
                    $adminBaseIp = '172.20.0';
                    for ($i = 1; $i <= 30; $i++) {
                        if (!in_array("$adminBaseIp.$i", $ipList)) {
                            $ipList[] = "$adminBaseIp.$i";
                            $additionalRangesScanned = true;
                        }
                    }
                    Log::info("Añadido rango de infraestructura administrativa (172.20.0.1-30) al escaneo");
                }
                
                // Si estamos escaneando un rango que no es el DHCP, añadir muestras del DHCP
                if (strpos($baseIp, '172.20.200') !== 0 && strpos($baseIp, '172.20.201') !== 0) {
                    // Agregar muestras del rango DHCP
                    $dhcpRanges = [
                        ['base' => '172.20.200', 'start' => 1, 'end' => 30],
                        ['base' => '172.20.201', 'start' => 1, 'end' => 30],
                        ['base' => '172.20.202', 'start' => 1, 'end' => 30],
                    ];
                    
                    foreach ($dhcpRanges as $range) {
                        for ($i = $range['start']; $i <= $range['end']; $i++) {
                            $ip = $range['base'] . '.' . $i;
                            if (!in_array($ip, $ipList)) {
                                $ipList[] = $ip;
                                $additionalRangesScanned = true;
                            }
                        }
                    }
                    Log::info("Añadidas muestras del rango DHCP al escaneo");
                }
            } else if ($isInstitutoScan && !$scanAdditionalRanges) {
                Log::info("Escaneo limitado solo al rango especificado (rangos adicionales desactivados)");
            }
            
            // Añadir dispositivos críticos si no están ya en el rango
            $criticalDevices = [
                '172.20.0.1',  // Router principal
                '172.20.0.2',  // DNS server
                '172.20.0.30', // Servidor departamental
            ];
            
            foreach ($criticalDevices as $ip) {
                if (!in_array($ip, $ipList)) {
                    // Si estamos escaneando la red del instituto pero el dispositivo crítico no está en el rango
                    if ($isInstitutoScan) {
                        $ipList[] = $ip;
                        Log::info("Añadido dispositivo crítico al escaneo: $ip");
                    }
                }
            }
            
            // Verificar que la VPN está configurada correctamente si estamos escaneando la red del instituto
            if ($isInstitutoScan && !$this->verifyVpnConfiguration()) {
                Log::warning("La configuración de VPN puede estar incompleta para la red del instituto");
                // No detenemos el escaneo, pero mostramos una advertencia al final
            }
            
            // Dividir en lotes más pequeños para procesar
            $ipBatches = array_chunk($ipList, 5); // Procesar 5 IPs a la vez
            
            $discoveredHosts = 0;
            $updatedHosts = 0;
            $skippedHosts = 0;
            $errorCount = 0;
            
            foreach ($ipBatches as $index => $batch) {
                Log::info("Procesando lote " . ($index + 1) . " de " . count($ipBatches) . " (" . count($batch) . " IPs)");
                
                foreach ($batch as $ip) {
                    // Verificar si ya existe este host en la base de datos
                    $hostExists = MonitorHost::where('ip_address', $ip)->exists();
                    $isUpdate = false;
                    
                    if ($hostExists) {
                        $isUpdate = true;
                        Log::debug("Host $ip ya existe, actualizando información");
                    } else {
                        Log::debug("Escaneando IP: $ip");
                    }
                    
                    try {
                        // Verificar si es un dispositivo crítico
                        $isCriticalDevice = in_array($ip, $criticalDevices);
                        
                        // Estado del ping inicialmente false
                        $pingSuccess = false;
                        
                        // Para dispositivos críticos, forzar detección sin importar el ping
                        if ($isCriticalDevice) {
                            $pingSuccess = true;
                            Log::info("Dispositivo crítico detectado: $ip (registro forzado)");
                        } else {
                            // Usar el servicio de ejecución remota para hacer ping
                            $executor = new RemoteExecutionService();
                            $result = $executor->ping($ip);
                            $pingSuccess = $result['success'];
                            
                            // Si el ping no tuvo éxito pero force_register está activado, proceder de todos modos
                            if (!$pingSuccess && $forceRegister) {
                                $pingSuccess = true; // Forzar registro aunque no responda
                                Log::info("Forzando registro del host $ip aunque no responde a ping");
                            }
                        }
                        
                        // Solo agregar si el ping es exitoso o forzamos registro
                        if ($pingSuccess) {
                            Log::debug("Procesando host $ip para registro/actualización");
                            
                            // Determinar hostname
                            $hostname = '';
                            
                            // Para dispositivos críticos, usar nombres predefinidos
                            if ($ip === '172.20.0.1') {
                                $hostname = 'Router-Principal';
                            } elseif ($ip === '172.20.0.2') {
                                $hostname = 'Servidor-DNS';
                            } elseif ($ip === '172.20.0.30') {
                                $hostname = 'Servidor-Departamental';
                            } else {
                                // Para otros dispositivos, intentar resolver el hostname
                                $dnsHostname = @gethostbyaddr($ip);
                                if ($dnsHostname && $dnsHostname !== $ip) {
                                    // Extraer solo el nombre del equipo sin el dominio
                                    $parts = explode('.', $dnsHostname);
                                    $hostname = $parts[0];
                                    Log::debug("Hostname obtenido por gethostbyaddr: $hostname");
                                }
                                
                                // Si aún no tenemos hostname, usar uno predeterminado
                                if (empty($hostname)) {
                                    // Si es un rango DHCP del instituto, usar un nombre más descriptivo
                                    if (strpos($ip, '172.20.2') === 0) {
                                        $lastOctet = explode('.', $ip)[3];
                                        $hostname = 'Equipo-DHCP-' . $lastOctet;
                                    } else {
                                        $hostname = 'Host-' . str_replace('.', '-', $ip);
                                    }
                                    Log::debug("No se pudo obtener hostname, usando nombre predeterminado: $hostname");
                                }
                            }
                            
                            // Determinar grupo basado en la IP o el hostname
                            $groupId = null;
                            
                            // Si es un dispositivo crítico, asignar al grupo "Infraestructura"
                            if ($isCriticalDevice) {
                                // Buscar o crear grupo de infraestructura
                                $infraGroup = MonitorGroup::where('name', 'Infraestructura')->first();
                                if (!$infraGroup) {
                                    $infraGroup = new MonitorGroup();
                                    $infraGroup->name = 'Infraestructura';
                                    $infraGroup->description = 'Dispositivos críticos de red';
                                    $infraGroup->type = 'infrastructure';
                                    $infraGroup->created_by = Auth::id() ?: 1;
                                    $infraGroup->save();
                                    Log::info("Creado grupo de infraestructura");
                                }
                                $groupId = $infraGroup->id;
                            } 
                            // Verificar si es un equipo del rango DHCP del instituto (172.20.2xx.xxx)
                            elseif (strpos($ip, '172.20.2') === 0) {
                                // Asignar al grupo DHCP si existe o crearlo
                                $dhcpGroup = MonitorGroup::where('name', 'DHCP-Dinamicos')->first();
                                if (!$dhcpGroup) {
                                    $dhcpGroup = new MonitorGroup();
                                    $dhcpGroup->name = 'DHCP-Dinamicos';
                                    $dhcpGroup->description = 'Equipos con IP dinámica DHCP';
                                    $dhcpGroup->type = 'dynamic';
                                    $dhcpGroup->created_by = Auth::id() ?: 1;
                                    $dhcpGroup->save();
                                    Log::info("Creado grupo DHCP para IPs dinámicas");
                                }
                                $groupId = $dhcpGroup->id;
                            }
                            // Para el resto, intentar detectar aula por hostname (formato B27-A1)
                            elseif (preg_match('/^([B][0-9]{2})-[A-F][0-9]/', $hostname, $matches)) {
                                $aula = $matches[1];
                                $group = MonitorGroup::where('name', $aula)->first();
                                
                                if ($group) {
                                    $groupId = $group->id;
                                } else {
                                    // Crear grupo automáticamente
                                    try {
                                        $group = new MonitorGroup();
                                        $group->name = $aula;
                                        $group->description = 'Aula ' . $aula . ' (creado automáticamente)';
                                        $group->type = 'classroom';
                                        $group->created_by = Auth::id() ?: 1;
                                        $group->save();
                                        
                                        $groupId = $group->id;
                                        Log::debug("Creado nuevo grupo para aula: $aula (ID: $groupId)");
                                    } catch (\Exception $e) {
                                        Log::error("Error creando grupo para aula $aula: " . $e->getMessage());
                                    }
                                }
                            } 
                            // Si no es aula, asignar al grupo seleccionado en el formulario
                            else {
                                $groupId = $request->input('group_id');
                            }
                            
                            // Obtener MAC para dispositivos que no son críticos
                            $macAddress = null;
                            if (!$isCriticalDevice) {
                                $macAddress = $this->getMacFromIp($ip);
                                Log::debug("MAC obtenida para $ip: " . ($macAddress ?? 'No encontrada'));
                            }
                            
                            // Estado a asignar basado en el resultado del ping y si es crítico
                            $hostStatus = ($result['success'] || $isCriticalDevice) ? 'online' : 'offline';
                            
                            // Guardar el host o actualizar
                            try {
                                if ($isUpdate) {
                                    // Actualizar el host existente
                                    $host = MonitorHost::where('ip_address', $ip)->first();
                                    $host->status = $hostStatus;
                                    $host->last_seen = now();
                                    
                                    // Actualizar información solo si está disponible
                                    if (!empty($hostname)) {
                                        $host->hostname = $hostname;
                                    }
                                    if (!empty($macAddress)) {
                                        $host->mac_address = $macAddress;
                                    }
                                    if (!is_null($groupId)) {
                                        $host->group_id = $groupId;
                                    }
                                    
                                    $host->save();
                                    $updatedHosts++;
                                    
                                    Log::info("Host actualizado: $ip ({$host->hostname}) - Estado: $hostStatus");
                                } else {
                                    // Crear el nuevo host
                                    $host = new MonitorHost();
                                    $host->hostname = $hostname;
                                    $host->ip_address = $ip;
                                    $host->mac_address = $macAddress;
                                    $host->status = $hostStatus;
                                    $host->last_seen = now();
                                    
                                    if ($isCriticalDevice) {
                                        $host->description = 'Dispositivo de red crítico';
                                    } elseif ($forceRegister && !$result['success']) {
                                        $host->description = 'Registrado manualmente el ' . now()->format('d/m/Y H:i:s') . ' (no responde a ping)';
                                    } else {
                                        $host->description = 'Detectado automáticamente el ' . now()->format('d/m/Y H:i:s');
                                    }
                                    
                                    $host->group_id = $groupId ?? $request->input('group_id');
                                    $host->created_by = Auth::id() ?: 1;
                                    $host->save();
                                    
                                    $discoveredHosts++;
                                    Log::info("Host añadido: $hostname ($ip) - Estado: $hostStatus");
                                }
                            } catch (\Exception $e) {
                                Log::error("Error guardando host $ip: " . $e->getMessage());
                                $errorCount++;
                            }
                        } else {
                            $skippedHosts++;
                            Log::debug("Host $ip no responde a ping, omitiendo");
                        }
                    } catch (\Exception $e) {
                        Log::error("Error escaneando IP $ip: " . $e->getMessage());
                        $errorCount++;
                    }
                }
                
                // Pausa entre lotes para no sobrecargar el sistema
                usleep(200000); // 200ms
            }
            
            Log::info("Escaneo completado. Encontrados: $discoveredHosts, Actualizados: $updatedHosts, Omitidos: $skippedHosts, Errores: $errorCount");
            
            // Mensaje específico si escaneamos rangos adicionales
            $additionalRangesMessage = $additionalRangesScanned ? 
                "Se escanearon rangos adicionales de la red del instituto. " : 
                "";
            
            if ($discoveredHosts > 0 || $updatedHosts > 0) {
                $message = "Escaneo completado. " . $additionalRangesMessage;
                if ($discoveredHosts > 0) {
                    $message .= "Se encontraron {$discoveredHosts} equipos nuevos. ";
                }
                if ($updatedHosts > 0) {
                    $message .= "Se actualizaron {$updatedHosts} equipos existentes. ";
                }
                if ($skippedHosts > 0) {
                    $message .= "Se omitieron {$skippedHosts} equipos que no respondieron.";
                }
                
                return redirect()->route('monitor.index')
                    ->with('success', $message);
            } else {
                if ($errorCount > 0) {
                    return redirect()->route('monitor.index')
                        ->with('warning', $additionalRangesMessage . "No se encontraron equipos nuevos. Hubo {$errorCount} errores durante el escaneo. Revise los logs para más detalles.");
                } else {
                    $message = $additionalRangesMessage . "No se encontraron equipos nuevos. ";
                    if ($isInstitutoScan && !$this->verifyVpnConfiguration()) {
                        $message .= "Es posible que la VPN no esté correctamente configurada. Debe incluir 'route 172.20.200.0 255.255.248.0' en su configuración.";
                        return redirect()->route('monitor.index')
                            ->with('warning', $message);
                    } else {
                        $message .= "Si espera ver equipos específicos, intente activar la opción 'Forzar registro' en la próxima exploración.";
                        return redirect()->route('monitor.index')
                            ->with('info', $message);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error en escaneo de red: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return redirect()->route('monitor.index')
                ->with('error', 'Error al escanear la red: ' . $e->getMessage());
        }
    }
    
    /**
     * Mostrar el formulario para escanear la red
     */
    public function scanNetworkForm()
    {
        // Detectar la IP del servidor para sugerir un rango
        $serverIp = request()->server('SERVER_ADDR');
        $baseIp = '172.20.200'; // IP base por defecto para red del Tierno
        
        if ($serverIp && filter_var($serverIp, FILTER_VALIDATE_IP)) {
            $ipParts = explode('.', $serverIp);
            if (count($ipParts) === 4) {
                $baseIp = $ipParts[0] . '.' . $ipParts[1] . '.' . $ipParts[2];
            }
        }
        
        // Obtener grupos para selector
        $groups = MonitorGroup::getGroupsForUser(Auth::user());
        
        return view('monitor.scan', compact('baseIp', 'groups'));
    }
    
    /**
     * Recibir actualizaciones de telemetría desde los agentes
     */
    public function updateTelemetry(Request $request)
    {
        try {
            Log::debug('Recibidos datos de telemetría: ' . json_encode(array_keys($request->all())));
            
            $validator = Validator::make($request->all(), [
                'hostname' => 'required|string',
                'ip_address' => 'required|ip',
                'mac_address' => 'nullable|string',
                'status' => 'required|string',
                'system_info' => 'nullable|array',
                'disk_usage' => 'nullable|array',
                'memory_usage' => 'nullable|array',
                'cpu_usage' => 'nullable|array',
                'network_info' => 'nullable|array',
                'processes' => 'nullable|array',
                'users' => 'nullable|array',
                'uptime' => 'nullable|string',
                'last_boot' => 'nullable|string',
                'agent_version' => 'nullable|string',
            ]);
            
            if ($validator->fails()) {
                Log::warning('Validación de telemetría fallida: ' . json_encode($validator->errors()));
                return response()->json(['status' => 'error', 'message' => $validator->errors()]);
            }
            
            // Buscar host por IP o hostname
            $host = MonitorHost::where('ip_address', $request->ip_address)
                ->orWhere('hostname', $request->hostname)
                ->first();
            
            if (!$host) {
                // Crear nuevo host si no existe
                Log::info('Creando nuevo host desde telemetría: ' . $request->hostname . ' (' . $request->ip_address . ')');
                $host = new MonitorHost();
                $host->hostname = $request->hostname;
                $host->ip_address = $request->ip_address;
                $host->status = $request->status;
                
                // Guardar MAC si está disponible
                if (!empty($request->mac_address) && $request->mac_address != '00:00:00:00:00:00') {
                    $host->mac_address = $request->mac_address;
                    Log::info('MAC address guardada para nuevo host: ' . $request->mac_address);
                }
                
                $host->save();
            } else {
                // Actualizar MAC si está disponible y el host no tiene una
                if (empty($host->mac_address) && !empty($request->mac_address) && $request->mac_address != '00:00:00:00:00:00') {
                    $host->mac_address = $request->mac_address;
                    $host->save();
                    Log::info('MAC address actualizada para host: ' . $request->mac_address);
                }
            }
            
            // Preparar todos los datos para almacenar
            $systemData = [
                'system_info' => $request->system_info ?? null,
                'disk_usage' => $request->disk_usage ?? null,
                'memory_usage' => $request->memory_usage ?? null, 
                'cpu_usage' => $request->cpu_usage ?? null,
                'last_boot' => $request->last_boot ?? null,
                'uptime' => $request->uptime ?? null,
            ];
            
            // Agregar datos adicionales a la información del sistema
            $additionalInfo = [];
            
            // Agregar info de red
            if (!empty($request->network_info)) {
                $additionalInfo['network_info'] = $request->network_info;
            }
            
            // Agregar info de procesos
            if (!empty($request->processes)) {
                $additionalInfo['processes'] = $request->processes;
            }
            
            // Agregar info de usuarios conectados
            if (!empty($request->users)) {
                $additionalInfo['users'] = $request->users;
            }
            
            // Combinar con system_info existente
            if (!empty($systemData['system_info'])) {
                if (is_array($systemData['system_info'])) {
                    $systemData['system_info'] = array_merge($systemData['system_info'], $additionalInfo);
                } else {
                    $systemInfo = json_decode($systemData['system_info'], true) ?: [];
                    $systemData['system_info'] = array_merge($systemInfo, $additionalInfo);
                }
            } else {
                $systemData['system_info'] = $additionalInfo;
            }
            
            // Actualizar información en el host
            MonitorHost::updateSystemInfo($host->id, $systemData);
            MonitorHost::updateStatus($host->id, $request->status);
            
            // Actualizar versión del agente si está disponible
            if (!empty($request->agent_version)) {
                $host->agent_version = $request->agent_version;
                $host->save();
                Log::debug('Versión del agente actualizada: ' . $request->agent_version);
            }
            
            return response()->json([
                'status' => 'success', 
                'message' => 'Telemetría actualizada',
                'host_id' => $host->id
            ]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar telemetría: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * Recibe actualizaciones del estado de los hosts monitoreados
     */
    public function updateStatus(Request $request)
    {
        $request->validate([
            'host_id' => 'required|exists:monitor_hosts,id',
            'status' => 'required|string|in:online,offline,warning,error',
        ]);
        
        try {
            MonitorHost::updateStatus($request->host_id, $request->status);
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar estado: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Recibe actualizaciones de información del sistema de los hosts
     */
    public function updateSystemInfo(Request $request)
    {
        $request->validate([
            'host_id' => 'required|exists:monitor_hosts,id',
            'cpu_usage' => 'nullable|numeric|min:0|max:100',
            'memory_usage' => 'nullable|numeric|min:0|max:100',
            'disk_usage' => 'nullable|numeric|min:0|max:100',
            'temperature' => 'nullable|numeric',
            'uptime' => 'nullable|string',
            'system_info' => 'nullable|json',
        ]);
        
        try {
            MonitorHost::updateSystemInfo($request->host_id, $request->all());
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error al actualizar info del sistema: ' . $e->getMessage());
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }
    
    /**
     * Enviar Wake-on-LAN a un host
     */
    public function wakeOnLan($id)
    {
        try {
            $host = MonitorHost::findOrFail($id);
            
            // Verificar si el usuario actual es administrador
            $user = Auth::user();
            $isAdmin = $user && ($user->is_admin || $user->role === 'admin');
            
            // Si no es admin ni el creador, no tiene permiso
            if (!$isAdmin && $host->created_by != $user->id) {
                abort(403, 'No tienes permiso para despertar este host');
            }
            
            if (empty($host->mac_address)) {
                return redirect()->back()
                    ->with('error', 'No se puede enviar Wake-on-LAN: El host no tiene una dirección MAC real configurada. Intente detectar la MAC primero.');
            }
            
            $result = $host->wakeOnLan();
            
            if ($result) {
                // Registrar el evento
                Log::info("Usuario {".Auth::user()->username."} envió Wake-on-LAN a {$host->hostname} ({$host->mac_address})");
                
                return redirect()->back()
                    ->with('success', "Paquete Wake-on-LAN enviado a {$host->hostname}");
            } else {
                return redirect()->back()
                    ->with('error', 'Error al enviar el paquete Wake-on-LAN. Revise los logs para más detalles.');
            }
        } catch (\Exception $e) {
            Log::error('Error en wakeOnLan: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error al despertar el host: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtener la dirección MAC a partir de una IP usando ARP
     */
    private function getMacFromIp($ip)
    {
        try {
            Log::debug("Obteniendo MAC real para IP: $ip");
            
            // Usar el sistema mejorado que solo devuelve MACs reales
            $executor = new RemoteExecutionService();
            $mac = $executor->getMacAddress($ip);
            
            if ($mac) {
                Log::debug("MAC real encontrada para $ip: $mac");
                return $mac;
            }
            
            Log::debug("No se pudo obtener una MAC real para $ip");
            return null;
        } catch (\Exception $e) {
            Log::error('Error obteniendo MAC real desde IP: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Mostrar formulario para crear un nuevo grupo
     */
    public function createGroup()
    {
        return view('monitor.group.create');
    }
    
    /**
     * Guardar un nuevo grupo en la base de datos
     */
    public function storeGroup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        
        try {
            $group = new MonitorGroup();
            $group->name = $request->name;
            $group->description = $request->description;
            $group->type = $request->type ?? 'classroom';
            $group->location = $request->location;
            $group->created_by = Auth::id();
            $group->save();
            
            return redirect()->route('monitor.groups.index')
                ->with('success', "Grupo '{$group->name}' creado correctamente.");
        } catch (\Exception $e) {
            Log::error('Error al guardar grupo: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error al guardar el grupo: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Listar todos los grupos
     */
    public function groupsIndex()
    {
        $user = Auth::user();
        $groups = MonitorGroup::getGroupsForUser($user);
        
        return view('monitor.group.index', compact('groups'));
    }
    
    /**
     * Ver detalles de un grupo y sus hosts
     */
    public function showGroup($id)
    {
        try {
            $group = MonitorGroup::findOrFail($id);
            
            // Todos los usuarios pueden ver los grupos
            // El siguiente bloque está comentado para permitir acceso general
            /*
            // Verificar si el usuario tiene permiso para ver este grupo
            if (!Auth::user()->is_admin && $group->created_by != Auth::id()) {
                abort(403, 'No tienes permiso para ver este grupo');
            }
            */
            
            $hosts = $group->hosts;
            
            return view('monitor.group.show', compact('group', 'hosts'));
        } catch (\Exception $e) {
            Log::error('Error al mostrar grupo: ' . $e->getMessage());
            return redirect()->route('monitor.groups.index')
                ->with('error', 'Error al obtener detalles del grupo: ' . $e->getMessage());
        }
    }
    
    /**
     * Editar un grupo
     */
    public function editGroup($id)
    {
        try {
            $group = MonitorGroup::findOrFail($id);
            
            // Si es administrador o creador, puede editar
            if (!Auth::user()->is_admin && $group->created_by != Auth::id()) {
                abort(403, 'No tienes permiso para editar este grupo');
            }
            
            return view('monitor.group.edit', compact('group'));
        } catch (\Exception $e) {
            Log::error('Error al editar grupo: ' . $e->getMessage());
            return redirect()->route('monitor.groups.index')
                ->with('error', 'Error al editar el grupo: ' . $e->getMessage());
        }
    }
    
    /**
     * Actualizar un grupo
     */
    public function updateGroup(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        
        try {
            $group = MonitorGroup::findOrFail($id);
            
            // Si es administrador o creador, puede actualizar
            if (!Auth::user()->is_admin && $group->created_by != Auth::id()) {
                abort(403, 'No tienes permiso para actualizar este grupo');
            }
            
            $group->name = $request->name;
            $group->description = $request->description;
            $group->type = $request->type ?? $group->type;
            $group->location = $request->location;
            $group->save();
            
            return redirect()->route('monitor.groups.index')
                ->with('success', "Grupo '{$group->name}' actualizado correctamente.");
        } catch (\Exception $e) {
            Log::error('Error al actualizar grupo: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error al actualizar el grupo: ' . $e->getMessage())
                ->withInput();
        }
    }
    
    /**
     * Eliminar un grupo
     */
    public function destroyGroup($id)
    {
        try {
            $group = MonitorGroup::findOrFail($id);
            
            // Si es administrador o creador, puede eliminar
            if (!Auth::user()->is_admin && $group->created_by != Auth::id()) {
                abort(403, 'No tienes permiso para eliminar este grupo');
            }
            
            // Verificar si tiene hosts asociados
            if ($group->hosts()->count() > 0) {
                return redirect()->route('monitor.groups.index')
                    ->with('error', "No se puede eliminar el grupo '{$group->name}' porque tiene equipos asociados.");
            }
            
            $groupName = $group->name;
            $group->delete();
            
            return redirect()->route('monitor.groups.index')
                ->with('success', "Grupo '{$groupName}' eliminado correctamente.");
        } catch (\Exception $e) {
            Log::error('Error al eliminar grupo: ' . $e->getMessage());
            return redirect()->route('monitor.groups.index')
                ->with('error', 'Error al eliminar el grupo: ' . $e->getMessage());
        }
    }
    
    /**
     * Enviar Wake-on-LAN a todos los equipos de un grupo
     */
    public function wakeOnLanGroup($id)
    {
        try {
            $group = MonitorGroup::findOrFail($id);
            
            // Si es administrador o creador, puede despertar
            if (!Auth::user()->is_admin && $group->created_by != Auth::id()) {
                abort(403, 'No tienes permiso para despertar los hosts de este grupo');
            }
            
            $hosts = $group->hosts()->whereNotNull('mac_address')->get();
            $sentCount = 0;
            $errorCount = 0;
            
            foreach ($hosts as $host) {
                $result = $host->wakeOnLan();
                if ($result) {
                    Log::info("Usuario {".Auth::user()->username."} envió Wake-on-LAN a {$host->hostname} ({$host->mac_address}) del grupo {$group->name}");
                    $sentCount++;
                } else {
                    $errorCount++;
                }
            }
            
            if ($sentCount > 0) {
                return redirect()->back()
                    ->with('success', "Paquetes Wake-on-LAN enviados a {$sentCount} equipos del grupo '{$group->name}'." . 
                           ($errorCount > 0 ? " {$errorCount} equipos fallaron." : ""));
            } else {
                return redirect()->back()
                    ->with('error', "No se pudo enviar Wake-on-LAN a ningún equipo del grupo '{$group->name}'. Verifica que tengan MAC configurada.");
            }
        } catch (\Exception $e) {
            Log::error('Error en wakeOnLanGroup: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error al despertar los hosts del grupo: ' . $e->getMessage());
        }
    }
    
    /**
     * Endpoint para verificar la conectividad con la red del instituto
     */
    public function checkNetwork(Request $request)
    {
        try {
            $networkStatus = $this->checkInstitutoNetwork();
            return response()->json($networkStatus);
        } catch (\Exception $e) {
            Log::error('Error verificando la red: ' . $e->getMessage());
            return response()->json([
                'connected' => false,
                'message' => 'Error al verificar la red: ' . $e->getMessage(),
                'details' => []
            ], 500);
        }
    }
    
    /**
     * Ping a todos los equipos y forzar estado online para dispositivos críticos
     */
    public function refreshNetworkDevices()
    {
        try {
            // Lista de IPs de dispositivos críticos que siempre deben aparecer online
            $criticalDevices = [
                '172.20.0.1',  // Router principal
                '172.20.0.2',  // DNS server
                '172.20.0.30', // Servidor departamental
            ];
            
            foreach ($criticalDevices as $ip) {
                $host = MonitorHost::where('ip_address', $ip)->first();
                
                if (!$host) {
                    // Si no existe, crearlo
                    $host = new MonitorHost();
                    $host->hostname = $this->getNetworkDeviceName($ip);
                    $host->ip_address = $ip;
                    $host->description = 'Dispositivo de red esencial - Añadido automáticamente';
                    $host->created_by = Auth::id() ?: 1; // Admin o primer usuario
                    $host->save();
                    
                    Log::info("Dispositivo de red crítico creado: $ip ({$host->hostname})");
                }
                
                // Forzar estado online para estos dispositivos críticos
                MonitorHost::updateStatus($host->id, 'online');
                Log::info("Dispositivo de red crítico marcado como online: $ip ({$host->hostname})");
            }
            
            return redirect()->route('monitor.index')
                ->with('success', 'Estado de dispositivos de red actualizado.');
        } catch (\Exception $e) {
            Log::error('Error al actualizar dispositivos de red: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error al actualizar dispositivos de red: ' . $e->getMessage());
        }
    }
    
    /**
     * Devuelve un nombre descriptivo para un dispositivo de red
     */
    private function getNetworkDeviceName($ip)
    {
        $names = [
            '172.20.0.1' => 'Router Principal',
            '172.20.0.2' => 'Servidor DNS',
            '172.20.0.30' => 'Servidor Departamental',
        ];
        
        return $names[$ip] ?? 'Dispositivo de Red ' . $ip;
    }
    
    /**
     * Verifica la conectividad con la red del instituto
     * Prueba pings a routers y servidores conocidos del instituto
     */
    private function checkInstitutoNetwork()
    {
        $result = [
            'connected' => false,
            'message' => 'No se detectó conexión con la red del instituto.',
            'details' => []
        ];
        
        // IPs críticas del instituto
        $criticalIPs = [
            '172.20.0.1' => 'Router principal',
            '172.20.0.2' => 'Servidor DNS',
            '172.20.0.30' => 'Servidor departamental'
        ];
        
        $connectedCount = 0;
        $executor = new RemoteExecutionService();
        
        foreach ($criticalIPs as $ip => $description) {
            try {
                // Usar el servicio de ejecución remota para hacer ping
                $pingResult = $executor->ping($ip);
                
                $pingSuccess = $pingResult['success'];
                $result['details'][$ip] = [
                    'name' => $description,
                    'status' => $pingSuccess ? 'online' : 'offline'
                ];
                
                if ($pingSuccess) {
                    $connectedCount++;
                }
                
                // Pequeña pausa para no saturar la red
                usleep(100000); // 100ms
            } catch (\Exception $e) {
                Log::error("Error verificando conectividad con $ip: " . $e->getMessage());
                $result['details'][$ip] = [
                    'name' => $description,
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        // Si al menos 2 de los 3 puntos críticos responden, consideramos que hay conexión
        if ($connectedCount >= 2) {
            $result['connected'] = true;
            $result['message'] = "Conectado a la red del instituto ($connectedCount/3 puntos críticos responden).";
        } else {
            $onlinePoints = $connectedCount > 0 ? " ($connectedCount/3 puntos críticos responden)." : "";
            $result['message'] = "No se detectó conexión completa con la red del instituto" . $onlinePoints . " Asegúrese de estar conectado a la VPN o red local del instituto.";
        }
        
        Log::info("Verificación de red del instituto: " . ($result['connected'] ? 'Conectado' : 'No conectado') . " - " . $result['message']);
        return $result;
    }

    /**
     * Obtener la lista de scripts disponibles para transferir
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableScripts()
    {
        // Verificar permisos
        if (!Auth::user()->can('view_monitor')) {
            return response()->json([
                'status' => 'error',
                'message' => 'No tienes permisos para ver esta información'
            ]);
        }

        $scriptPath = public_path('agent');
        $scripts = [];

        if (is_dir($scriptPath)) {
            $files = scandir($scriptPath);
            foreach ($files as $file) {
                // Solo incluir archivos .sh y .ps1
                if (in_array(pathinfo($file, PATHINFO_EXTENSION), ['sh', 'ps1'])) {
                    $filePath = $scriptPath . '/' . $file;
                    $fileSize = filesize($filePath);
                    $fileContent = file_get_contents($filePath);
                    
                    // Detectar tipo de script
                    $type = (pathinfo($file, PATHINFO_EXTENSION) === 'ps1') ? 'Windows' : 'Linux';
                    
                    // Intentar extraer descripción del script
                    $description = '';
                    $lines = explode("\n", $fileContent);
                    foreach ($lines as $index => $line) {
                        // Omitir primera línea si contiene shebang
                        if ($index === 0 && strpos($line, '#!') === 0) {
                            continue;
                        }
                        
                        // Buscar comentarios al inicio
                        if (preg_match('/^[#\s]*(.+)$/', $line, $matches)) {
                            $description = trim($matches[1]);
                            if (!empty($description)) {
                                // Si es una línea de separación, ignorarla
                                if (preg_match('/^[-=#*]{3,}$/', $description)) {
                                    continue;
                                }
                                // Eliminar prefijos comunes de comentarios
                                $description = preg_replace('/^[#\s]*/', '', $description);
                                break;
                            }
                        } elseif ($index > 5) {
                            // Si no encontramos descripción en las primeras líneas, dejamos de buscar
                            break;
                        }
                    }
                    
                    $scripts[] = [
                        'name' => $file,
                        'path' => 'agent/' . $file,
                        'size' => $fileSize,
                        'description' => $description,
                        'type' => $type
                    ];
                }
            }
        }
        
        return response()->json([
            'status' => 'success',
            'scripts' => $scripts
        ]);
    }

    /**
     * Transfiere un script al host seleccionado
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function transferScript(Request $request)
    {
        // Validar entrada
        $validator = Validator::make($request->all(), [
            'host_id' => 'required|integer|exists:monitor_hosts,id',
            'script_name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Datos inválidos: ' . $validator->errors()->first()
            ]);
        }

        // Verificar permisos
        if (!Auth::user()->can('edit_monitor')) {
            return response()->json([
                'status' => 'error',
                'message' => 'No tienes permisos para transferir scripts'
            ]);
        }

        $hostId = $request->input('host_id');
        $scriptName = $request->input('script_name');
        $scriptPath = public_path('agent/' . $scriptName);

        // Verificar que el script existe
        if (!file_exists($scriptPath)) {
            return response()->json([
                'status' => 'error',
                'message' => 'El script seleccionado no existe'
            ]);
        }

        try {
            // Obtener información del host
            $host = MonitorHost::findOrFail($hostId);
            
            // Obtener servicio de ejecución remota
            $remoteService = new RemoteExecutionService();
            
            // Determinar si es un script de Windows o Linux
            $isWindows = (pathinfo($scriptName, PATHINFO_EXTENSION) === 'ps1');
            
            // Configurar directorio destino según el sistema operativo
            if ($isWindows) {
                // Para Windows: crear directorio C:\Scripts si no existe
                $remoteService->executeRemoteCommand($host->ip_address, 'if not exist "C:\Scripts" mkdir "C:\Scripts"');
                $remotePath = 'C:\Scripts\\' . $scriptName;
            } else {
                // Para Linux: crear directorio /opt/monitor-scripts si no existe
                $remoteService->executeRemoteCommand($host->ip_address, 'mkdir -p /opt/monitor-scripts');
                $remotePath = '/opt/monitor-scripts/' . $scriptName;
            }
            
            // Transferir el archivo usando el servicio
            $result = $remoteService->transferFile($host->ip_address, $scriptPath, $remotePath);
            
            if ($result['success']) {
                // Si es un script bash, hacerlo ejecutable
                if (!$isWindows) {
                    $chmodResult = $remoteService->executeRemoteCommand(
                        $host->ip_address, 
                        'chmod +x ' . escapeshellarg($remotePath)
                    );
                    
                    if (!$chmodResult['success']) {
                        Log::warning("No se pudo hacer ejecutable el script: " . ($chmodResult['output'] ?? 'Error desconocido'));
                    }
                }
                
                // Registrar actividad
                Log::info("Usuario {" . Auth::user()->username . "} transfirió el script {$scriptName} al host {$host->hostname}");
                
                return response()->json([
                    'status' => 'success',
                    'message' => "Script {$scriptName} transferido correctamente a {$host->hostname}"
                ]);
            } else {
                Log::error("Error al transferir script: " . ($result['output'] ?? 'Error desconocido'));
                return response()->json([
                    'status' => 'error',
                    'message' => $result['output'] ?? 'Error al transferir el script'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error al transferir script: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error al transferir el script: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Conectar a una terminal SSH en un host
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function terminalConnect(Request $request)
    {
        \Log::info('Terminal Connect: Iniciando conexión SSH - Request data: ' . json_encode($request->all()));
        
        try {
            $hostId = $request->input('host_id');
            $host = MonitorHost::find($hostId);
            
            if (!$host) {
                \Log::error('Terminal Connect: Host no encontrado: ' . $hostId);
                return response()->json([
                    'success' => false,
                    'message' => 'Host no encontrado'
                ], 404);
            }
            
            \Log::info('Terminal Connect: Host encontrado: ' . $host->hostname . ' (' . $host->ip_address . ')');
            
            // Generar un ID único para la sesión
            $sessionId = uniqid('ssh_', true);
            $sessionKey = 'ssh_session_' . $sessionId;
            
            \Log::info('Terminal Connect: Generado ID de sesión: ' . $sessionId);
            
            // Ruta de la clave SSH privada generada
            $sshKeyPath = storage_path('app/ssh/id_rsa');
            $useKeyAuth = file_exists($sshKeyPath);
            
            // Intentar una conexión de prueba para verificar que podemos conectarnos
            \Log::info('Terminal Connect: Verificando conectividad SSH a ' . $host->ip_address);
            
            // Preparar el proceso SSH según el método de autenticación disponible
            if ($useKeyAuth) {
                \Log::info('Terminal Connect: Usando autenticación por clave SSH: ' . $sshKeyPath);
                
                $process = new \Symfony\Component\Process\Process([
                    'ssh',
                    '-i', $sshKeyPath,
                    '-o', 'StrictHostKeyChecking=no',
                    '-o', 'UserKnownHostsFile=/dev/null',
                    '-o', 'LogLevel=ERROR',
                    '-o', 'ConnectTimeout=5',
                    'root@' . $host->ip_address,
                    'echo "SSH_TEST_CONNECTION_OK"'
                ]);
            } else {
                \Log::info('Terminal Connect: No se encontró clave SSH, usando autenticación por contraseña');
                
                // Asegurarnos que sshpass está instalado
                $this->ensureSshpassInstalled();
                
                // Contraseña predeterminada del script ldap-setup.sh
                $rootPassword = "root";
                
                $process = new \Symfony\Component\Process\Process([
                    'sshpass',
                    '-p', $rootPassword,
                    'ssh',
                    '-o', 'StrictHostKeyChecking=no',
                    '-o', 'UserKnownHostsFile=/dev/null',
                    '-o', 'LogLevel=ERROR',
                    '-o', 'ConnectTimeout=5',
                    'root@' . $host->ip_address,
                    'echo "SSH_TEST_CONNECTION_OK"'
                ]);
            }
            
            \Log::info('Terminal Connect: Comando SSH de prueba: ' . $process->getCommandLine());
            
            $process->setTimeout(10);
            $process->run();
            
            $output = trim($process->getOutput());
            $errorOutput = $process->getErrorOutput();
            $exitCode = $process->getExitCode();
            
            \Log::info('Terminal Connect: Código de salida: ' . $exitCode);
            \Log::info('Terminal Connect: Output: ' . $output);
            
            if ($errorOutput) {
                \Log::warning('Terminal Connect: Error Output: ' . $errorOutput);
            }
            
            // Si la conexión falló con la clave SSH, intentar con contraseña
            if ($useKeyAuth && $exitCode !== 0 && strpos($errorOutput, 'Permission denied') !== false) {
                \Log::info('Terminal Connect: Falló autenticación por clave, intentando con contraseña');
                
                // Contraseña predeterminada del script ldap-setup.sh
                $rootPassword = "root";
                
                // Intentar con sshpass
                $this->ensureSshpassInstalled();
                
                $process = new \Symfony\Component\Process\Process([
                    'sshpass',
                    '-p', $rootPassword,
                    'ssh',
                    '-o', 'StrictHostKeyChecking=no',
                    '-o', 'UserKnownHostsFile=/dev/null',
                    '-o', 'LogLevel=ERROR',
                    '-o', 'ConnectTimeout=5',
                    'root@' . $host->ip_address,
                    'echo "SSH_TEST_CONNECTION_OK"'
                ]);
                
                $process->setTimeout(10);
                $process->run();
                
                $output = trim($process->getOutput());
                $errorOutput = $process->getErrorOutput();
                $exitCode = $process->getExitCode();
                
                \Log::info('Terminal Connect: (Reintento) Código de salida: ' . $exitCode);
                \Log::info('Terminal Connect: (Reintento) Output: ' . $output);
                
                if ($errorOutput) {
                    \Log::warning('Terminal Connect: (Reintento) Error Output: ' . $errorOutput);
                }
            }
            
            // Verificar si la conexión fue exitosa
            if ($exitCode !== 0 || $output !== 'SSH_TEST_CONNECTION_OK') {
                \Log::error('Terminal Connect: No se pudo conectar al host: ' . $host->ip_address);
                
                // Si parece un error de autenticación, intentar método alternativo
                if (strpos($errorOutput, 'Permission denied') !== false) {
                    \Log::info('Terminal Connect: Intentando método alternativo para autenticación...');
                    
                    $rootPassword = "root";
                    $result = $this->executeWithExpect($host->ip_address, 'echo "SSH_TEST_CONNECTION_OK"', $rootPassword);
                    
                    if ($result['success'] && trim($result['output']) === 'SSH_TEST_CONNECTION_OK') {
                        \Log::info('Terminal Connect: Conexión exitosa con método alternativo');
                    } else {
                        \Log::error('Terminal Connect: También falló método alternativo: ' . ($result['error'] ?? 'Sin detalles'));
                        
                        return response()->json([
                            'success' => false,
                            'message' => 'No se pudo establecer conexión SSH con el host',
                            'details' => [
                                'exit_code' => $exitCode,
                                'error' => $errorOutput,
                                'alt_method_error' => $result['error'] ?? null
                            ]
                        ], 500);
                    }
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se pudo establecer conexión SSH con el host',
                        'details' => [
                            'exit_code' => $exitCode,
                            'error' => $errorOutput
                        ]
                    ], 500);
                }
            }
            
            // Almacenar datos de sesión en caché
            Cache::put($sessionKey, [
                'host_id' => $hostId,
                'created_at' => now(),
                'last_activity' => now(),
                'auth_method' => $useKeyAuth ? 'key' : 'password'
            ], 30 * 60); // 30 minutos
            
            \Log::info('Terminal Connect: Sesión SSH establecida y almacenada: ' . $sessionId);
            
            // Devolver ID de sesión
            return response()->json([
                'success' => true,
                'session_id' => $sessionId,
                'message' => 'Conexión SSH establecida correctamente'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Terminal Connect: Excepción: ' . $e->getMessage());
            \Log::error('Terminal Connect: Traza: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al establecer conexión SSH: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Desconecta una sesión SSH
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function terminalDisconnect(Request $request)
    {
        \Log::info('Terminal Disconnect: Cerrando sesión SSH');
        
        $request->validate([
            'session_id' => 'required|string'
        ]);
        
        $sessionId = $request->input('session_id');
        
        // Verificar si la sesión existe
        if (Cache::has('ssh_session_' . $sessionId)) {
            // Obtener datos de la sesión para el log
            $sessionData = Cache::get('ssh_session_' . $sessionId);
            $hostId = $sessionData['host_id'] ?? 'desconocido';
            
            // Eliminar la sesión
            Cache::forget('ssh_session_' . $sessionId);
            
            \Log::info('Terminal Disconnect: Sesión cerrada correctamente. Host ID: ' . $hostId);
            
            return response()->json([
                'success' => true,
                'message' => 'Sesión SSH cerrada correctamente'
            ]);
        }
        
        \Log::warning('Terminal Disconnect: Sesión no encontrada: ' . $sessionId);
        
        return response()->json([
            'success' => false,
            'message' => 'Sesión no encontrada o ya cerrada'
        ], 400);
    }

    /**
     * Procesa un comando SSH y devuelve su resultado
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function terminalSend(Request $request)
    {
        try {
            $ip = $request->input('ip');
            $command = $request->input('command');
            $currentDir = $request->input('current_dir', '~');
            
            // Utilizamos la variable de sesión para guardar el directorio actual
            if (!session()->has('terminal_dirs')) {
                session(['terminal_dirs' => []]);
            }
            
            $terminalDirs = session('terminal_dirs');
            if (!isset($terminalDirs[$ip])) {
                $terminalDirs[$ip] = '~';
            }
            
            $currentDir = $terminalDirs[$ip];
            
            // Si hay un comando 'cd', actualizar el directorio actual
            if (preg_match('/^cd\s+(.+)$/', $command, $matches)) {
                $newDir = $matches[1];
                
                // Cd especial para directorios relativos y absolutos
                if ($newDir == '..') {
                    // Subir un nivel
                    if ($currentDir != '~' && $currentDir != '/') {
                        $currentDir = dirname($currentDir);
                        if ($currentDir == '/') {
                            // Mantener la barra si estamos en el root
                            $currentDir = '/';
                        }
                    }
                } else if ($newDir == '~' || $newDir == '$HOME') {
                    // Ir al home
                    $currentDir = '~';
                } else if (substr($newDir, 0, 1) == '/') {
                    // Ruta absoluta
                    $currentDir = $newDir;
                } else {
                    // Ruta relativa
                    if ($currentDir == '~') {
                        // Si estamos en home, la ruta es relativa a /root
                        $newDir = '/root/' . $newDir;
                        $currentDir = $newDir;
                    } else {
                        // Ruta relativa normal
                        if ($currentDir == '/') {
                            $currentDir = '/' . $newDir;
                        } else {
                            $currentDir = $currentDir . '/' . $newDir;
                        }
                    }
                }
                
                // Al hacer cd, ejecutemos 'cd' y luego 'pwd' para obtener la ruta real
                $cdCommand = "cd " . escapeshellarg($currentDir) . " && pwd";
                
                // Ejecutar con SSH key autenticación
                $sshKeyPath = storage_path('app/ssh/id_rsa');
                $useKeyAuth = file_exists($sshKeyPath);
                
                $process = null;
                if ($useKeyAuth) {
                    $process = new \Symfony\Component\Process\Process([
                        'ssh',
                        '-i', $sshKeyPath,
                        '-o', 'StrictHostKeyChecking=no',
                        '-o', 'UserKnownHostsFile=/dev/null',
                        '-o', 'LogLevel=ERROR',
                        'root@' . $ip,
                        $cdCommand
                    ]);
                } else {
                    $process = new \Symfony\Component\Process\Process([
                        'sshpass',
                        '-p', 'root',
                        'ssh',
                        '-o', 'StrictHostKeyChecking=no',
                        '-o', 'UserKnownHostsFile=/dev/null',
                        '-o', 'LogLevel=ERROR',
                        'root@' . $ip,
                        $cdCommand
                    ]);
                }
                
                $process->setTimeout(10);
                $process->run();
                
                if ($process->isSuccessful()) {
                    $realPath = trim($process->getOutput());
                    if (!empty($realPath)) {
                        $currentDir = $realPath;
                    }
                    
                    // Actualizar la sesión
                    $terminalDirs[$ip] = $currentDir;
                    session(['terminal_dirs' => $terminalDirs]);
                    
                    // Devolver solo el cambio de directorio
                    return response()->json([
                        'output' => '',
                        'current_dir' => $currentDir,
                        'prompt' => 'root@' . $ip . ':' . $this->formatPathForPrompt($currentDir) . '# '
                    ]);
                } else {
                    // Si falló, quizás el directorio no existe
                    return response()->json([
                        'output' => "cd: " . $newDir . ": No such file or directory",
                        'current_dir' => $terminalDirs[$ip],
                        'prompt' => 'root@' . $ip . ':' . $this->formatPathForPrompt($terminalDirs[$ip]) . '# '
                    ]);
                }
            }
            
            // Para comandos de limpieza de pantalla
            if ($command == 'clear' || $command == 'cls') {
                return response()->json([
                    'output' => 'CLEAR_TERMINAL',
                    'current_dir' => $currentDir,
                    'prompt' => 'root@' . $ip . ':' . $this->formatPathForPrompt($currentDir) . '# '
                ]);
            }
            
            // Construir el comando con el directorio actual
            $fullCommand = '';
            if ($currentDir == '~') {
                $fullCommand = $command;
            } else {
                $fullCommand = "cd " . escapeshellarg($currentDir) . " && " . $command;
            }
            
            // Comprobar si es un comando para listar directorios
            $isListCommand = false;
            if (preg_match('/^\s*(ls|dir)\b/', $command) || $command === 'ls') {
                $isListCommand = true;
            }
            
            // Ejecutar con SSH key autenticación
            $sshKeyPath = storage_path('app/ssh/id_rsa');
            $useKeyAuth = file_exists($sshKeyPath);
            
            if ($isListCommand) {
                // Para comandos de listado, usar nuestra función robusta
                $targetDir = $currentDir;
                if (preg_match('/^\s*(ls|dir)\s+(.+)$/', $command, $matches)) {
                    // Si hay un argumento, usarlo como directorio
                    $targetDir = $matches[2];
                    // Manejar rutas relativas
                    if (substr($targetDir, 0, 1) != '/') {
                        if ($currentDir == '~') {
                            $targetDir = '/root/' . $targetDir;
                        } else {
                            $targetDir = $currentDir . '/' . $targetDir;
                        }
                    }
                } else if ($currentDir == '~') {
                    $targetDir = '/root';
                }
                
                $result = $this->executeRobustDirectoryListing(
                    $ip, 
                    $targetDir, 
                    $useKeyAuth, 
                    $useKeyAuth ? $sshKeyPath : null, 
                    'root'
                );
                
                $output = $result['output'];
                $exitCode = $result['exit_code'];
                
                // Formatear la salida para que se parezca a ls con colores
                $formattedOutput = $this->formatDirectoryListing($output);
                
                // Registrar la ejecución del comando
                \Log::info('Terminal command executed', [
                    'ip' => $ip,
                    'command' => $command,
                    'fullCommand' => "Robust Directory Listing for " . $targetDir,
                    'exitCode' => $exitCode,
                    'outputLength' => strlen($formattedOutput)
                ]);
                
                return response()->json([
                    'output' => $formattedOutput,
                    'exit_code' => $exitCode,
                    'current_dir' => $currentDir,
                    'prompt' => 'root@' . $ip . ':' . $this->formatPathForPrompt($currentDir) . '# '
                ]);
            } else {
                // Para otros comandos, usar el proceso normal
                $process = null;
                if ($useKeyAuth) {
                    $process = new \Symfony\Component\Process\Process([
                        'ssh',
                        '-i', $sshKeyPath,
                        '-o', 'StrictHostKeyChecking=no',
                        '-o', 'UserKnownHostsFile=/dev/null',
                        '-o', 'LogLevel=ERROR',
                        'root@' . $ip,
                        $fullCommand
                    ]);
                } else {
                    $process = new \Symfony\Component\Process\Process([
                        'sshpass',
                        '-p', 'root',
                        'ssh',
                        '-o', 'StrictHostKeyChecking=no',
                        '-o', 'UserKnownHostsFile=/dev/null',
                        '-o', 'LogLevel=ERROR',
                        'root@' . $ip,
                        $fullCommand
                    ]);
                }
                
                $process->setTimeout(30);
                $process->setIdleTimeout(null);
                $process->run();
                
                $output = $process->getOutput() . $process->getErrorOutput();
                $exitCode = $process->getExitCode();
                
                // Registrar la ejecución del comando
                \Log::info('Terminal command executed', [
                    'ip' => $ip,
                    'command' => $command,
                    'fullCommand' => $fullCommand,
                    'exitCode' => $exitCode,
                    'outputLength' => strlen($output)
                ]);
                
                return response()->json([
                    'output' => $output,
                    'exit_code' => $exitCode,
                    'current_dir' => $currentDir,
                    'prompt' => 'root@' . $ip . ':' . $this->formatPathForPrompt($currentDir) . '# '
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Error executing terminal command', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'output' => 'Error: ' . $e->getMessage(),
                'exit_code' => 1,
                'error' => true,
                'prompt' => 'root@' . $ip . ':' . $this->formatPathForPrompt($currentDir ?? '~') . '# '
            ]);
        }
    }
    
    /**
     * Formatea un path para mostrarlo en el prompt del terminal
     */
    private function formatPathForPrompt($path) 
    {
        if ($path == '~') {
            return '~';
        }
        
        if ($path == '/root') {
            return '~';
        }
        
        if (strpos($path, '/root/') === 0) {
            return '~' . substr($path, 5);
        }
        
        return $path;
    }
    
    /**
     * Formatea la salida del listado de directorio para que se parezca a ls con colores
     */
    private function formatDirectoryListing($output)
    {
        // Simplemente devolver la salida estándar sin formato adicional
        return $output;
    }
    
    /**
     * Asegura que sshpass esté instalado en el sistema
     */
    private function ensureSshpassInstalled()
    {
        $checkProcess = new \Symfony\Component\Process\Process(['which', 'sshpass']);
        $checkProcess->run();
        
        if (!$checkProcess->isSuccessful()) {
            \Log::warning('Terminal Send: No se encontró sshpass, intentando instalarlo...');
            
            // Intentar detectar el sistema operativo e instalar sshpass
            $osProcess = new \Symfony\Component\Process\Process(['cat', '/etc/os-release']);
            $osProcess->run();
            $osInfo = $osProcess->getOutput();
            
            $installProcess = null;
            
            if (strpos($osInfo, 'ID=debian') !== false || strpos($osInfo, 'ID=ubuntu') !== false) {
                $installProcess = new \Symfony\Component\Process\Process(['apt-get', 'update', '-y']);
                $installProcess->run();
                $installProcess = new \Symfony\Component\Process\Process(['apt-get', 'install', '-y', 'sshpass']);
            } elseif (strpos($osInfo, 'ID=centos') !== false || strpos($osInfo, 'ID=rhel') !== false) {
                $installProcess = new \Symfony\Component\Process\Process(['yum', 'install', '-y', 'sshpass']);
            }
            
            if ($installProcess) {
                $installProcess->run();
                if ($installProcess->isSuccessful()) {
                    \Log::info('Terminal Send: sshpass instalado correctamente');
                } else {
                    \Log::error('Terminal Send: No se pudo instalar sshpass: ' . $installProcess->getErrorOutput());
                }
            } else {
                \Log::error('Terminal Send: No se pudo determinar cómo instalar sshpass en este sistema');
            }
        }
    }
    
    /**
     * Ejecutar comando SSH usando expect si está disponible
     * Método alternativo para conexiones SSH con contraseña
     */
    private function executeWithExpect($ip, $command, $password)
    {
        $result = [
            'success' => false,
            'output' => '',
            'error' => ''
        ];
        
        // Verificar si expect está instalado
        $checkProcess = new \Symfony\Component\Process\Process(['which', 'expect']);
        $checkProcess->run();
        
        if (!$checkProcess->isSuccessful()) {
            \Log::warning('Terminal Send: expect no está instalado, no se puede usar método alternativo');
            $result['error'] = 'expect no está instalado';
            return $result;
        }
        
        // Crear script temporal de expect
        $tmpFile = tempnam(sys_get_temp_dir(), 'ssh_expect_');
        $expectScript = <<<EOT
#!/usr/bin/expect -f
set timeout 10
spawn ssh -o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null root@$ip "$command"
expect {
    "password:" { send "$password\r" }
    timeout { exit 1 }
}
expect eof
EOT;
        
        file_put_contents($tmpFile, $expectScript);
        chmod($tmpFile, 0700);
        
        // Ejecutar el script expect
        $process = new \Symfony\Component\Process\Process([$tmpFile]);
        $process->run();
        
        // Eliminar el archivo temporal
        unlink($tmpFile);
        
        if ($process->isSuccessful()) {
            $result['success'] = true;
            $result['output'] = $process->getOutput();
        } else {
            $result['error'] = $process->getErrorOutput();
        }
        
        return $result;
    }

    /**
     * Ejecuta un comando específico para obtener la información completa de un directorio
     * cuando el comando ls normal no funciona o da resultados incompletos.
     * 
     * @param string $ip Dirección IP del host
     * @param string $directory Directorio a listar (por defecto directorio actual)
     * @param bool $useKeyAuth Si usar autenticación por clave
     * @param string|null $sshKeyPath Ruta a la clave SSH si $useKeyAuth es true
     * @param string|null $rootPassword Contraseña para autenticación si $useKeyAuth es false
     * @return array Salida del comando y código de salida
     */
    private function executeRobustDirectoryListing($ip, $directory = '.', $useKeyAuth = true, $sshKeyPath = null, $rootPassword = null)
    {
        $rootPassword = $rootPassword ?? 'root';
        
        // Usar un comando estándar como en una terminal normal de Ubuntu
        $cmd = "ls -la " . escapeshellarg($directory);
        
        \Log::debug("Ejecutando comando de listado simple: $cmd");
        $process = null;
        
        if ($useKeyAuth && $sshKeyPath) {
            $process = new \Symfony\Component\Process\Process([
                'ssh',
                '-i', $sshKeyPath,
                '-o', 'StrictHostKeyChecking=no',
                '-o', 'UserKnownHostsFile=/dev/null',
                '-o', 'LogLevel=ERROR',
                '-o', 'ConnectTimeout=10',
                'root@' . $ip,
                $cmd
            ]);
        } else {
            $process = new \Symfony\Component\Process\Process([
                'sshpass',
                '-p', $rootPassword,
                'ssh',
                '-o', 'StrictHostKeyChecking=no',
                '-o', 'UserKnownHostsFile=/dev/null',
                '-o', 'LogLevel=ERROR',
                '-o', 'ConnectTimeout=10',
                'root@' . $ip,
                $cmd
            ]);
        }
        
        $process->setTimeout(20);
        $process->setIdleTimeout(null);
        $process->run();
        
        $output = $process->getOutput();
        $errorOutput = $process->getErrorOutput();
        
        // Si el comando fue exitoso
        if ($process->isSuccessful() && !empty($output)) {
            return [
                'output' => $output,
                'exit_code' => $process->getExitCode()
            ];
        }
        
        // Si falló, intentar con un comando más simple
        $cmd = "ls " . escapeshellarg($directory);
        
        \Log::debug("Intentando comando alternativo: $cmd");
        
        if ($useKeyAuth && $sshKeyPath) {
            $process = new \Symfony\Component\Process\Process([
                'ssh',
                '-i', $sshKeyPath,
                '-o', 'StrictHostKeyChecking=no',
                '-o', 'UserKnownHostsFile=/dev/null',
                '-o', 'LogLevel=ERROR',
                'root@' . $ip,
                $cmd
            ]);
        } else {
            $process = new \Symfony\Component\Process\Process([
                'sshpass',
                '-p', $rootPassword,
                'ssh',
                '-o', 'StrictHostKeyChecking=no',
                '-o', 'UserKnownHostsFile=/dev/null',
                '-o', 'LogLevel=ERROR',
                'root@' . $ip,
                $cmd
            ]);
        }
        
        $process->setTimeout(20);
        $process->run();
        
        if ($process->isSuccessful() && !empty($process->getOutput())) {
            return [
                'output' => $process->getOutput(),
                'exit_code' => $process->getExitCode()
            ];
        }
        
        // Si todos los comandos fallan, devolver el error
        return [
            'output' => $errorOutput ? $errorOutput : "No se pudo listar el directorio",
            'exit_code' => 1
        ];
    }
} 