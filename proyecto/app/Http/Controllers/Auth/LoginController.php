<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use LdapRecord\Connection;
use LdapRecord\Laravel\Auth\ListensForLdapBindFailure;
use Exception;

class LoginController extends Controller
{
    use ListensForLdapBindFailure;
    
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->listenForLdapBindFailure();
    }
    
    /**
     * Carga las variables de entorno directamente del archivo .env
     */
    private function loadEnvVariables()
    {
        $env = [];
        $lines = file(base_path('.env'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                $env[$key] = $value;
            }
        }
        return $env;
    }
    
    /**
     * Get the login username to be used by the controller.
     */
    public function username()
    {
        return 'username';
    }
    
    /**
     * Show the application's login form.
     */
    public function showLoginForm()
    {
        return redirect()->route('login');
    }

    /**
     * Handle a login request to the application.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required'],
            'password' => ['required'],
        ]);

        Log::info('Intento de login desde LoginController', ['username' => $credentials['username']]);

        try {
            // Redirigir al controlador principal de autenticación para mantener la coherencia
            return redirect()->route('auth.login')->withInput();
        } catch (Exception $e) {
            Log::error('Error en autenticación:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->withErrors([
                'username' => 'Ha ocurrido un error. Inténtelo de nuevo.',
            ]);
        }
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
} 