<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClaseGrupo extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Tabla asociada al modelo
     */
    protected $table = 'clase_grupos';

    /**
     * Atributos que son asignables en masa
     */
    protected $fillable = [
        'nombre',
        'descripcion',
        'nivel',
        'curso',
        'seccion',
        'codigo',
        'profesor_id',
        'activo'
    ];

    /**
     * Atributos que deben ser convertidos
     */
    protected $casts = [
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Obtener el profesor tutor del grupo
     */
    public function profesor()
    {
        return $this->belongsTo(User::class, 'profesor_id');
    }

    /**
     * Obtener los alumnos asociados a este grupo
     */
    public function alumnos()
    {
        return $this->hasMany(AlumnoClase::class, 'clase_grupo_id');
    }

    /**
     * Obtener el nombre completo del grupo combinando curso y sección
     */
    public function getNombreCompletoAttribute()
    {
        $nombreCompleto = $this->nombre;

        if ($this->curso && $this->seccion) {
            $nombreCompleto .= " ({$this->curso}º {$this->seccion})";
        } elseif ($this->curso) {
            $nombreCompleto .= " ({$this->curso}º)";
        } elseif ($this->seccion) {
            $nombreCompleto .= " ({$this->seccion})";
        }

        return $nombreCompleto;
    }

    /**
     * Obtener el número de alumnos en el grupo
     */
    public function getNumeroAlumnosAttribute()
    {
        return $this->alumnos()->where('activo', true)->count();
    }
}
