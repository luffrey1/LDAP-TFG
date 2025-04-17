<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mensaje extends Model
{
    use HasFactory;

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'mensajes';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array
     */
    protected $fillable = [
        'remitente_id',
        'destinatario_id',
        'asunto',
        'contenido',
        'leido',
        'destacado',
        'etiqueta',
        'borrador',
        'eliminado_remitente',
        'eliminado_destinatario'
    ];

    /**
     * Los atributos que deben convertirse a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'leido' => 'boolean',
        'destacado' => 'boolean',
        'borrador' => 'boolean',
        'eliminado_remitente' => 'boolean',
        'eliminado_destinatario' => 'boolean',
    ];

    /**
     * Obtener el remitente del mensaje.
     */
    public function remitente()
    {
        return $this->belongsTo(User::class, 'remitente_id');
    }

    /**
     * Obtener el destinatario del mensaje.
     */
    public function destinatario()
    {
        return $this->belongsTo(User::class, 'destinatario_id');
    }

    /**
     * Obtener los adjuntos del mensaje.
     */
    public function adjuntos()
    {
        return $this->hasMany(MensajeAdjunto::class, 'mensaje_id');
    }

    /**
     * Scope para obtener los mensajes recibidos por un usuario.
     */
    public function scopeRecibidos($query, $usuario_id)
    {
        return $query->where('destinatario_id', $usuario_id)
                    ->where('eliminado_destinatario', false)
                    ->where('borrador', false);
    }

    /**
     * Scope para obtener los mensajes enviados por un usuario.
     */
    public function scopeEnviados($query, $usuario_id)
    {
        return $query->where('remitente_id', $usuario_id)
                    ->where('eliminado_remitente', false)
                    ->where('borrador', false);
    }

    /**
     * Scope para obtener los borradores de un usuario.
     */
    public function scopeBorradores($query, $usuario_id)
    {
        return $query->where('remitente_id', $usuario_id)
                    ->where('borrador', true)
                    ->where('eliminado_remitente', false);
    }

    /**
     * Scope para obtener los mensajes destacados de un usuario.
     */
    public function scopeDestacados($query, $usuario_id)
    {
        return $query->where(function($q) use ($usuario_id) {
                        $q->where('remitente_id', $usuario_id)
                          ->where('eliminado_remitente', false);
                    })
                    ->orWhere(function($q) use ($usuario_id) {
                        $q->where('destinatario_id', $usuario_id)
                          ->where('eliminado_destinatario', false);
                    })
                    ->where('destacado', true)
                    ->where('borrador', false);
    }

    /**
     * Scope para obtener los mensajes en la papelera de un usuario.
     */
    public function scopePapelera($query, $usuario_id)
    {
        return $query->where(function($q) use ($usuario_id) {
                        $q->where('remitente_id', $usuario_id)
                          ->where('eliminado_remitente', true);
                    })
                    ->orWhere(function($q) use ($usuario_id) {
                        $q->where('destinatario_id', $usuario_id)
                          ->where('eliminado_destinatario', true);
                    });
    }
} 