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
use Illuminate\Support\Facades\Http;
use App\Models\Group;

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
        // Asignar grupo automáticamente a hosts sin grupo
        foreach ($hosts as $host) {
            if (empty($host->group_id) && preg_match('/^([A-Z][0-9]{2})-/', $host->hostname, $matches)) {
                $nombreGrupo = $matches[1];
                $grupoDetectado = \App\Models\MonitorGroup::firstOrCreate(
                    ['name' => $nombreGrupo],
                    [
                        'description' => 'Aula ' . $nombreGrupo,
                        'type' => 'classroom',
                        'created_by' => \Auth::id() ?: 1
                    ]
                );
                $host->group_id = $grupoDetectado->id;
                $host->save();
            }
        }
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
        try {
            \Log::info('Iniciando creación de host', ['request' => $request->all()]);

            // Validar datos básicos (hostname único)
            $validated = $request->validate([
                'hostname' => 'required_if:tipo_host,dhcp|nullable|string|max:255|unique:monitor_hosts,hostname',
                'tipo_host' => 'required|in:fija,dhcp',
                'ip_address' => 'required_if:tipo_host,fija|nullable|ip',
                'description' => 'nullable|string',
                'group_id' => 'nullable|exists:monitor_groups,id'
            ], [
                'hostname.unique' => 'Ya existe un equipo con ese hostname. Edítalo o elimínalo antes de crear uno nuevo.',
                'hostname.required_if' => 'El hostname es requerido para equipos DHCP.',
                'ip_address.required_if' => 'La dirección IP es requerida para equipos con IP fija.'
            ]);

            \Log::info('Validación pasada', ['validated' => $validated]);

            // Crear el host directamente con los datos proporcionados
            $host = new MonitorHost();
            // Si es IP fija y no hay hostname, usar la IP como hostname
            if ($request->tipo_host === 'fija' && empty($validated['hostname']) && empty($request->hostname_fija)) {
                $host->hostname = 'IP-' . $request->ip_address;
            } else {
                $host->hostname = $validated['hostname'] ?? $request->hostname_fija;
            }
            // Usar la IP detectada si existe, sino la IP fija introducida
            $host->ip_address = $request->ip_address_display ?: $request->ip_address;
            $host->mac_address = $request->mac_address;
            $host->description = $validated['description'] ?? null;
            $host->created_by = auth()->id();
            $host->status = 'offline'; // Estado inicial

            // Asignar grupo automáticamente si no se especificó
            if (empty($validated['group_id'])) {
                // Intentar detectar el grupo por el prefijo del hostname (ej: A27, B27, etc)
                if (preg_match('/^([A-Z][0-9]{2})-/', $host->hostname, $matches)) {
                    $nombreGrupo = $matches[1];
                    $grupoDetectado = MonitorGroup::firstOrCreate(
                        ['name' => $nombreGrupo],
                        [
                            'description' => 'Aula ' . $nombreGrupo,
                            'type' => 'classroom',
                            'created_by' => auth()->id()
                        ]
                    );
                    $host->group_id = $grupoDetectado->id;
                    \Log::info("Host {$host->hostname} asignado al grupo {$nombreGrupo} (ID: {$grupoDetectado->id})");
                }
            } else {
                $host->group_id = $validated['group_id'];
            }
                
            $host->save();
            \Log::info('Host creado exitosamente', ['host' => $host->toArray()]);

            // Redirigir a la página de índice con mensaje de éxito
            return redirect()->route('monitor.index')
                ->with('success', 'Host ' . $host->hostname . ' agregado correctamente');

        } catch (\Exception $e) {
            \Log::error('Error al crear el host', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al crear el host: ' . $e->getMessage());
        }
    }
    
    /**
     * Verifica el estado de un host específico usando el microservicio Python
     * Llama a /scan?ip=... en el microservicio, nunca a /ping/{id}
     */
    public function ping($id)
    {
        try {
            $host = MonitorHost::findOrFail($id);
            $baseUrl = env('MACSCANNER_URL', 'http://172.20.0.6:5000');
            $mac = null;
            $ipDetectada = null;
            $status = 'offline';
            $scanType = request()->input('scan_type', 'hostname');

            if ($scanType === 'hostname') {
                // Escaneo por hostname
                $hostname = $host->hostname;
                if (!empty($hostname)) {
                    $pythonServiceUrl = rtrim($baseUrl, '/') . '/scan-hostnames';
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $pythonServiceUrl);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['hostname' => $hostname]));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                    
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    if ($response !== false && $httpCode === 200) {
                        $data = json_decode($response, true);
                        if (isset($data['success']) && $data['success'] && isset($data['hosts'][0])) {
                            $hostData = $data['hosts'][0];
                            $mac = $hostData['mac'] ?? null;
                            $ipDetectada = $hostData['ip'] ?? null;
                            $status = 'online';
                        }
                    }
                }
            } else {
                // Escaneo por IP
                $ip = $host->ip_address;
                if (!empty($ip)) {
                    $pythonServiceUrl = rtrim($baseUrl, '/') . '/scan?ip=' . urlencode($ip);
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $pythonServiceUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
                    
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    if ($response !== false && $httpCode === 200) {
                        $data = json_decode($response, true);
                        if (isset($data['success']) && $data['success']) {
                            $mac = $data['mac'] ?? null;
                            $ipDetectada = $ip;
                            $status = 'online';
                        }
                    }
                }
            }

            // Solo actualizar si realmente se detectó el host
            if ($status === 'online') {
                $host->status = $status;
                $host->last_seen = now();
                if (!empty($mac)) $host->mac_address = $mac;
                if (!empty($ipDetectada)) $host->ip_address = $ipDetectada;
                $host->save();
            } else {
                // Si no se detectó, marcar como offline
                $host->status = 'offline';
                $host->save();
            }
                
            return response()->json([
                'success' => true,
                'status' => $status,
                'message' => $status === 'online' ? 'Host está en línea' : 'Host está fuera de línea',
                'last_seen' => $host->last_seen ? $host->last_seen->format('d/m/Y H:i:s') : null,
                'ip' => $host->ip_address,
                'mac' => $host->mac_address
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al escanear el host: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Verifica el estado de todos los hosts usando el microservicio Python y redirige con mensaje
     */
    public function pingAll(Request $request)
    {
        try {
            $groupId = $request->query('group');
            $scanType = $request->input('scan_type', 'hostname');
            
            // Solo obtener hosts del grupo seleccionado
            $hosts = MonitorHost::where('group_id', $groupId)->get();
            
            if ($hosts->isEmpty()) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se encontraron hosts en este grupo.'
                    ], 404);
                }
                return redirect()->route('monitor.index')
                    ->with('error', 'No se encontraron hosts en este grupo.');
            }

            $updated = 0;
            $errors = 0;
            $baseUrl = env('MACSCANNER_URL', 'http://172.20.0.6:5000');

            foreach ($hosts as $host) {
                $mac = null;
                $ipDetectada = null;
                $status = 'offline';

                if ($scanType === 'hostname') {
                    // Escaneo por hostname para equipos de aula
                    $hostname = $host->hostname;
                    if (!empty($hostname)) {
                        $pythonServiceUrl = rtrim($baseUrl, '/') . '/scan-hostnames';
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $pythonServiceUrl);
                        curl_setopt($ch, CURLOPT_POST, 1);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['hostname' => $hostname]));
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 3); // Reducido a 3 segundos
                        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                        
                        $response = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);

                        if ($response !== false && $httpCode === 200) {
                            $data = json_decode($response, true);
                            if (isset($data['success']) && $data['success']) {
                                $mac = $data['mac'] ?? null;
                                $ipDetectada = $data['ip'] ?? null;
                                $status = 'online';
                            }
                        }
                    }
                } else {
                    // Escaneo por IP para equipos de infraestructura
                    $ip = $host->ip_address;
                    if (!empty($ip)) {
                        $pythonServiceUrl = rtrim($baseUrl, '/') . '/scan?ip=' . urlencode($ip);
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $pythonServiceUrl);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 3); // Reducido a 3 segundos
                        
                        $response = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);

                        if ($response !== false && $httpCode === 200) {
                            $data = json_decode($response, true);
                            if (isset($data['success']) && $data['success']) {
                                $mac = $data['mac'] ?? null;
                                $ipDetectada = $ip;
                                $status = 'online';
                            }
                        }
                    }
                }

                try {
                    $host->status = $status;
                    $host->last_seen = $status === 'online' ? now() : $host->last_seen;
                    if (!empty($mac)) $host->mac_address = $mac;
                    if (!empty($ipDetectada)) $host->ip_address = $ipDetectada;
                    $host->save();
                    $updated++;
                } catch (\Exception $e) {
                    \Log::error('Error actualizando host en pingAll: ' . $e->getMessage());
                    $errors++;
                }
            }

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => "Estado actualizado. {$updated} hosts actualizados, {$errors} errores.",
                    'updated' => $updated,
                    'errors' => $errors
                ]);
            }

            return redirect()->route('monitor.index')
                ->with('success', "Estado actualizado. {$updated} hosts actualizados, {$errors} errores.");
        } catch (\Exception $e) {
            \Log::error('Error en pingAll: ' . $e->getMessage());
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al actualizar el estado: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->route('monitor.index')
                ->with('error', 'Error al actualizar el estado: ' . $e->getMessage());
        }
    }
    
    /**
     * Escanear la red local usando el microservicio Python
     */
    public function scanNetwork(Request $request)
    {
        try {
            // Nuevo: Si el usuario selecciona escaneo por hostname
            if ($request->has('scan_by_hostname')) {
                $aula = $request->input('aula');
                $columnas = $request->input('columnas', ['A','B','C','D','E','F']);
                $filas = $request->input('filas', range(1, 6));
                $dominio = 'tierno.es';
                $groupId = $request->input('group_id');
                $forceRegister = $request->has('force_register');

                $baseUrl = env('MACSCANNER_URL', 'http://172.20.0.6:5000');
                $macscannerUrl = rtrim($baseUrl, '/') . '/scan-hostnames';
                $payload = [
                    'aula' => $aula,
                    'columnas' => $columnas,
                    'filas' => $filas,
                    'dominio' => $dominio
                ];
                $options = [
                    'http' => [
                        'header'  => "Content-type: application/json\r\n",
                        'method'  => 'POST',
                        'content' => json_encode($payload),
                        'timeout' => 60
                    ]
                ];
                $context  = stream_context_create($options);
                $result = file_get_contents($macscannerUrl, false, $context);
                $data = json_decode($result, true);
                if (!$data || !isset($data['success']) || !$data['success']) {
                    return back()->with('error', 'Error al escanear hostnames');
                }
                $created = 0;
                $updated = 0;
                $errors = 0;
                $duplicados = [];
                foreach ($data['hosts'] as $hostData) {
                    $hostnameLimpio = preg_replace('/\.tierno\.es$/i', '', $hostData['hostname']);
                    // Comprobar si ya existe un host con ese hostname
                    $hostExistente = \App\Models\MonitorHost::where('hostname', $hostnameLimpio)->first();
                    if ($hostExistente) {
                        $duplicados[] = $hostnameLimpio;
                        continue; // Saltar duplicados
                    }
                    try {
                        $host = new \App\Models\MonitorHost();
                        $host->hostname = $hostnameLimpio;
                        $host->created_by = \Auth::id() ?: 1;
                        // Guardar IP del host
                        $host->ip_address = $hostData['ip'] ?? null;
                        // Intentar obtener la MAC usando el hostname primero (más fiable con DHCP)
                        $macObtenida = false;
                        if (!empty($hostData['hostname'])) {
                            $pythonServiceUrl = rtrim($baseUrl, '/') . '/scan?hostname=' . urlencode($hostData['hostname']);
                            $scanResponse = @file_get_contents($pythonServiceUrl);
                            if ($scanResponse !== false) {
                                $scanData = json_decode($scanResponse, true);
                                if (!empty($scanData['mac'])) {
                                    $host->mac_address = $scanData['mac'];
                                    $macObtenida = true;
                                    \Log::info("MAC detectada para hostname {$hostData['hostname']}: {$scanData['mac']}");
                                }
                            }
                        }
                        if (!$macObtenida && !empty($host->ip_address)) {
                            $pythonServiceUrl = rtrim($baseUrl, '/') . '/scan?ip=' . urlencode($host->ip_address);
                            $scanResponse = @file_get_contents($pythonServiceUrl);
                            if ($scanResponse !== false) {
                                $scanData = json_decode($scanResponse, true);
                                if (!empty($scanData['mac'])) {
                                    $host->mac_address = $scanData['mac'];
                                    $macObtenida = true;
                                    \Log::info("MAC detectada para IP {$host->ip_address}: {$scanData['mac']}");
                                }
                            }
                        }
                        if (!$macObtenida) {
                            $host->mac_address = $hostData['mac'] ?? null;
                            if (!empty($host->mac_address)) {
                                \Log::info("Usando MAC del escaneo original para {$hostData['hostname']}: {$host->mac_address}");
                            }
                        }
                        $host->status = 'online';
                        $host->last_seen = now();
                        if ($groupId) $host->group_id = $groupId;
                        $host->save();
                        $created++;
                    } catch (\Exception $e) {
                        \Log::error('Error guardando host en scanNetwork (hostname): ' . $e->getMessage());
                        $errors++;
                    }
                }
                $msg = "Escaneo por hostname completado. {$created} equipos nuevos, {$updated} actualizados, {$errors} errores.";
                if (count($duplicados) > 0) {
                    $msg .= " Los siguientes hostnames ya existían y no se crearon: " . implode(', ', $duplicados);
                    return redirect()->route('monitor.index')->with('warning', $msg);
                }
                return redirect()->route('monitor.index')->with('success', $msg);
            }

            // Lógica original: escaneo por IP
            // Leer parámetros del formulario
            $baseIp = $request->input('base_ip', '172.20.200');
            $rangeStart = (int) $request->input('range_start', 1);
            $rangeEnd = (int) $request->input('range_end', 254);
            $groupId = $request->input('group_id');
            $forceRegister = $request->has('force_register');
            $scanAdditional = $request->has('scan_additional_ranges');

            $ipsToScan = [];
            // Construir rango principal
            for ($i = $rangeStart; $i <= $rangeEnd; $i++) {
                $ipsToScan[] = $baseIp . '.' . $i;
            }

            // Incluir dispositivos críticos y rangos adicionales si corresponde
            if ($scanAdditional) {
                $critical = ['172.20.0.1', '172.20.0.2', '172.20.0.30'];
                $ipsToScan = array_merge($ipsToScan, $critical);
                // Ejemplo: incluir muestras de otros rangos DHCP
                foreach ([201,202,203,204,205,206,207,208,209] as $octet) {
                    foreach ([1, 100, 200] as $host) {
                        $ipsToScan[] = "172.20.$octet.$host";
                    }
                }
            }
            // Eliminar duplicados
            $ipsToScan = array_unique($ipsToScan);

            $created = 0;
            $updated = 0;
            $errors = 0;
            $baseUrl = env('MACSCANNER_URL', 'http://172.20.0.6:5000');
            foreach ($ipsToScan as $ip) {
                $pythonServiceUrl = rtrim($baseUrl, '/') . '/scan?ip=' . urlencode($ip);
                $response = @file_get_contents($pythonServiceUrl);
                if ($response === false) {
                    \Log::error('No se pudo conectar al microservicio Python para escaneo (host ' . $ip . '): ' . $pythonServiceUrl);
                    $errors++;
                    continue;
                }
                $data = json_decode($response, true);
                if (!$data || !isset($data['success'])) {
                    \Log::error('Respuesta inválida del microservicio Python (scanNetwork, host ' . $ip . '): ' . $response);
                    $errors++;
                    continue;
                }
                // Solo registrar si responde o si está activado forzar registro
                if ($data['success'] || $forceRegister) {
                    try {
                        $host = MonitorHost::where('ip_address', $ip)->first();
                        $isNew = false;
                        if (!$host) {
                            $host = new MonitorHost();
                            $host->ip_address = $ip;
                            $host->created_by = \Auth::id() ?: 1;
                            $isNew = true;
                        }
                        if (!empty($data['hostname'])) $host->hostname = preg_replace('/\.tierno\.es$/i', '', $data['hostname']);
                        if (!empty($data['mac'])) $host->mac_address = $data['mac'];
                        $host->status = $data['success'] ? 'online' : 'offline';
                        $host->last_seen = $data['success'] ? now() : $host->last_seen;
                        // Asignar grupo si se seleccionó
                        if ($groupId) $host->group_id = $groupId;
                        $host->save();
                        if ($isNew) {
                            $created++;
                        } else {
                            $updated++;
                        }
                    } catch (\Exception $e) {
                        \Log::error('Error guardando host en scanNetwork: ' . $e->getMessage());
                        $errors++;
                    }
                }
            }
            $msg = "Escaneo completado. {$created} equipos nuevos, {$updated} actualizados, {$errors} errores.";
            return redirect()->route('monitor.index')->with('success', $msg);
        } catch (\Exception $e) {
            \Log::error('Error en scanNetwork (Python): ' . $e->getMessage());
            return redirect()->route('monitor.index')->with('error', 'Error al escanear la red: ' . $e->getMessage());
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
                $host->hostname = preg_replace('/\.tierno\.es$/i', '', $request->hostname);
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
                'users' => $request->users ?? null,
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
            'users' => 'nullable|json',
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
     * Enviar Wake-on-LAN a un host usando el microservicio Python
     */
    public function wakeOnLan($id)
    {
        try {
            $host = MonitorHost::findOrFail($id);
            $user = Auth::user();
            $isAdmin = $user && ($user->is_admin || $user->role === 'admin');
            if (!$isAdmin && $host->created_by != $user->id) {
                abort(403, 'No tienes permiso para despertar este host');
            }
            if (empty($host->mac_address)) {
                return redirect()->back()
                    ->with('error', 'No se puede enviar Wake-on-LAN: El host no tiene una dirección MAC real configurada. Intente detectar la MAC primero.');
            }
            $baseUrl = env('MACSCANNER_URL', 'http://172.20.0.6:5000');
            $wolUrl = rtrim($baseUrl, '/') . '/wol';
            $payload = json_encode(['mac' => $host->mac_address]);
            $opts = [
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\n",
                    'content' => $payload,
                    'timeout' => 5
                ]
            ];
            $context = stream_context_create($opts);
            $response = @file_get_contents($wolUrl, false, $context);
            if ($response === false) {
                Log::error('No se pudo conectar al microservicio Python para WoL: ' . $wolUrl);
                return redirect()->back()->with('error', 'No se pudo conectar al microservicio de red para WoL.');
            }
            $data = json_decode($response, true);
            if (!isset($data['success']) || !$data['success']) {
                Log::error('Error en respuesta WoL Python: ' . $response);
                return redirect()->back()->with('error', 'Error al enviar WoL: ' . ($data['error'] ?? 'Error desconocido'));
            }
            Log::info("Usuario {".Auth::user()->username."} envió Wake-on-LAN a {$host->hostname} ({$host->mac_address})");
            return redirect()->back()->with('success', "Paquete Wake-on-LAN enviado a {$host->hostname}");
        } catch (\Exception $e) {
            Log::error('Error en wakeOnLan: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al despertar el host: ' . $e->getMessage());
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
     * Enviar Wake-on-LAN a todos los hosts de un grupo usando el microservicio Python
     */
    public function wakeOnLanGroup($id)
    {
        try {
            $group = MonitorGroup::findOrFail($id);
            if (!Auth::user()->is_admin && $group->created_by != Auth::id()) {
                abort(403, 'No tienes permiso para despertar los hosts de este grupo');
            }
            $hosts = $group->hosts()->whereNotNull('mac_address')->get();
            $sentCount = 0;
            $errorCount = 0;
            $baseUrl = env('MACSCANNER_URL', 'http://172.20.0.6:5000');
            $wolUrl = rtrim($baseUrl, '/') . '/wol';
            foreach ($hosts as $host) {
                $payload = json_encode(['mac' => $host->mac_address]);
                $opts = [
                    'http' => [
                        'method' => 'POST',
                        'header' => "Content-Type: application/json\r\n",
                        'content' => $payload,
                        'timeout' => 5
                    ]
                ];
                $context = stream_context_create($opts);
                $response = @file_get_contents($wolUrl, false, $context);
                $ok = false;
                if ($response !== false) {
                    $data = json_decode($response, true);
                    $ok = isset($data['success']) && $data['success'];
                }
                if ($ok) {
                    Log::info("Usuario {".Auth::user()->username."} envió Wake-on-LAN a {$host->hostname} ({$host->mac_address}) del grupo {$group->name}");
                    $sentCount++;
                } else {
                    $errorCount++;
                }
            }
            if ($sentCount > 0) {
                return redirect()->back()->with('success', "Paquetes Wake-on-LAN enviados a {$sentCount} equipos del grupo '{$group->name}'." . ($errorCount > 0 ? " {$errorCount} equipos fallaron." : ""));
            } else {
                return redirect()->back()->with('error', "No se pudo enviar Wake-on-LAN a ningún equipo del grupo '{$group->name}'. Verifica que tengan MAC configurada.");
            }
        } catch (\Exception $e) {
            Log::error('Error en wakeOnLanGroup: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al despertar los hosts del grupo: ' . $e->getMessage());
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
     * Ver detalles de un host
     */
    public function show($id)
    {
        try {
            $host = \App\Models\MonitorHost::findOrFail($id);
            $user = \Auth::user();
            // Forzar actualización de telemetría si el host es local
            if ($host->ip_address === '127.0.0.1' || $host->ip_address === 'localhost') {
                // Ejecutar el agente localmente (solo si el servidor es el host)
                try {
                    exec('python3 ' . base_path('public/agent/telemetry_agent.py') . ' > /dev/null 2>&1 &');
                } catch (\Exception $e) {
                    \Log::warning('No se pudo ejecutar el agente de telemetría local: ' . $e->getMessage());
                }
            }
            // Permitir a cualquier usuario autenticado ver cualquier host
            return view('monitor.show', compact('host'));
        } catch (\Exception $e) {
            \Log::error('Error al mostrar host: ' . $e->getMessage());
            return redirect()->route('monitor.index')
                ->with('error', 'Error al obtener detalles del host: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar formulario de edición de un host
     */
    public function edit($id)
    {
        $host = \App\Models\MonitorHost::findOrFail($id);
        $groups = \App\Models\MonitorGroup::getGroupsForUser(\Auth::user());
        return view('monitor.edit', compact('host', 'groups'));
    }

    /**
     * Actualizar un host en la base de datos
     */
    public function update(Request $request, $id)
    {
        $validator = \Validator::make($request->all(), [
            'hostname' => 'required|string|max:255',
            'ip_address' => 'required|ip',
            'description' => 'nullable|string|max:1000',
        ]);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        try {
            $host = \App\Models\MonitorHost::findOrFail($id);
            $host->hostname = preg_replace('/\.tierno\.es$/i', '', $request->hostname);
            $host->ip_address = $request->ip_address;
            $host->description = $request->description;
            $host->mac_address = $request->mac_address;

            // En update, después de asignar el grupo:
            if (preg_match('/^([A-Z][0-9]{2})-/', $host->hostname, $matches)) {
                $nombreGrupo = $matches[1];
                $grupoDetectado = \App\Models\MonitorGroup::firstOrCreate(
                    ['name' => $nombreGrupo],
                    [
                        'description' => 'Aula ' . $nombreGrupo,
                        'type' => 'classroom',
                        'created_by' => \Auth::id()
                    ]
                );
                $host->group_id = $grupoDetectado->id;
                \Log::info("Host {$host->hostname} asignado/creado al grupo {$nombreGrupo} (ID: {$grupoDetectado->id})");
            } else {
                $host->group_id = $request->group_id ?? 0;
            }

            $host->save();
            return redirect()->route('monitor.show', $host->id)
                ->with('success', "Host actualizado correctamente." . (isset($grupoDetectado) ? " Asignado al grupo {$nombreGrupo}." : ""));
        } catch (\Exception $e) {
            \Log::error('Error al actualizar host: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al actualizar el host: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Eliminar un host
     */
    public function destroy($id)
    {
        try {
            $host = \App\Models\MonitorHost::findOrFail($id);
            $host->delete();
            return redirect()->route('monitor.index')->with('success', 'Host eliminado correctamente.');
        } catch (\Exception $e) {
            \Log::error('Error al eliminar host: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al eliminar el host: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar el estado global de los equipos
     */
    public function healthStatus()
    {
        $hosts = \App\Models\MonitorHost::all();
        $summary = [
            'saludable' => 0,
            'critico' => 0,
            'grave' => 0,
            'encendidos_8h' => 0,
        ];
        $equipos = [];
        foreach ($hosts as $host) {
            $cpu = is_array($host->cpu_usage) ? ($host->cpu_usage['percentage'] ?? 0) : ($host->cpu_usage ?? 0);
            $mem = is_array($host->memory_usage) ? ($host->memory_usage['percentage'] ?? 0) : ($host->memory_usage ?? 0);
            $disk = is_array($host->disk_usage) ? ($host->disk_usage['percentage'] ?? 0) : ($host->disk_usage ?? 0);
            $max = max($cpu, $mem, $disk);
            if ($max < 50) $estado = 'saludable';
            elseif ($max < 70) $estado = 'critico';
            else $estado = 'grave';
            $summary[$estado]++;
            // Uptime en horas
            $uptime_h = 0;
            if ($host->uptime) {
                if (preg_match('/(\d+)h/', $host->uptime, $m)) {
                    $uptime_h = (int)$m[1];
                }
            }
            $encendido_8h = $uptime_h >= 8;
            if ($encendido_8h) $summary['encendidos_8h']++;
            $equipos[] = [
                'id' => $host->id,
                'hostname' => $host->hostname,
                'ip_address' => $host->ip_address,
                'cpu' => $cpu,
                'mem' => $mem,
                'disk' => $disk,
                'estado' => $estado,
                'uptime' => $host->uptime,
                'encendido_8h' => $encendido_8h,
            ];
        }
        return view('monitor.health_status', compact('summary', 'equipos'));
    }
    
    /**
     * Detecta información de un host (IP, MAC) basado en hostname o IP
     * Usado para la creación de nuevos equipos
     */
    public function detectHost(Request $request)
    {
        try {
            $hostname = $request->input('hostname');
            $ip = $request->input('ip_address');
            $baseUrl = env('MACSCANNER_URL', 'http://172.20.0.6:5000');
            $mac = null;
            $ipDetectada = null;
            $hostnameDetectado = null;
            $status = 'offline';

            // Si el hostname es tipo aula-columna-fila (ej: B24-A2)
            if (preg_match('/^([A-Z][0-9]{2})-([A-Z])([0-9])$/i', $hostname, $matches)) {
                $aula = strtoupper($matches[1]);
                $columna = strtoupper($matches[2]);
                $fila = intval($matches[3]);
                $macscannerUrl = rtrim($baseUrl, '/') . '/scan-hostnames';
                $payload = [
                    'aula' => $aula,
                    'columnas' => [$columna],
                    'filas' => [$fila],
                    'dominio' => 'tierno.es'
                ];
                $options = [
                    'http' => [
                        'header'  => "Content-type: application/json\r\n",
                        'method'  => 'POST',
                        'content' => json_encode($payload),
                        'timeout' => 20
                    ]
                ];
                $context = stream_context_create($options);
                $response = @file_get_contents($macscannerUrl, false, $context);
                if ($response !== false) {
                    $data = json_decode($response, true);
                    if (isset($data['success']) && $data['success'] && isset($data['hosts'][0])) {
                        $hostData = $data['hosts'][0];
                        $mac = $hostData['mac'] ?? null;
                        $ipDetectada = $hostData['ip'] ?? null;
                        $hostnameDetectado = $hostData['hostname'] ?? $hostname;
                        $status = 'online';
                    }
                }
            }
            // Si no es tipo aula-columna-fila, usar la lógica anterior
            if ($status === 'offline') {
                // 1. Si tenemos hostname, intentar con scan?hostname
                if (!empty($hostname)) {
                    $hostnameCompleto = !str_contains($hostname, '.') ? $hostname . '.tierno.es' : $hostname;
                    $pythonServiceUrl = rtrim($baseUrl, '/') . '/scan?hostname=' . urlencode($hostnameCompleto);
                    $response = @file_get_contents($pythonServiceUrl);
                    if ($response !== false) {
                        $data = json_decode($response, true);
                        if (isset($data['success']) && $data['success']) {
                            $mac = $data['mac'] ?? null;
                            $ipDetectada = $data['ip'] ?? null;
                            $hostnameDetectado = $data['hostname'] ?? $hostname;
                            $status = 'online';
                        }
                    }
                }
                // 2. Si tenemos IP, intentar con scan?ip
                if (($status === 'offline' || empty($mac)) && !empty($ip)) {
                    $pythonServiceUrl = rtrim($baseUrl, '/') . '/scan?ip=' . urlencode($ip);
                    $response = @file_get_contents($pythonServiceUrl);
                    if ($response !== false) {
                        $data = json_decode($response, true);
                        if (isset($data['success']) && $data['success']) {
                            $mac = $data['mac'] ?? $mac;
                            $ipDetectada = $ip;
                            $hostnameDetectado = $data['hostname'] ?? $hostname;
                            $status = 'online';
                        }
                    }
                }
            }
            // Si aún no tenemos MAC, intentar con ping y arp
            if (($status === 'offline' || empty($mac)) && !empty($ipDetectada)) {
                $pingCommand = "ping -c 2 -W 1 " . escapeshellarg($ipDetectada);
                exec($pingCommand, $pingOutput, $pingReturnVal);
                if ($pingReturnVal === 0) {
                    $status = 'online';
                    $arpScanCommand = "arp-scan --interface=eth0 " . escapeshellarg($ipDetectada);
                    exec($arpScanCommand, $arpOutput, $arpReturnVal);
                    foreach ($arpOutput as $line) {
                        if (preg_match('/([0-9a-fA-F]{2}(:[0-9a-fA-F]{2}){5})/', $line, $matches)) {
                            $mac = strtolower($matches[1]);
                            break;
                        }
                    }
                    if (!$mac) {
                        $arpCommand = "arp -n " . escapeshellarg($ipDetectada);
                        exec($arpCommand, $arpOutput, $arpReturnVal);
                        foreach ($arpOutput as $line) {
                            if (preg_match('/([0-9a-fA-F]{2}(:[0-9a-fA-F]{2}){5})/', $line, $matches)) {
                                $mac = strtolower($matches[1]);
                                break;
                            }
                        }
                    }
                }
            }
            if ($status === 'online') {
                return response()->json([
                    'success' => true,
                    'message' => 'Host detectado correctamente',
                    'data' => [
                        'hostname' => $hostnameDetectado ?? $hostname,
                        'ip_address' => $ipDetectada,
                        'mac_address' => $mac,
                        'status' => $status
                    ]
                ]);
            }
            return response()->json([
                'success' => false,
                'message' => 'No se pudo detectar el host. ¿Está encendido y conectado a la red?'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al detectar el host: ' . $e->getMessage()
            ]);
        }
    }

    public function deleteAllHostsInGroup($groupId)
    {
        try {
            $group = \App\Models\MonitorGroup::findOrFail($groupId);
            $deleted = $group->hosts()->delete();
            return redirect()->back()->with('success', "Se han eliminado $deleted equipos de la clase '{$group->name}'.");
        } catch (\Exception $e) {
            \Log::error('Error al limpiar clase: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al limpiar la clase: ' . $e->getMessage());
        }
    }
}