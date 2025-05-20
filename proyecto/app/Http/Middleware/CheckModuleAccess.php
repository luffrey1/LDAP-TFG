<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\SistemaConfig;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckModuleAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $moduleName): Response
    {
        try {
            // Obtener la configuración del módulo
            $configKey = 'modulo_' . $moduleName . '_activo';
            $moduleActive = SistemaConfig::obtenerConfig($configKey, false);
            
            // Registrar en el log para debug
            Log::debug("Verificando módulo $moduleName: " . ($moduleActive ? 'activo' : 'inactivo') . ' para usuario: ' . session('auth_user.username'));
            
            // Verificar si el módulo está activo
            if (!$moduleActive) {
                // Si el usuario no es administrador, denegar acceso
                if (!$this->isAdmin($request)) {
                    Log::warning('Acceso denegado al módulo ' . $moduleName . ' para usuario: ' . session('auth_user.username'));
                    return redirect()->route('dashboard.index')
                        ->with('error', 'El módulo ' . ucfirst($moduleName) . ' no está disponible actualmente.');
                }
                // Si es administrador, permitir acceso pero registrar en el log
                Log::info('Acceso permitido al módulo ' . $moduleName . ' desactivado para administrador: ' . session('auth_user.username'));
            }
            
            return $next($request);
            
        } catch (\Exception $e) {
            Log::error('Error al verificar módulo ' . $moduleName . ': ' . $e->getMessage());
            return redirect()->route('dashboard.index')
                ->with('error', 'Error al verificar el estado del módulo.');
        }
    }
    
    /**
     * Verifica si el usuario tiene permisos de administración
     */
    private function isAdmin(Request $request)
    {
        return session('auth_user.is_admin') || session('auth_user.username') === 'ldap-admin';
    }
} 