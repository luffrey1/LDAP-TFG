<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MonitorHost;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Auth;

class MonitorController extends Controller
{
    /**
     * Mostrar la página de monitoreo de hosts
     */
    public function index()
    {
        $user = Auth::user();
        $hosts = MonitorHost::getHostsForUser($user);
        
        // Compactar las variables a pasar a la vista
        return view('monitor.index', compact('hosts'));
    }
    
    /**
     * Mostrar el formulario para crear un nuevo host
     */
    public function create()
    {
        return view('monitor.create');
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
            
            // Creamos un proceso para ejecutar ping según el sistema operativo
            if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                $process = new Process(['ping', '-n', '1', '-w', '1000', $ip]);
            } else {
                $process = new Process(['ping', '-c', '1', '-W', '1', $ip]);
            }
            
            $process->run();
            
            // Si el ping es exitoso, actualizamos el estado
            if ($process->isSuccessful()) {
                MonitorHost::updateStatus($id, 'online');
                return response()->json(['status' => 'online', 'message' => 'Host está en línea']);
            } else {
                MonitorHost::updateStatus($id, 'offline');
                return response()->json(['status' => 'offline', 'message' => 'Host está fuera de línea']);
            }
        } catch (ProcessFailedException $e) {
            Log::error('Error al ejecutar ping: ' . $e->getMessage());
            MonitorHost::updateStatus($id, 'error');
            return response()->json(['status' => 'error', 'message' => 'Error al ejecutar ping']);
        } catch (\Exception $e) {
            Log::error('Error en ping: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * Ejecutar ping a todos los hosts
     */
    public function pingAll()
    {
        try {
            $user = Auth::user();
            $hosts = MonitorHost::getHostsForUser($user);
            
            foreach ($hosts as $host) {
                $this->ping(request(), $host->id);
            }
            
            return redirect()->route('monitor.index')
                ->with('success', 'Estado de todos los hosts actualizado.');
        } catch (\Exception $e) {
            Log::error('Error en pingAll: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error al actualizar hosts: ' . $e->getMessage());
        }
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
            
            // Verificar si el usuario tiene permiso para ver este host
            if (!Auth::user()->is_admin && $host->created_by != Auth::id()) {
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
            
            // Verificar si el usuario tiene permiso para editar este host
            if (!Auth::user()->is_admin && $host->created_by != Auth::id()) {
                abort(403, 'No tienes permiso para editar este host');
            }
            
            return view('monitor.edit', compact('host'));
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
            
            // Verificar si el usuario tiene permiso para actualizar este host
            if (!Auth::user()->is_admin && $host->created_by != Auth::id()) {
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
            
            // Verificar si el usuario tiene permiso para eliminar este host
            if (!Auth::user()->is_admin && $host->created_by != Auth::id()) {
                abort(403, 'No tienes permiso para eliminar este host');
            }
            
            $hostname = $host->hostname;
            $host->delete();
            
            return redirect()->route('monitor.index')
                ->with('success', "Host '{$hostname}' eliminado correctamente.");
        } catch (\Exception $e) {
            Log::error('Error al eliminar host: ' . $e->getMessage());
            return redirect()->route('monitor.index')
                ->with('error', 'Error al eliminar el host: ' . $e->getMessage());
        }
    }
    
    /**
     * Escanear la red local para detectar hosts automáticamente
     */
    public function scanNetwork(Request $request)
    {
        try {
            // Obtener parámetros del escaneo
            $baseIp = $request->input('base_ip', '192.168.1');
            $rangeStart = (int)$request->input('range_start', 1);
            $rangeEnd = (int)$request->input('range_end', 254);
            
            // Limitar el rango para evitar escaneos muy largos
            if ($rangeEnd - $rangeStart > 254) {
                $rangeEnd = $rangeStart + 254;
            }
            
            $discoveredHosts = 0;
            $errorCount = 0;
            
            // Escanear el rango de IPs
            for ($i = $rangeStart; $i <= $rangeEnd; $i++) {
                $ip = $baseIp . '.' . $i;
                
                // Verificar si ya existe este host en la base de datos
                $hostExists = MonitorHost::where('ip_address', $ip)->exists();
                if ($hostExists) {
                    continue;
                }
                
                // Probar ping para ver si el host está online
                try {
                    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                        $process = new Process(['ping', '-n', '1', '-w', '500', $ip]);
                    } else {
                        $process = new Process(['ping', '-c', '1', '-W', '0.5', $ip]);
                    }
                    
                    $process->run();
                    
                    // Solo agregar si el ping es exitoso
                    if ($process->isSuccessful()) {
                        // Intentar obtener el hostname
                        $hostname = '';
                        $hostnameProcess = new Process(['nslookup', $ip]);
                        $hostnameProcess->run();
                        
                        if ($hostnameProcess->isSuccessful()) {
                            $output = $hostnameProcess->getOutput();
                            // Intentar extraer el nombre del host del resultado de nslookup
                            if (preg_match('/name\s*=\s*([^\s]+)/', $output, $matches)) {
                                $hostname = $matches[1];
                                // Eliminar posible punto al final
                                $hostname = rtrim($hostname, '.');
                            }
                        }
                        
                        if (empty($hostname)) {
                            $hostname = 'Host-' . $ip;
                        }
                        
                        // Crear el nuevo host
                        $host = new MonitorHost();
                        $host->hostname = $hostname;
                        $host->ip_address = $ip;
                        $host->status = 'online';
                        $host->last_seen = now();
                        $host->description = 'Detectado automáticamente el ' . now()->format('d/m/Y H:i:s');
                        $host->group_id = $request->input('group_id', 0);
                        $host->created_by = Auth::id();
                        $host->save();
                        
                        $discoveredHosts++;
                    }
                } catch (\Exception $e) {
                    Log::error("Error escaneando IP $ip: " . $e->getMessage());
                    $errorCount++;
                }
            }
            
            return redirect()->route('monitor.index')
                ->with('success', "Escaneo completado. Se encontraron {$discoveredHosts} equipos nuevos.");
        } catch (\Exception $e) {
            Log::error('Error en escaneo de red: ' . $e->getMessage());
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
        $baseIp = '192.168.1'; // IP base por defecto
        
        if ($serverIp && filter_var($serverIp, FILTER_VALIDATE_IP)) {
            $ipParts = explode('.', $serverIp);
            if (count($ipParts) === 4) {
                $baseIp = $ipParts[0] . '.' . $ipParts[1] . '.' . $ipParts[2];
            }
        }
        
        return view('monitor.scan', compact('baseIp'));
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
} 