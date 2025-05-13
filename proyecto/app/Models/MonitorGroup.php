<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitorGroup extends Model
{
    use HasFactory;
    
    protected $table = 'monitor_groups';
    
    protected $fillable = [
        'name',
        'description',
        'location',
        'type',
        'created_by',
    ];
    
    /**
     * Obtener los hosts que pertenecen a este grupo
     */
    public function hosts()
    {
        return $this->hasMany(MonitorHost::class, 'group_id');
    }
    
    /**
     * Relación con el usuario creador
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    /**
     * Obtener el número de hosts en línea en este grupo
     */
    public function getOnlineHostsCountAttribute()
    {
        return $this->hosts()->where('status', 'online')->count();
    }
    
    /**
     * Obtener el número total de hosts en este grupo
     */
    public function getTotalHostsCountAttribute()
    {
        return $this->hosts()->count();
    }
    
    /**
     * Determinar si el grupo está activo (al menos un host en línea)
     */
    public function getIsActiveAttribute()
    {
        return $this->online_hosts_count > 0;
    }
    
    /**
     * Obtener grupos según permisos del usuario
     */
    public static function getGroupsForUser($user)
    {
        // Mostrar todos los grupos independientemente del usuario
        return self::all();
        
        /* Código original comentado
        if (!$user) {
            return collect([]);
        }
        
        // Si es admin, puede ver todos los grupos
        if ($user->is_admin) {
            return self::all();
        }
        
        // Si no es admin, solo ve sus propios grupos
        return self::where('created_by', $user->id)->get();
        */
    }
} 