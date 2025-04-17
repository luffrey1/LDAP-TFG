<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MensajeAdjunto extends Model
{
    use HasFactory;

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'mensaje_adjuntos';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array
     */
    protected $fillable = [
        'mensaje_id',
        'nombre',
        'nombre_original',
        'extension',
        'tipo',
        'tamaño',
        'ruta'
    ];

    /**
     * Los atributos que deben convertirse a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'tamaño' => 'integer'
    ];

    /**
     * Obtener el mensaje al que pertenece el adjunto.
     */
    public function mensaje()
    {
        return $this->belongsTo(Mensaje::class, 'mensaje_id');
    }

    /**
     * Formatear el tamaño del adjunto para mostrar.
     */
    public function getTamañoFormateadoAttribute()
    {
        $size = $this->tamaño;
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        
        for ($i = 0; $size > 1024; $i++) {
            $size /= 1024;
        }
        
        return round($size, 2) . ' ' . $units[$i];
    }
} 