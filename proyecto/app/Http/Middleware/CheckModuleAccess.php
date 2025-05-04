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
            // CASO ESPECIAL: El calendario siempre está disponible para todos
            if ($moduleName === 'calendario') {
                Log::info('Acceso al calendario permitido para todos los usuarios: ' . session('auth_user.username'));
                return $next($request);
            }
            
            $configKey = 'modulo_' . $moduleName . '_activo';
            $moduleActive = SistemaConfig::obtenerConfig($configKey, true); // Por defecto está activo si no existe configuración
            
            // Si el módulo no está activo y el usuario no es administrador, negar acceso
            if (!$moduleActive && !$this->isAdmin($request)) {
                Log::warning('Acceso denegado al módulo ' . $moduleName . ' para usuario: ' . session('auth_user.username'));
                return redirect()->route('dashboard.index')
                    ->with('error', 'El módulo ' . ucfirst($moduleName) . ' no está disponible actualmente.');
            }
            
            Log::info('Acceso permitido al módulo ' . $moduleName . ' para usuario: ' . session('auth_user.username'));
            return $next($request);
            
        } catch (\Exception $e) {
            Log::warning('Error al verificar módulo ' . $moduleName . ': ' . $e->getMessage());
            // Si hay un error, permitir el acceso pero loguear el problema
            return $next($request);
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