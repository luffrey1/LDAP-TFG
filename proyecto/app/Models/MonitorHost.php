<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class MonitorHost extends Model
{
    use HasFactory;

    protected $table = 'monitor_hosts';
    
    protected $fillable = [
        'hostname',
        'ip_address',
        'mac_address',
        'description',
        'status',
        'last_seen',
        'created_by',
        'group_id',
        'cpu_usage',
        'memory_usage',
        'disk_usage',
        'temperature',
        'uptime',
        'system_info',
    ];

    protected $casts = [
        'last_seen' => 'datetime',
        'last_boot' => 'datetime',
        'system_info' => 'json',
        'disk_usage' => 'json',
        'memory_usage' => 'json',
        'cpu_usage' => 'json',
        'temperature' => 'float',
    ];

    /**
     * Obtener los hosts según los permisos del usuario
     * @param mixed $user El usuario actual
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getHostsForUser($user)
    {
        // Mostrar todos los hosts independientemente del usuario
        return self::all();
        
        /* Código original comentado
        if (!$user) {
            return collect([]);
        }
        
        // Si es admin, puede ver todos los hosts
        if ($user->is_admin) {
            return self::all();
        }
        
        // Si no es admin, solo ve sus propios hosts
        return self::where('created_by', $user->id)->get();
        */
    }

    /**
     * Actualizar el estado de un host
     * @param int $id ID del host
     * @param string $status Nuevo estado
     * @return bool
     */
    public static function updateStatus($id, $status)
    {
        try {
            $host = self::findOrFail($id);
            $host->status = $status;
            $host->last_seen = now();
            return $host->save();
        } catch (\Exception $e) {
            Log::error('Error al actualizar estado del host: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Actualizar la información del sistema de un host
     * @param int $id ID del host
     * @param array $data Datos a actualizar
     * @return bool
     */
    public static function updateSystemInfo($id, $data)
    {
        try {
            $host = self::findOrFail($id);
            
            if (isset($data['cpu_usage'])) {
                $host->cpu_usage = $data['cpu_usage'];
            }
            
            if (isset($data['memory_usage'])) {
                $host->memory_usage = $data['memory_usage'];
            }
            
            if (isset($data['disk_usage'])) {
                $host->disk_usage = $data['disk_usage'];
            }
            
            if (isset($data['temperature'])) {
                $host->temperature = $data['temperature'];
            }
            
            if (isset($data['uptime'])) {
                $host->uptime = $data['uptime'];
            }
            
            if (isset($data['system_info'])) {
                $host->system_info = $data['system_info'];
            }
            
            if (isset($data['last_boot'])) {
                $host->last_boot = $data['last_boot'];
            }
            
            $host->last_seen = now();
            return $host->save();
        } catch (\Exception $e) {
            Log::error('Error al actualizar información del sistema: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Envía un paquete Wake-on-LAN a la dirección MAC del host
     * @return bool Éxito de la operación
     */
    public function wakeOnLan()
    {
        if (empty($this->mac_address)) {
            Log::error("No se puede enviar WoL: dirección MAC no disponible para el host {$this->hostname}");
            return false;
        }
        
        try {
            // Limpia la dirección MAC (elimina caracteres no hex)
            $mac = preg_replace('/[^a-fA-F0-9]/', '', $this->mac_address);
            
            if (strlen($mac) != 12) {
                Log::error("Dirección MAC no válida para WoL: {$this->mac_address}");
                return false;
            }
            
            // Crea el paquete mágico
            $hwAddr = pack('H*', $mac);
            $magicPacket = str_repeat(chr(0xff), 6) . str_repeat($hwAddr, 16);
            
            // Envía el paquete a la dirección de broadcast
            $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
            if (!$sock) {
                Log::error('Error al crear socket para WoL: ' . socket_strerror(socket_last_error()));
                return false;
            }
            
            // Permite broadcast
            socket_set_option($sock, SOL_SOCKET, SO_BROADCAST, true);
            
            // Envía a broadcast general y también a la subred específica
            $broadcastAddr = '255.255.255.255';
            $port = 9;  // Puerto estándar para WoL
            
            // Intenta determinar la dirección de broadcast de la subred
            if ($this->ip_address) {
                $ipParts = explode('.', $this->ip_address);
                if (count($ipParts) == 4) {
                    $subnetBroadcast = $ipParts[0] . '.' . $ipParts[1] . '.' . $ipParts[2] . '.255';
                    socket_sendto($sock, $magicPacket, strlen($magicPacket), 0, $subnetBroadcast, $port);
                }
            }
            
            // Envía a broadcast general como respaldo
            $result = socket_sendto($sock, $magicPacket, strlen($magicPacket), 0, $broadcastAddr, $port);
            socket_close($sock);
            
            if ($result) {
                Log::info("Paquete WoL enviado a {$this->mac_address} para {$this->hostname}");
                return true;
            } else {
                Log::error('Error al enviar paquete WoL: ' . socket_strerror(socket_last_error()));
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Error al enviar WoL: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener el color según el estado
     */
    public function getStatusColorAttribute()
    {
        switch ($this->status) {
            case 'online':
                return 'success';
            case 'offline':
                return 'danger';
            case 'warning':
                return 'warning';
            case 'error':
                return 'danger';
            default:
                return 'secondary';
        }
    }

    /**
     * Obtener el texto legible del estado
     */
    public function getStatusTextAttribute()
    {
        switch ($this->status) {
            case 'online':
                return 'En línea';
            case 'offline':
                return 'Desconectado';
            case 'warning':
                return 'Advertencia';
            case 'error':
                return 'Error';
            default:
                return 'Desconocido';
        }
    }

    /**
     * Obtener el color según el uso de CPU
     */
    public function getCpuColorAttribute()
    {
        if (!isset($this->cpu_usage['percentage'])) {
            return 'secondary';
        }
        
        $usage = $this->cpu_usage['percentage'];
        
        if ($usage > 90) {
            return 'danger';
        } elseif ($usage > 70) {
            return 'warning';
        } else {
            return 'success';
        }
    }

    /**
     * Obtener el color según el uso de memoria
     */
    public function getMemoryColorAttribute()
    {
        if (!isset($this->memory_usage['percentage'])) {
            return 'secondary';
        }
        
        $usage = $this->memory_usage['percentage'];
        
        if ($usage > 90) {
            return 'danger';
        } elseif ($usage > 70) {
            return 'warning';
        } else {
            return 'success';
        }
    }

    /**
     * Obtener el color según el uso de disco
     */
    public function getDiskColorAttribute()
    {
        if (!isset($this->disk_usage['percentage'])) {
            return 'secondary';
        }
        
        $usage = $this->disk_usage['percentage'];
        
        if ($usage > 90) {
            return 'danger';
        } elseif ($usage > 70) {
            return 'warning';
        } else {
            return 'success';
        }
    }
    
    /**
     * Verificar si el host pertenece a un aula específica basado en el hostname
     */
    public function getClassroomAttribute()
    {
        if (empty($this->hostname)) {
            return null;
        }
        
        // Intenta extraer el aula del hostname según el formato B27-A1
        if (preg_match('/^([B][0-9]{2})-[A-F][0-9]/', $this->hostname, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Obtener la ubicación del host basado en el hostname
     * Formato esperado: B27-A1 (Aula B27, columna A, fila 1)
     */
    public function getLocationDetailsAttribute()
    {
        if (empty($this->hostname)) {
            return null;
        }
        
        if (preg_match('/^([B][0-9]{2})-([A-F])([0-9])/', $this->hostname, $matches)) {
            return [
                'aula' => $matches[1],
                'columna' => $matches[2],
                'fila' => $matches[3]
            ];
        }
        
        return null;
    }

    /**
     * Relación con el usuario creador
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    /**
     * Relación con el grupo
     */
    public function group()
    {
        return $this->belongsTo(MonitorGroup::class, 'group_id');
    }
} 