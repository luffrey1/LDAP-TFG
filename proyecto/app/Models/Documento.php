<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    use HasFactory;

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'documentos';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array
     */
    protected $fillable = [
        'nombre',
        'nombre_original',
        'descripcion',
        'carpeta',
        'extension',
        'tipo',
        'tamaño',
        'ruta',
        'subido_por',
        'activo'
    ];

    /**
     * Los atributos que deben convertirse a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'tamaño' => 'integer',
        'activo' => 'boolean',
    ];

    /**
     * Obtener el usuario que subió el documento.
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'subido_por');
    }

    /**
     * Formatear el tamaño del documento para mostrar.
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