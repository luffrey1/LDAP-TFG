<?php

namespace App\Services;

use phpseclib3\Net\SSH2;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use phpseclib3\Crypt\PublicKeyLoader;

class SshService
{
    /**
     * Almacena conexiones activas durante una solicitud
     *
     * @var array
     */
    protected static $activeConnections = [];

    /**
     * Crea una nueva conexión SSH con PTY y devuelve la instancia
     *
     * @param string $host
     * @param int $port
     * @param string $username
     * @param string $password
     * @param string|null $keyPath
     * @return array
     */
    public function createConnection($host, $port = 22, $username = 'root', $password = null, $keyPath = null)
    {
        try {
            // Si no se especificó una ruta de clave, usar la predeterminada
            if (empty($keyPath)) {
                $keyPath = storage_path('ssh/id_rsa');
                if (!file_exists($keyPath)) {
                    $keyPath = storage_path('app/ssh/id_rsa');
                }
            }
            
            Log::info("Intentando conexión SSH a $host:$port como $username");
            
            // Crear una nueva instancia de SSH2
            $ssh = new SSH2($host, $port);
            $ssh->setTimeout(10);
            
            // Método de autenticación usado
            $authMethod = 'password';
            $keyPathUsed = null;
            
            // Intentar autenticación con clave si existe
            if (file_exists($keyPath) && filesize($keyPath) > 0) {
                try {
                    Log::info("Usando clave SSH: $keyPath");
                    $keyContent = file_get_contents($keyPath);
                    
                    if (!empty($keyContent)) {
                        Log::debug("Intentando conexión SSH con clave para $host");
                        $key = PublicKeyLoader::load($keyContent);
                        
                        if ($ssh->login($username, $key)) {
                            Log::info("Conexión SSH exitosa con clave para $host");
                            $authMethod = 'key';
                            $keyPathUsed = $keyPath;
                        } else {
                            Log::warning("Falló autenticación con clave para $host, intentando con contraseña");
                        }
                    } else {
                        Log::warning("Archivo de clave SSH vacío: $keyPath");
                    }
                } catch (\Exception $e) {
                    Log::warning("Error al usar clave SSH: " . $e->getMessage());
                }
            } else {
                Log::warning("No se encontró el archivo de clave SSH: $keyPath");
            }
            
            // Si la autenticación con clave falló, intentar con contraseña
            if ($authMethod !== 'key') {
                if (empty($password)) {
                    throw new \Exception("Se requiere contraseña para la conexión SSH");
                }
                
                Log::debug("Intentando conexión SSH con contraseña para $host");
                if (!$ssh->login($username, $password)) {
                    throw new \Exception("Falló la autenticación SSH para $username@$host");
                }
                
                Log::info("Conexión SSH exitosa con contraseña para $host");
            }
            
            // Ejecutar comandos de configuración sin PTY
            $ssh->exec('export TERM=xterm-256color');
            $ssh->exec('stty -echo');
            
            // Devolver la conexión y el método de autenticación usado
            return [
                'success' => true,
                'connection' => $ssh,
                'auth_method' => $authMethod,
                'key_path' => $keyPathUsed
            ];
            
        } catch (\Exception $e) {
            Log::error("Error al crear conexión SSH: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Ejecuta un comando en la conexión SSH
     *
     * @param SSH2 $connection
     * @param string $command
     * @return string|bool
     */
    public function executeCommand($connection, $command)
    {
        try {
            // El PTY debe estar deshabilitado para ejecutar múltiples comandos
            // Solo habilitarlo para comandos interactivos cuando sea necesario
            return $connection->exec($command);
        } catch (\Exception $e) {
            Log::error("Error ejecutando comando SSH: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Guarda la información de conexión en la sesión
     *
     * @param string $sessionId
     * @param SSH2 $connection
     * @param array $connectionInfo
     * @return bool
     */
    public function cacheConnection($sessionId, $connection, $connectionInfo)
    {
        try {
            // Guardar en la variable estática para uso dentro de la misma solicitud
            self::$activeConnections[$sessionId] = $connection;
            
            // Guardar información de conexión en la sesión
            $sessionData = [
                'connection_info' => $connectionInfo,
                'created_at' => now()->toDateTimeString()
            ];
            
            // Guardar en la sesión PHP
            Session::put('ssh_connection_' . $sessionId, $sessionData);
            
            Log::debug("SSH: Conexión guardada en sesión con ID $sessionId");
            return true;
        } catch (\Exception $e) {
            Log::error("Error al guardar conexión en sesión: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Recupera una conexión SSH de la sesión
     *
     * @param string $sessionId
     * @return \phpseclib3\Net\SSH2|null
     */
    public function getCachedConnection($sessionId)
    {
        // Primero intentamos recuperar de la memoria estática
        if (isset(self::$activeConnections[$sessionId]) && self::$activeConnections[$sessionId] instanceof SSH2) {
            $connection = self::$activeConnections[$sessionId];
            
            // Verificar si la conexión sigue activa
            if ($connection->isConnected()) {
                return $connection;
            }
        }
        
        // Intentar recuperar los datos de conexión de la sesión
        if (Session::has('ssh_connection_' . $sessionId)) {
            $connectionData = Session::get('ssh_connection_' . $sessionId);
            
            try {
                Log::debug("SSH: Recreando conexión para sesión $sessionId usando datos de sesión");
                
                // Extraer datos de conexión
                $connectionInfo = $connectionData['connection_info'];
                $host = $connectionInfo['host'] ?? null;
                $port = $connectionInfo['port'] ?? 22;
                $username = $connectionInfo['username'] ?? 'root';
                $password = $connectionInfo['password'] ?? null;
                $keyPath = $connectionInfo['key_path'] ?? null;
                
                if (empty($host)) {
                    throw new \Exception("Falta información de host en los datos de sesión");
                }
                
                // Crear una nueva conexión SSH2
                $ssh = new SSH2($host, $port);
                $ssh->setTimeout(10);
                
                // Intentar autenticación con clave primero si está disponible
                $authSuccess = false;
                if (!empty($keyPath) && file_exists($keyPath) && filesize($keyPath) > 0) {
                    try {
                        $keyContent = file_get_contents($keyPath);
                        if (!empty($keyContent)) {
                            $key = PublicKeyLoader::load($keyContent);
                            if ($ssh->login($username, $key)) {
                                Log::debug("Reconexión exitosa con clave para $host");
                                $authSuccess = true;
                            }
                        }
                    } catch (\Exception $e) {
                        Log::warning("Error al usar clave SSH para reconexión: " . $e->getMessage());
                    }
                }
                
                // Si falló la autenticación con clave, intentar con contraseña
                if (!$authSuccess) {
                    if (empty($password)) {
                        throw new \Exception("Faltan credenciales para reconectar");
                    }
                    
                    if (!$ssh->login($username, $password)) {
                        throw new \Exception("Falló la autenticación al reconectar");
                    }
                    Log::debug("Reconexión exitosa con contraseña para $host");
                }
                
                // Configurar el entorno del terminal
                $ssh->exec('export TERM=xterm-256color');
                $ssh->exec('stty -echo');
                
                // Guardar en la memoria estática para esta solicitud
                self::$activeConnections[$sessionId] = $ssh;
                
                return $ssh;
                
            } catch (\Exception $e) {
                Log::error("Error al recuperar/recrear conexión SSH: " . $e->getMessage());
                return null;
            }
        }
        
        return null;
    }
    
    /**
     * Cierra una conexión SSH
     *
     * @param SSH2 $connection
     * @return bool
     */
    public function closeConnection($connection)
    {
        try {
            if ($connection->isConnected()) {
                $connection->disconnect();
            }
            return true;
        } catch (\Exception $e) {
            Log::error("Error al cerrar conexión SSH: " . $e->getMessage());
            return false;
        }
    }
} 