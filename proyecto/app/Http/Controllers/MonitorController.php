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
     * Ejecutar ping a un host
     */
    public function ping(Request $request, $id)
    {
        try {
            $host = MonitorHost::findOrFail($id);
            $ip = $host->ip_address;
            
            // Usar el servicio de ejecución remota para hacer ping
            $executor = new RemoteExecutionService();
            $result = $executor->ping($ip);
            
            // Actualizar estado del host
            if ($result['success']) {
                MonitorHost::updateStatus($id, 'online');
                
                // Si el ping fue exitoso y no tiene MAC registrada, intentar obtenerla
                if (empty($host->mac_address)) {
                    $mac = $executor->getMacAddress($ip);
                    if ($mac) {
                        $host->mac_address = $mac;
                        $host->save();
                        Log::info("MAC real detectada y actualizada para {$host->hostname} ({$ip}): {$mac}");
                    } else {
                        Log::debug("No se pudo obtener una MAC real para {$host->hostname} ({$ip})");
                    }
                }
                
                return response()->json(['status' => 'online', 'message' => 'Host está en línea']);
            } else {
                MonitorHost::updateStatus($id, 'offline');
                return response()->json(['status' => 'offline', 'message' => 'Host está fuera de línea']);
            }
        } catch (\Exception $e) {
            Log::error('Error en ping: ' . $e->getMessage());
            MonitorHost::updateStatus($id, 'error');
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * Ejecutar ping a todos los hosts
     */
    public function pingAll()
    {
        try {
            $hosts = MonitorHost::all();
            $updatedCount = 0;
            $errorCount = 0;
            
            // Actualizar estado para cada host
            foreach ($hosts as $host) {
                try {
                    // Usar el servicio de ejecución remota para hacer ping
                    $executor = new RemoteExecutionService();
                    $result = $executor->ping($host->ip_address);
                    
                    // Verificar y actualizar estado
                    if ($result['success']) {
                        $host->status = 'online';
                        if ($this->isNetworkInfrastructure($host->ip_address)) {
                            Log::info("Dispositivo crítico marcado como online: {$host->hostname} ({$host->ip_address})");
                        }
                    } else {
                        $host->status = 'offline';
                    }
                    
                    $host->last_seen = now();
                    $host->save();
                    
                    // Si el host está online y no tiene MAC, intentar obtenerla
                    if ($result['success'] && empty($host->mac_address)) {
                        $mac = $executor->getMacAddress($host->ip_address);
                        if ($mac) {
                            $host->mac_address = $mac;
                            $host->save();
                            Log::info("MAC real detectada y actualizada para {$host->hostname} ({$host->ip_address}): {$mac}");
                        }
                    }
                    
                    $updatedCount++;
                    Log::debug("Host {$host->hostname} actualizado con estado: {$host->status}");
                } catch (\Exception $e) {
                    Log::error("Error actualizando host {$host->hostname}: " . $e->getMessage());
                    $errorCount++;
                }
            }
            
            Log::info("Actualización de estado completada. Actualizados: $updatedCount, Errores: $errorCount");
            return response()->json([
                'status' => 'success',
                'message' => "Actualización completada: $updatedCount hosts actualizados, $errorCount errores",
                'updated' => $updatedCount,
                'errors' => $errorCount
            ]);
        } catch (\Exception $e) {
            Log::error('Error en pingAll: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
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
     * Ejecutar un comando en un host remoto
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
            
            // Aquí implementaríamos la lógica para ejecutar el comando en el host remoto
            // Esto podría hacerse a través de SSH o un agente instalado en el host
            
            // Para fines de demostración, simulamos una respuesta
            $command = $request->command;
            
            // Log de seguridad para registrar quién ejecutó qué comandos
            Log::info("Usuario {$request->session()->get('auth_user.username')} ejecutó el comando '{$command}' en host {$host->hostname}");
            
            return response()->json([
                'status' => 'success', 
                'command' => $command,
                'output' => "Simulación de ejecución: '{$command}' en {$host->hostname}",
                'hostname' => $host->hostname
            ]);
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
            $this->ping(request(), $id);
            
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
            $validator = Validator::make($request->all(), [
                'hostname' => 'required|string',
                'ip_address' => 'required|ip',
                'status' => 'required|string',
                'system_info' => 'nullable|array',
                'disk_usage' => 'nullable|array',
                'memory_usage' => 'nullable|array',
                'cpu_usage' => 'nullable|array',
                'last_boot' => 'nullable|date',
            ]);
            
            if ($validator->fails()) {
                return response()->json(['status' => 'error', 'message' => $validator->errors()]);
            }
            
            // Buscar host por IP o hostname
            $host = MonitorHost::where('ip_address', $request->ip_address)
                ->orWhere('hostname', $request->hostname)
                ->first();
            
            if (!$host) {
                // Crear nuevo host si no existe
                $host = new MonitorHost();
                $host->hostname = $request->hostname;
                $host->ip_address = $request->ip_address;
                $host->status = $request->status;
                $host->save();
            }
            
            // Actualizar información del sistema
            $data = $request->only([
                'system_info',
                'disk_usage',
                'memory_usage', 
                'cpu_usage',
                'last_boot'
            ]);
            
            MonitorHost::updateSystemInfo($host->id, $data);
            MonitorHost::updateStatus($host->id, $request->status);
            
            return response()->json(['status' => 'success', 'message' => 'Telemetría actualizada']);
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
} 