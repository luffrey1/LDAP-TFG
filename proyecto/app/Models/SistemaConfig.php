<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SistemaConfig extends Model
{
    use HasFactory;

    /**
     * La tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'sistema_config';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array
     */
    protected $fillable = [
        'clave',
        'valor',
        'tipo',
        'descripcion',
        'modificado_por'
    ];

    /**
     * Los atributos que deben convertirse a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Obtener el valor de una configuración específica
     *
     * @param string $clave
     * @param mixed $default
     * @return mixed
     */
    public static function obtenerConfig($clave, $default = null)
    {
        $config = static::where('clave', $clave)->first();
        
        if (!$config) {
            return $default;
        }
        
        return static::convertirValor($config->valor, $config->tipo);
    }

    /**
     * Establecer el valor de una configuración
     *
     * @param string $clave
     * @param mixed $valor
     * @param string $tipo
     * @param string|null $descripcion
     * @param int|null $modificado_por
     * @return bool
     */
    public static function establecerConfig($clave, $valor, $tipo = 'string', $descripcion = null, $modificado_por = null)
    {
        $config = static::updateOrCreate(
            ['clave' => $clave],
            [
                'valor' => $valor,
                'tipo' => $tipo,
                'descripcion' => $descripcion,
                'modificado_por' => $modificado_por
            ]
        );
        
        return $config ? true : false;
    }

    /**
     * Convertir el valor almacenado al tipo correspondiente
     *
     * @param mixed $valor
     * @param string $tipo
     * @return mixed
     */
    protected static function convertirValor($valor, $tipo)
    {
        switch ($tipo) {
            case 'boolean':
                // Convertir explícitamente a booleano
                if (is_string($valor)) {
                    return strtolower($valor) === 'true';
                }
                return (bool) $valor;
            case 'integer':
                return (int) $valor;
            case 'float':
                return (float) $valor;
            case 'json':
                return json_decode($valor, true);
            case 'array':
                return explode(',', $valor);
            default:
                return $valor;
        }
    }
} 