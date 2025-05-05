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
        'system_info' => 'json',
        'cpu_usage' => 'float',
        'memory_usage' => 'float',
        'disk_usage' => 'float',
        'temperature' => 'float',
    ];

    /**
     * Obtener los hosts según los permisos del usuario
     * @param mixed $user El usuario actual
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getHostsForUser($user)
    {
        if (!$user) {
            return collect([]);
        }
        
        // Si es admin, puede ver todos los hosts
        if ($user->is_admin) {
            return self::all();
        }
        
        // Si no es admin, solo ve sus propios hosts
        return self::where('created_by', $user->id)->get();
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
            
            $host->last_seen = now();
            return $host->save();
        } catch (\Exception $e) {
            Log::error('Error al actualizar información del sistema: ' . $e->getMessage());
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
        if ($this->cpu_usage === null) {
            return 'secondary';
        }
        
        if ($this->cpu_usage > 90) {
            return 'danger';
        } elseif ($this->cpu_usage > 70) {
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
        if ($this->memory_usage === null) {
            return 'secondary';
        }
        
        if ($this->memory_usage > 90) {
            return 'danger';
        } elseif ($this->memory_usage > 70) {
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
        if ($this->disk_usage === null) {
            return 'secondary';
        }
        
        if ($this->disk_usage > 90) {
            return 'danger';
        } elseif ($this->disk_usage > 70) {
            return 'warning';
        } else {
            return 'success';
        }
    }

    /**
     * Relación con el usuario creador
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
} 