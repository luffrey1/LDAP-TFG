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
            if (empty($host->group_id) && preg_match('/^(B[0-9]{2})-/', $host->hostname, $matches)) {
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

            // Validar datos básicos
            $validated = $request->validate([
                'hostname' => 'required|string|max:255',
                'tipo_host' => 'required|in:fija,dhcp',
                'ip_address' => 'nullable|ip',
                'description' => 'nullable|string',
                'group_id' => 'nullable|exists:monitor_groups,id'
            ]);

            \Log::info('Validación pasada', ['validated' => $validated]);

            // Intentar detectar IP y MAC usando el hostname
            $baseUrl = env('MACSCANNER_URL', 'http://172.20.0.6:5000');
            $hostname = $request->hostname;
            $macObtenida = false;
            $mac = null;
            $ipDetectada = null;
            $hostnameDetectado = $hostname;
            $status = 'offline';

            // Usar método más efectivo para detección
            if (preg_match('/^(B[0-9]{2})-([A-Z][0-9])$/i', $hostname, $matches)) {
                $aula = $matches[1]; // B27
                $columna = $matches[2]; // A1
                
                // Usar el endpoint scan-hostnames que tiene la lógica completa
                $macscannerUrl = rtrim($baseUrl, '/') . '/scan-hostnames';
                $payload = [
                    'aula' => $aula,
                    'columnas' => [$columna],
                    'filas' => [1], // No importa la fila para un solo host
                    'dominio' => 'tierno.es'
                ];
                
                $options = [
                    'http' => [
                        'header'  => "Content-type: application/json\r\n",
                        'method'  => 'POST',
                        'content' => json_encode($payload),
                        'timeout' => 10
                    ]
                ];
                
                $context = stream_context_create($options);
                $response = @file_get_contents($macscannerUrl, false, $context);
                
                if ($response !== false) {
                    $data = json_decode($response, true);
                    if (isset($data['success']) && $data['success']) {
                        // Buscar el host en los resultados
                        foreach ($data['hosts'] as $hostData) {
                            $foundHostname = preg_replace('/\.tierno\.es$/i', '', $hostData['hostname']);
                            if (strcasecmp($foundHostname, $hostname) === 0) {
                                $mac = $hostData['mac'] ?? null;
                                $ipDetectada = $hostData['ip'] ?? null;
                                $hostnameDetectado = $hostData['hostname'];
                                $status = 'online';
                                if (!empty($mac)) {
                                    $macObtenida = true;
                                    \Log::info("MAC detectada para hostname {$hostnameDetectado}: {$mac}");
                                }
                                if (!empty($ipDetectada)) {
                                    \Log::info("IP detectada para hostname {$hostnameDetectado}: {$ipDetectada}");
                                }
                                break;
                            }
                        }
                    }
                }
            }

            // Si no se pudo detectar con el método anterior, probar con los métodos tradicionales
            if (!$macObtenida) {
                // Método alternativo: usar scan?hostname
                $pythonServiceUrl = rtrim($baseUrl, '/') . '/scan?hostname=' . urlencode($hostname);
                $response = @file_get_contents($pythonServiceUrl);
                
                if ($response !== false) {
                    $data = json_decode($response, true);
                    if (isset($data['success']) && $data['success']) {
                        $mac = $data['mac'] ?? null;
                        $ipDetectada = $data['ip'] ?? null;
                        $status = 'online';
                        if (!empty($mac)) {
                            $macObtenida = true;
                            \Log::info("MAC detectada para hostname {$hostnameDetectado} (método 2): {$mac}");
                        }
                        if (!empty($ipDetectada)) {
                            \Log::info("IP detectada para hostname {$hostnameDetectado} (método 2): {$ipDetectada}");
                        }
                    }
                }
                
                // Si aún no se detecta y es un nombre sin dominio, intentar con .tierno.es
                if (!$macObtenida && !str_contains($hostname, '.')) {
                    $hostnameCompleto = $hostname . '.tierno.es';
                    $pythonServiceUrl = rtrim($baseUrl, '/') . '/scan?hostname=' . urlencode($hostnameCompleto);
                    $response = @file_get_contents($pythonServiceUrl);
                    
                    if ($response !== false) {
                        $data = json_decode($response, true);
                        if (isset($data['success']) && $data['success']) {
                            $mac = $data['mac'] ?? null;
                            $ipDetectada = $data['ip'] ?? null;
                            $status = 'online';
                            if (!empty($mac)) {
                                $macObtenida = true;
                                \Log::info("MAC detectada para hostname {$hostnameCompleto} (método 3): {$mac}");
                            }
                            if (!empty($ipDetectada)) {
                                \Log::info("IP detectada para hostname {$hostnameCompleto} (método 3): {$ipDetectada}");
                            }
                        }
                    }
                }
            }

            // Si no se pudo detectar y es DHCP, mostrar error
            if ($request->tipo_host === 'dhcp' && !$macObtenida && !$ipDetectada) {
                \Log::warning('No se pudo detectar la información del host', ['hostname' => $hostnameDetectado]);
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'No se pudo detectar la información del host. Asegúrese de que el equipo esté encendido y conectado a la red.');
            }

            // Crear el host
            $host = new MonitorHost();
            $host->hostname = $validated['hostname'];
            $host->ip_address = $ipDetectada ?? $validated['ip_address'] ?? null;
            $host->mac_address = $mac ?? null;
            $host->description = $validated['description'] ?? null;
            $host->created_by = auth()->id();
            $host->status = $status;

            // Asignar grupo automáticamente si no se especificó
            if (empty($validated['group_id'])) {
                // Intentar detectar el grupo por el prefijo del hostname (ej: B27)
                if (preg_match('/^(B[0-9]{2})-/', $validated['hostname'], $matches)) {
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
                ->with('success', 'Host ' . $host->hostname . ' agregado correctamente' . 
                    ($status === 'online' ? ' (IP: ' . $host->ip_address . ', MAC: ' . $host->mac_address . ')' : ''));

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
            Log::info("Iniciando ping para host ID {$id}: {$host->hostname} ({$host->ip_address})");

            $mac = null;
            $ipDetectada = null;
            $status = 'offline';

            $hostnameCompleto = !str_contains($host->hostname, '.') ? $host->hostname . '.tierno.es' : $host->hostname;
            Log::info("PING: Hostname completo: {$hostnameCompleto}");

            // 1. Hacer ping
            $pingCommand = "ping -c 2 -W 1 " . escapeshellarg($hostnameCompleto);
            exec($pingCommand, $pingOutput, $pingReturnVal);
            Log::info("PING: Resultado: " . implode(" | ", $pingOutput) . " (código: {$pingReturnVal})");
            $pingSuccess = ($pingReturnVal === 0);

            if ($pingSuccess) {
                $status = 'online';
                Log::info("PING: Host responde a ping");

                // 2. Intentar obtener la IP con gethostbyname (más fiable que host)
                $ipByPhp = gethostbyname($hostnameCompleto);
                if (filter_var($ipByPhp, FILTER_VALIDATE_IP) && $ipByPhp !== $hostnameCompleto) {
                    $ipDetectada = $ipByPhp;
                    Log::info("PING: IP detectada con gethostbyname: {$ipDetectada}");
                }

                // 3. Si no, intentar con host
                if (!$ipDetectada) {
                    $hostCommand = "host " . escapeshellarg($hostnameCompleto);
                    exec($hostCommand, $hostOutput, $hostReturnVal);
                    Log::info("PING: Resultado host: " . implode(" | ", $hostOutput));
                    foreach ($hostOutput as $line) {
                        if (preg_match('/has address (\d+\.\d+\.\d+\.\d+)/', $line, $matches)) {
                            $ipDetectada = $matches[1];
                            Log::info("PING: IP detectada con host: {$ipDetectada}");
                            break;
                        }
                    }
                }

                // 4. Si no, intentar con nslookup
                if (!$ipDetectada) {
                    $nslookupCommand = "nslookup " . escapeshellarg($hostnameCompleto);
                    exec($nslookupCommand, $nslookupOutput, $nslookupReturnVal);
                    Log::info("PING: Resultado nslookup: " . implode(" | ", $nslookupOutput));
                    foreach ($nslookupOutput as $line) {
                        if (preg_match('/Address: (\d+\.\d+\.\d+\.\d+)/', $line, $matches)) {
                            $ipDetectada = $matches[1];
                            Log::info("PING: IP detectada con nslookup: {$ipDetectada}");
                            break;
                        }
                    }
                }

                // 5. Si tenemos IP, intentar obtener la MAC
                if ($ipDetectada && $ipDetectada !== '127.0.0.1' && $ipDetectada !== '::1') {
                    // a) arp-scan
                    $arpScanCommand = "arp-scan --interface=eth0 " . escapeshellarg($ipDetectada);
                    exec($arpScanCommand, $arpScanOutput, $arpScanReturnVal);
                    Log::info("PING: Resultado arp-scan: " . implode(" | ", $arpScanOutput));
                    foreach ($arpScanOutput as $line) {
                        if (preg_match('/([0-9a-fA-F]{2}(:[0-9a-fA-F]{2}){5})/', $line, $matches)) {
                            $mac = strtolower($matches[1]);
                            Log::info("PING: MAC detectada con arp-scan: {$mac}");
                            break;
                        }
                    }
                    // b) arp -n
                    if (!$mac) {
                        $arpCommand = "arp -n " . escapeshellarg($ipDetectada);
                        exec($arpCommand, $arpOutput, $arpReturnVal);
                        Log::info("PING: Resultado arp -n: " . implode(" | ", $arpOutput));
                        foreach ($arpOutput as $line) {
                            if (preg_match('/([0-9a-fA-F]{2}(:[0-9a-fA-F]{2}){5})/', $line, $matches)) {
                                $mac = strtolower($matches[1]);
                                Log::info("PING: MAC detectada con arp -n: {$mac}");
                                break;
                            }
                        }
                    }
                    // c) ip neigh
                    if (!$mac) {
                        $ipNeighCommand = "ip neigh show " . escapeshellarg($ipDetectada);
                        exec($ipNeighCommand, $ipNeighOutput, $ipNeighReturnVal);
                        Log::info("PING: Resultado ip neigh: " . implode(" | ", $ipNeighOutput));
                        foreach ($ipNeighOutput as $line) {
                            if (preg_match('/([0-9a-fA-F]{2}(:[0-9a-fA-F]{2}){5})/', $line, $matches)) {
                                $mac = strtolower($matches[1]);
                                Log::info("PING: MAC detectada con ip neigh: {$mac}");
                                break;
                            }
                        }
                    }
                } else {
                    // Si la IP es localhost o no se detecta, intentar con /scan-hostnames del microservicio Python
                    Log::warning("PING: IP no válida o no detectada, intentando con /scan-hostnames del microservicio Python");
                    // Detectar si el hostname es de aula tipo B##-X#
                    if (preg_match('/^(B[0-9]{2})-([A-Z][0-9])$/i', $host->hostname, $matches)) {
                        $aula = $matches[1];
                        $columna = $matches[2];
                        $macscannerUrl = rtrim($baseUrl, '/') . '/scan-hostnames';
                        $payload = [
                            'aula' => $aula,
                            'columnas' => [$columna],
                            'filas' => [1],
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
                            if (isset($data['success']) && $data['success']) {
                                foreach ($data['hosts'] as $hostData) {
                                    $foundHostname = preg_replace('/\.tierno\.es$/i', '', $hostData['hostname']);
                                    if (strcasecmp($foundHostname, $host->hostname) === 0) {
                                        $ipDetectada = $hostData['ip'] ?? $ipDetectada;
                                        $mac = $hostData['mac'] ?? $mac;
                                        Log::info("PING: IP/MAC detectadas por /scan-hostnames: IP={$ipDetectada}, MAC={$mac}");
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                Log::warning("PING: El host no responde a ping");
            }

            // Fallback: si no se detectó nada, intentar con microservicio Python como antes
            if ((!$ipDetectada || $ipDetectada === '127.0.0.1' || $ipDetectada === '::1') && empty($mac)) {
                Log::info("PING: Intentando con microservicio Python /scan?hostname");
                $pythonServiceUrl = rtrim($baseUrl, '/') . '/scan?hostname=' . urlencode($hostnameCompleto);
                $response = @file_get_contents($pythonServiceUrl);
                if ($response !== false) {
                    $data = json_decode($response, true);
                    if (isset($data['success']) && $data['success']) {
                        $ipDetectada = $data['ip'] ?? $ipDetectada;
                        $mac = $data['mac'] ?? $mac;
                        $status = 'online';
                        Log::info("PING: IP/MAC detectadas por microservicio: IP={$ipDetectada}, MAC={$mac}");
                    }
                }
            }

            // Actualizar el host si se detectó información
            if ($ipDetectada && $ipDetectada !== '127.0.0.1' && $ipDetectada !== '::1') {
                $host->status = 'online';
                $host->last_seen = now();
                if (!empty($ipDetectada) && $ipDetectada !== $host->ip_address) {
                    $host->ip_address = $ipDetectada;
                    Log::info("PING: Actualizando IP de {$host->hostname} a {$ipDetectada}");
                }
                if (!empty($mac) && $mac !== $host->mac_address) {
                    $host->mac_address = $mac;
                    Log::info("PING: Actualizando MAC de {$host->hostname} a {$mac}");
                }
                $host->save();
                Log::info("PING: Host actualizado - IP: {$host->ip_address}, MAC: {$host->mac_address}");
                return response()->json([
                    'status' => 'online',
                    'message' => 'Host está en línea',
                    'last_seen' => $host->last_seen->format('d/m/Y H:i:s'),
                    'ip' => $host->ip_address,
                    'mac' => $host->mac_address
                ]);
            } else {
                $host->status = 'offline';
                $host->save();
                Log::info("PING: Host no detectado - marcado como offline");
                return response()->json([
                    'status' => 'offline',
                    'message' => 'Host está fuera de línea',
                    'last_seen' => $host->last_seen ? $host->last_seen->format('d/m/Y H:i:s') : null,
                    'ip' => $host->ip_address,
                    'mac' => $host->mac_address
                ]);
            }
        } catch (\Exception $e) {
            Log::error('PING: Error en ping: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * Verifica el estado de todos los hosts usando el microservicio Python y redirige con mensaje
     */
    public function pingAll()
    {
        try {
            $hosts = \App\Models\MonitorHost::all();
            $updated = 0;
            $errors = 0;
            $baseUrl = env('MACSCANNER_URL', 'http://172.20.0.6:5000');
            foreach ($hosts as $host) {
                // Asignar grupo automáticamente si no tiene
                if (empty($host->group_id) && preg_match('/^(B[0-9]{2})-/', $host->hostname, $matches)) {
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
                $ip = $host->ip_address;
                $pythonServiceUrl = rtrim($baseUrl, '/') . '/scan?ip=' . urlencode($ip);
                $response = @file_get_contents($pythonServiceUrl);
                if ($response === false) {
                    \Log::error('No se pudo conectar al microservicio Python para pingAll (host ' . $ip . '): ' . $pythonServiceUrl);
                    $errors++;
                    continue;
                }
                $data = json_decode($response, true);
                if (!$data || !isset($data['success'])) {
                    \Log::error('Respuesta inválida del microservicio Python (pingAll, host ' . $ip . '): ' . $response);
                    $errors++;
                    continue;
                }
                try {
                    $host->status = $data['success'] ? 'online' : 'offline';
                    $host->last_seen = $data['success'] ? now() : $host->last_seen;
                    if (!empty($data['mac'])) $host->mac_address = $data['mac'];
                    if (!empty($data['hostname'])) $host->hostname = $data['hostname'];
                    $host->save();
                    $updated++;
                } catch (\Exception $e) {
                    \Log::error('Error actualizando host en pingAll: ' . $e->getMessage());
                    $errors++;
                }
            }
            $msg = "Estado actualizado. {$updated} hosts actualizados, {$errors} errores.";
            return redirect()->route('monitor.index')->with('success', $msg);
        } catch (\Exception $e) {
            \Log::error('Error en pingAll (Python): ' . $e->getMessage());
            return redirect()->route('monitor.index')->with('error', 'Error al actualizar estado: ' . $e->getMessage());
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
                foreach ($data['hosts'] as $hostData) {
                    try {
                        $host = MonitorHost::where('hostname', $hostData['hostname'])->first();
                        $isNew = false;
                        if (!$host) {
                            $host = new MonitorHost();
                            $host->hostname = preg_replace('/\.tierno\.es$/i', '', $hostData['hostname']);
                            $host->created_by = \Auth::id() ?: 1;
                            $isNew = true;
                        }
                        
                        // Guardar IP del host
                        $host->ip_address = $hostData['ip'] ?? null;
                        
                        // Intentar obtener la MAC usando el hostname primero (más fiable con DHCP)
                        $macObtenida = false;
                        
                        // Primero intentar con el hostname completo (más fiable)
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
                        
                        // Si no se pudo obtener por hostname, intentar con IP
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
                        
                        // Si no se pudo obtener de ninguna manera, usar la MAC proporcionada por el escaneo original
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
                        if ($isNew) {
                            $created++;
                        } else {
                            $updated++;
                        }
                    } catch (\Exception $e) {
                        \Log::error('Error guardando host en scanNetwork (hostname): ' . $e->getMessage());
                        $errors++;
                    }
                }
                $msg = "Escaneo por hostname completado. {$created} equipos nuevos, {$updated} actualizados, {$errors} errores.";
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
            if (preg_match('/^(B[0-9]{2})-/', $host->hostname, $matches)) {
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
            // Verificar autenticación
            if (!auth()->check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No autorizado'
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'hostname' => 'required_without:ip_address|nullable|string|max:255',
                'ip_address' => 'required_without:hostname|nullable|ip',
                'tipo' => 'required|in:fija,dhcp',
            ]);
            
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }
            
            $baseUrl = env('MACSCANNER_URL', 'http://172.20.0.6:5000');
            $hostname = $request->hostname;
            $ip = $request->ip_address;
            $mac = null;
            $ipDetectada = null;
            $hostnameDetectado = $hostname;
            $status = 'offline';
            
            // Si tenemos un hostname, intentar detectar directamente con ping
            if (!empty($hostname)) {
                $hostnameCompleto = !str_contains($hostname, '.') ? $hostname . '.tierno.es' : $hostname;
                Log::info("detectHost: Ejecutando ping directo al hostname: {$hostnameCompleto}");
                
                // Ejecutar ping directamente desde PHP
                $pingCommand = "ping -c 2 -W 1 " . escapeshellarg($hostnameCompleto);
                Log::info("detectHost: Comando: {$pingCommand}");
                
                exec($pingCommand, $pingOutput, $pingReturnVal);
                Log::info("detectHost: Resultado ping directo: " . implode("\n", $pingOutput) . " (código: {$pingReturnVal})");
                
                $pingSuccess = ($pingReturnVal === 0);
                
                if ($pingSuccess) {
                    Log::info("detectHost: ¡Ping exitoso directo a {$hostnameCompleto}!");
                    $status = 'online';
                    $hostnameDetectado = $hostnameCompleto;
                    
                    // Obtener la IP usando el comando host
                    $hostCommand = "host " . escapeshellarg($hostnameCompleto);
                    Log::info("detectHost: Obteniendo IP con comando: {$hostCommand}");
                    
                    exec($hostCommand, $hostOutput, $hostReturnVal);
                    Log::info("detectHost: Resultado comando host: " . implode("\n", $hostOutput));
                    
                    // Extraer la IP del resultado del comando host
                    foreach ($hostOutput as $line) {
                        if (preg_match('/has address (\d+\.\d+\.\d+\.\d+)/', $line, $matches)) {
                            $ipDetectada = $matches[1];
                            Log::info("detectHost: IP detectada: {$ipDetectada}");
                            break;
                        }
                    }
                    
                    // Si se detectó la IP, intentar obtener la MAC
                    if ($ipDetectada) {
                        // Intentar con arp-scan
                        $arpScanCommand = "arp-scan --interface=eth0 " . escapeshellarg($ipDetectada);
                        Log::info("detectHost: Obteniendo MAC con comando: {$arpScanCommand}");
                        
                        exec($arpScanCommand, $arpOutput, $arpReturnVal);
                        Log::info("detectHost: Resultado arp-scan: " . implode("\n", $arpOutput));
                        
                        // Extraer la MAC del resultado de arp-scan
                        foreach ($arpOutput as $line) {
                            if (preg_match('/([0-9a-fA-F]{2}(:[0-9a-fA-F]{2}){5})/', $line, $matches)) {
                                $mac = strtolower($matches[1]);
                                Log::info("detectHost: MAC detectada: {$mac}");
                                break;
                            }
                        }
                        
                        // Si no se encontró con arp-scan, intentar con arp
                        if (!$mac) {
                            $arpCommand = "arp -n " . escapeshellarg($ipDetectada);
                            Log::info("detectHost: Obteniendo MAC con comando alternativo: {$arpCommand}");
                            
                            exec($arpCommand, $arpOutput, $arpReturnVal);
                            Log::info("detectHost: Resultado arp: " . implode("\n", $arpOutput));
                            
                            // Extraer la MAC del resultado de arp
                            foreach ($arpOutput as $line) {
                                if (preg_match('/([0-9a-fA-F]{2}(:[0-9a-fA-F]{2}){5})/', $line, $matches)) {
                                    $mac = strtolower($matches[1]);
                                    Log::info("detectHost: MAC detectada con arp: {$mac}");
                                    break;
                                }
                            }
                        }
                    }
                } else {
                    Log::info("detectHost: El ping directo falló, intentando con el microservicio Python");
                }
            }
            
            // Si no se encontró por ping directo o no tenemos hostname, intentar con microservicio
            if ($status === 'offline') {
                // Si tenemos un hostname con formato de aula
                if (!empty($hostname) && preg_match('/^(B[0-9]{2})-([A-Z][0-9])$/i', $hostname, $matches)) {
                    $aula = $matches[1]; // B27
                    $columna = $matches[2]; // A1
                    
                    Log::info("detectHost: Detectando con formato de aula {$aula}, columna {$columna}");
                    
                    // Usar el endpoint scan-hostnames
                    $macscannerUrl = rtrim($baseUrl, '/') . '/scan-hostnames';
                    $payload = [
                        'aula' => $aula,
                        'columnas' => [$columna],
                        'filas' => [1], // No importa la fila para un solo host
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
                        Log::info("detectHost: Respuesta scan-hostnames: " . json_encode($data));
                        
                        if (isset($data['success']) && $data['success']) {
                            // Buscar el host en los resultados
                            foreach ($data['hosts'] as $hostData) {
                                $foundHostname = preg_replace('/\.tierno\.es$/i', '', $hostData['hostname']);
                                if (strcasecmp($foundHostname, $hostname) === 0) {
                                    $mac = $hostData['mac'] ?? null;
                                    $ipDetectada = $hostData['ip'] ?? null;
                                    $hostnameDetectado = $hostData['hostname'];
                                    $status = 'online';
                                    
                                    if (!empty($mac)) {
                                        Log::info("detectHost: MAC detectada para hostname {$hostnameDetectado}: {$mac}");
                                    }
                                    if (!empty($ipDetectada)) {
                                        Log::info("detectHost: IP detectada para hostname {$hostnameDetectado}: {$ipDetectada}");
                                    }
                                    break;
                                }
                            }
                        }
                    }
                }
                
                // Si no se encontró, probar con hostname en scan
                if ($status === 'offline' && !empty($hostname)) {
                    Log::info("detectHost: Intentando detectar host con scan?hostname");
                    $pythonServiceUrl = rtrim($baseUrl, '/') . '/scan?hostname=' . urlencode($hostname);
                    $response = @file_get_contents($pythonServiceUrl);
                    if ($response !== false) {
                        $data = json_decode($response, true);
                        if (isset($data['success']) && $data['success']) {
                            $mac = $data['mac'] ?? null;
                            $ipDetectada = $data['ip'] ?? null;
                            $status = 'online';
                            
                            if (!empty($mac)) {
                                Log::info("detectHost: MAC detectada con scan?hostname: {$mac}");
                            }
                            if (!empty($ipDetectada)) {
                                Log::info("detectHost: IP detectada con scan?hostname: {$ipDetectada}");
                            }
                        }
                    }
                    
                    // Si no se obtuvo la MAC y el hostname no tiene punto, intentar con .tierno.es
                    if ($status === 'offline' && !str_contains($hostname, '.')) {
                        $hostnameCompleto = $hostname . '.tierno.es';
                        Log::info("detectHost: Intentando con hostname completo: {$hostnameCompleto}");
                        $pythonServiceUrl = rtrim($baseUrl, '/') . '/scan?hostname=' . urlencode($hostnameCompleto);
                        $response = @file_get_contents($pythonServiceUrl);
                        if ($response !== false) {
                            $data = json_decode($response, true);
                            if (isset($data['success']) && $data['success']) {
                                $mac = $data['mac'] ?? null;
                                $ipDetectada = $data['ip'] ?? null;
                                $hostnameDetectado = $hostnameCompleto;
                                $status = 'online';
                                
                                if (!empty($mac)) {
                                    Log::info("detectHost: MAC detectada con scan?hostname completo: {$mac}");
                                }
                                if (!empty($ipDetectada)) {
                                    Log::info("detectHost: IP detectada con scan?hostname completo: {$ipDetectada}");
                                }
                            }
                        }
                    }
                }
            }
            
            // 3. Si no se obtuvo nada o solo tenemos IP, intentar con IP
            if (($status === 'offline' || empty($mac)) && !empty($ip)) {
                Log::info("detectHost: Intentando detectar por IP: {$ip}");
                $pythonServiceUrl = rtrim($baseUrl, '/') . '/scan?ip=' . urlencode($ip);
                $response = @file_get_contents($pythonServiceUrl);
                if ($response !== false) {
                    $data = json_decode($response, true);
                    if (isset($data['success']) && $data['success']) {
                        $mac = $data['mac'] ?? $mac;
                        $ipDetectada = $ip;
                        if (!empty($data['hostname'])) {
                            $hostnameDetectado = $data['hostname'];
                        }
                        $status = 'online';
                        if (!empty($mac)) {
                            Log::info("detectHost: MAC detectada con scan?ip: {$mac}");
                        }
                    }
                }
            }
            
            // 5. Devolver resultado
            if ($status === 'online') {
                Log::info("detectHost: Host detectado correctamente - IP: {$ipDetectada}, MAC: {$mac}");
                return response()->json([
                    'success' => true,
                    'message' => 'Host detectado correctamente',
                    'data' => [
                        'hostname' => $hostnameDetectado,
                        'ip_address' => $ipDetectada,
                        'mac_address' => $mac,
                        'status' => $status
                    ]
                ]);
            }
            
            // Si llegamos aquí, no pudimos detectar el host
            $sugerencia = '¿Está encendido y conectado a la red? Prueba a escribir el hostname completo (ej: B27-A9.tierno.es) o revisa la IP.';
            Log::warning("detectHost: No se detectó el host {$hostname}");
            return response()->json([
                'success' => false,
                'message' => 'No se detectó el host. ' . $sugerencia,
                'data' => null
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error en detectHost: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al detectar host: ' . $e->getMessage()
            ], 500);
        }
    }
}