<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SistemaConfig;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ConfiguracionController extends Controller
{
    /**
     * Mostrar el panel de configuración del sistema
     */
    public function index()
    {
        try {
            // Obtener todas las configuraciones del sistema
            $configuraciones = SistemaConfig::all()->groupBy(function($item) {
                if (Str::startsWith($item->clave, 'modulo_')) {
                    return 'modulos';
                } elseif (Str::startsWith($item->clave, 'politica_password_')) {
                    return 'seguridad';
                } else {
                    return 'general';
                }
            });
            
            return view('admin.configuracion.index', compact('configuraciones'));
        } catch (\Exception $e) {
            Log::error('Error al cargar la configuración: ' . $e->getMessage());
            return back()->with('error', 'Error al cargar la configuración: ' . $e->getMessage());
        }
    }
    
    /**
     * Guardar cambios en la configuración del sistema
     */
    public function guardar(Request $request)
    {
        try {
            // Validar datos recibidos
            $request->validate([
                'modulos' => 'nullable|array',
                'telemetria_intervalo_minutos' => 'required|integer|min:1|max:1440',
            ]);
            
            // Actualizar configuración de módulos
            $modulos = [
                'modulo_calendario_activo',
                'modulo_mensajeria_activo',
                'modulo_documentos_activo',
                'modulo_monitoreo_activo',
                'modulo_ssh_activo',
                'modulo_clases_activo'
            ];
            
            foreach ($modulos as $modulo) {
                $activo = in_array($modulo, $request->modulos ?? []);
                SistemaConfig::establecerConfig($modulo, $activo ? 'true' : 'false', 'boolean', null, Auth::id());
                cache()->forget('config.' . $modulo);
            }
            
            // Si se desactiva el módulo de clases, también desactivamos los elementos relacionados
            if (!in_array('modulo_clases_activo', $request->modulos ?? [])) {
                SistemaConfig::establecerConfig('modulo_mis_clases_activo', 'false', 'boolean', null, Auth::id());
                SistemaConfig::establecerConfig('modulo_gestion_alumnos_activo', 'false', 'boolean', null, Auth::id());
                cache()->forget('config.modulo_mis_clases_activo');
                cache()->forget('config.modulo_gestion_alumnos_activo');
            } else {
                // Si se activa el módulo de clases, activamos también los módulos relacionados
                SistemaConfig::establecerConfig('modulo_mis_clases_activo', 'true', 'boolean', null, Auth::id());
                SistemaConfig::establecerConfig('modulo_gestion_alumnos_activo', 'true', 'boolean', null, Auth::id());
                cache()->forget('config.modulo_mis_clases_activo');
                cache()->forget('config.modulo_gestion_alumnos_activo');
            }
            
            // Limpiar la caché general
            cache()->forget('sistema_config');
            
            // Guardar intervalo de telemetría
            SistemaConfig::establecerConfig(
                'telemetria_intervalo_minutos',
                $request->telemetria_intervalo_minutos,
                'integer',
                'Intervalo de telemetría de los agentes (en minutos)',
                Auth::id()
            );
            
            return redirect()->route('admin.configuracion.index')
                ->with('success', 'Configuración guardada correctamente');
                
        } catch (\Exception $e) {
            Log::error('Error al guardar la configuración: ' . $e->getMessage());
            return back()->with('error', 'Error al guardar la configuración: ' . $e->getMessage());
        }
    }
    
    /**
     * Genera una contraseña segura aleatoria
     */
    private function generarPasswordSeguro($longitud = 12)
    {
        $minusculas = 'abcdefghijklmnopqrstuvwxyz';
        $mayusculas = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $numeros = '0123456789';
        $especiales = '!@#$%^&*()-_=+[]{}|;:,.<>?';
        
        $caracteres = $minusculas . $mayusculas . $numeros . $especiales;
        $password = '';
        
        // Asegurar que tenga al menos un carácter de cada tipo
        $password .= $minusculas[rand(0, strlen($minusculas) - 1)];
        $password .= $mayusculas[rand(0, strlen($mayusculas) - 1)];
        $password .= $numeros[rand(0, strlen($numeros) - 1)];
        $password .= $especiales[rand(0, strlen($especiales) - 1)];
        
        // Completar el resto de la contraseña
        for ($i = 4; $i < $longitud; $i++) {
            $password .= $caracteres[rand(0, strlen($caracteres) - 1)];
        }
        
        // Mezclar los caracteres
        return str_shuffle($password);
    }
} 