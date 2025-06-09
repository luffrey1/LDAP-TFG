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
                'politica_password_longitud' => 'required|integer|min:6|max:20',
                'politica_password_mayusculas' => 'nullable|boolean',
                'politica_password_numeros' => 'nullable|boolean',
                'politica_password_especiales' => 'nullable|boolean',
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
            
            // Limpiar la caché general
            cache()->forget('sistema_config');
            
            // Actualizar políticas de contraseñas
            SistemaConfig::establecerConfig(
                'politica_password_longitud',
                $request->politica_password_longitud,
                'integer',
                'Longitud mínima de contraseñas',
                Auth::id()
            );
            
            SistemaConfig::establecerConfig(
                'politica_password_mayusculas',
                $request->has('politica_password_mayusculas') ? 'true' : 'false',
                'boolean',
                'Requerir al menos una letra mayúscula en contraseñas',
                Auth::id()
            );
            
            SistemaConfig::establecerConfig(
                'politica_password_numeros',
                $request->has('politica_password_numeros') ? 'true' : 'false',
                'boolean',
                'Requerir al menos un número en contraseñas',
                Auth::id()
            );
            
            SistemaConfig::establecerConfig(
                'politica_password_especiales',
                $request->has('politica_password_especiales') ? 'true' : 'false',
                'boolean',
                'Requerir al menos un carácter especial en contraseñas',
                Auth::id()
            );
            
            // Guardar intervalo de telemetría
            SistemaConfig::establecerConfig(
                'telemetria_intervalo_minutos',
                $request->telemetria_intervalo_minutos,
                'integer',
                'Intervalo de telemetría de los agentes (en minutos)',
                Auth::id()
            );
            
            // Generar contraseña de VPN si se solicita
            if ($request->has('generar_vpn_password')) {
                $vpnPassword = $this->generarPasswordSeguro(12);
                SistemaConfig::establecerConfig(
                    'vpn_password',
                    $vpnPassword,
                    'string',
                    'Contraseña de acceso VPN',
                    Auth::id()
                );
                return redirect()->route('admin.configuracion.index')
                    ->with('success', 'Configuración guardada correctamente')
                    ->with('vpn_password', $vpnPassword);
            }
            
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