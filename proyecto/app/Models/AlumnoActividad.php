<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AlumnoActividad extends Model
{
    use HasFactory;

    /**
     * Tabla asociada al modelo
     */
    protected $table = 'alumno_actividades';

    /**
     * Atributos que son asignables en masa
     */
    protected $fillable = [
        'alumno_clase_id',
        'ip_ordenador',
        'nombre_ordenador',
        'sistema_operativo',
        'navegador',
        'tipo_accion',
        'detalles',
        'fecha_hora'
    ];

    /**
     * Atributos que deben ser convertidos
     */
    protected $casts = [
        'fecha_hora' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Obtener el alumno asociado a esta actividad
     */
    public function alumno()
    {
        return $this->belongsTo(AlumnoClase::class, 'alumno_clase_id');
    }

    /**
     * Scope para filtrar actividades por tipo
     */
    public function scopeTipo($query, $tipo)
    {
        return $query->where('tipo_accion', $tipo);
    }

    /**
     * Scope para obtener actividades recientes
     */
    public function scopeRecientes($query, $dias = 7)
    {
        return $query->where('fecha_hora', '>=', now()->subDays($dias));
    }
} 