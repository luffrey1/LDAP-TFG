<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

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
        'color',
        'todo_el_dia',
        'creado_por',
        'nombre_creador'
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
    ];

    /**
     * Sobreescribimos el método save para agregar logs de depuración
     */
    public function save(array $options = [])
    {
        try {
            // Si no tenemos un nombre de creador pero tenemos un ID de creador, intentar obtenerlo
            if (empty($this->nombre_creador) && !empty($this->creado_por)) {
                // Intentar obtener desde la sesión de LDAP primero si es el usuario actual
                if (session()->has('auth_user') && session('auth_user.id') == $this->creado_por) {
                    $this->nombre_creador = session('auth_user.nombre') ?: session('auth_user.name') ?: session('auth_user.username');
                    Log::info('Nombre de creador obtenido de sesión LDAP: ' . $this->nombre_creador);
                } else {
                    // Intentar obtener desde la base de datos
                    $usuario = User::find($this->creado_por);
                    if ($usuario) {
                        $this->nombre_creador = $usuario->name;
                        Log::info('Nombre de creador obtenido de base de datos: ' . $this->nombre_creador);
                    } else {
                        // Si no se encontró usuario, usar 'Usuario desconocido'
                        $this->nombre_creador = 'Usuario desconocido';
                        Log::warning('No se encontró nombre para usuario ID: ' . $this->creado_por);
                    }
                }
            }
            
            Log::info('Guardando evento: ' . json_encode([
                'id' => $this->id ?? 'nuevo',
                'titulo' => $this->titulo,
                'fecha_inicio' => $this->fecha_inicio,
                'creado_por' => $this->creado_por,
                'nombre_creador' => $this->nombre_creador
            ]));
            
            return parent::save($options);
        } catch (\Exception $e) {
            Log::error('Error al guardar evento: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtener el creador del evento.
     */
    public function creador()
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /**
     * Obtener el nombre del creador para mostrar
     */
    public function getNombreCreadorAttribute($value)
    {
        // Si ya tenemos un nombre de creador almacenado, usarlo
        if (!empty($value)) {
            return $value;
        }
        
        // Si tenemos relación con el creador, usar su nombre
        if ($this->creador) {
            return $this->creador->name;
        }
        
        // Si estamos en sesión LDAP y somos el creador, usar nuestro nombre
        if (session()->has('auth_user') && session('auth_user.id') == $this->creado_por) {
            return session('auth_user.nombre') ?: session('auth_user.name') ?: session('auth_user.username');
        }
        
        // Si todo lo demás falla
        return 'Usuario desconocido';
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

    /**
     * Determinar si el usuario actual puede editar este evento
     */
    public function puedeEditar($userId = null)
    {
        // Si no se proporciona un ID de usuario, usar el usuario autenticado
        if (!$userId) {
            $userId = session('auth_user.id');
        }
        
        // El creador del evento o los administradores pueden editar
        $esAdmin = session('auth_user.is_admin') ?? false;
        
        return $this->creado_por == $userId || $esAdmin;
    }
} 