<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evento extends Model
{
    use HasFactory;

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'eventos';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array
     */
    protected $fillable = [
        'titulo',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'todo_el_dia',
        'color',
        'creado_por',
        'publico'
    ];

    /**
     * Los atributos que deben convertirse a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'todo_el_dia' => 'boolean',
        'publico' => 'boolean'
    ];

    /**
     * Obtener el creador del evento.
     */
    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /**
     * Participantes del evento.
     */
    public function participantes()
    {
        return $this->belongsToMany(User::class, 'evento_participantes', 'evento_id', 'usuario_id')
                    ->withPivot('confirmado', 'notificado')
                    ->withTimestamps();
    }

    /**
     * Scope para obtener eventos públicos.
     */
    public function scopePublicos($query)
    {
        return $query->where('publico', true);
    }

    /**
     * Scope para obtener eventos del usuario.
     */
    public function scopeDelUsuario($query, $usuario_id)
    {
        return $query->where('creado_por', $usuario_id)
                    ->orWhereHas('participantes', function($q) use ($usuario_id) {
                        $q->where('users.id', $usuario_id);
                    });
    }

    /**
     * Scope para obtener eventos próximos.
     */
    public function scopeProximos($query, $dias = 30)
    {
        $hoy = now();
        $limite = $hoy->copy()->addDays($dias);
        
        return $query->where('fecha_inicio', '>=', $hoy)
                    ->where('fecha_inicio', '<=', $limite);
    }
} 