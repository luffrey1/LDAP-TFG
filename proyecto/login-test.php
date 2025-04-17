<?php

// Mostrar todos los errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Crear archivo de log para esta ejecución
$logFile = __DIR__ . '/storage/logs/login-test.log';
file_put_contents($logFile, "Iniciando prueba de login: " . date('Y-m-d H:i:s') . "\n");

try {
    require __DIR__ . '/vendor/autoload.php';
    
    // Función de log
    function writeLog($message) {
        global $logFile;
        file_put_contents($logFile, $message . "\n", FILE_APPEND);
        echo $message . "\n";
    }
    
    writeLog("Autoload cargado exitosamente");
    
    // Probar conexión LDAP directamente
    $env = [];
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
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
    
    $ldapHost = $env['LDAP_HOST'] ?? 'openldap-osixia';
    $ldapPort = $env['LDAP_PORT'] ?? '389';
    $ldapBaseDn = $env['LDAP_BASE_DN'] ?? 'dc=test,dc=tierno,dc=es';
    $ldapUsername = $env['LDAP_USERNAME'] ?? 'cn=admin,dc=test,dc=tierno,dc=es';
    $ldapPassword = $env['LDAP_PASSWORD'] ?? 'admin';
    
    writeLog("Configuración LDAP:");
    writeLog("LDAP_HOST: $ldapHost");
    writeLog("LDAP_PORT: $ldapPort");
    writeLog("LDAP_BASE_DN: $ldapBaseDn");
    writeLog("LDAP_USERNAME: $ldapUsername");
    writeLog("LDAP_PASSWORD: $ldapPassword");
    
    writeLog("Creando conexión LDAP...");
    
    $connection = new \LdapRecord\Connection([
        'hosts' => [$ldapHost],
        'port' => $ldapPort,
        'base_dn' => $ldapBaseDn,
        'username' => $ldapUsername,
        'password' => $ldapPassword,
        'use_ssl' => false,
        'use_tls' => false,
        'timeout' => 5,
        'options' => [
            LDAP_OPT_X_TLS_REQUIRE_CERT => LDAP_OPT_X_TLS_NEVER,
            LDAP_OPT_REFERRALS => 0,
        ],
    ]);
    
    try {
        writeLog("Intentando conectar a LDAP...");
        $connection->connect();
        writeLog("Conexión LDAP exitosa");
        
        // Probar autenticación con usuario ldap-admin
        try {
            $userDn = 'uid=ldap-admin,ou=people,' . $ldapBaseDn;
            $password = 'password';
            
            writeLog("Intentando autenticar con LDAP: $userDn");
            
            if ($connection->auth()->attempt($userDn, $password)) {
                writeLog("Autenticación LDAP exitosa para ldap-admin");
            } else {
                writeLog("Autenticación LDAP fallida para ldap-admin");
            }
        } catch (Exception $e) {
            writeLog("Error autenticando con LDAP: " . $e->getMessage());
        }
        
        // Probar autenticación con usuario profesor
        try {
            $userDn = 'uid=profesor,ou=people,' . $ldapBaseDn;
            $password = 'password';
            
            writeLog("Intentando autenticar con LDAP: $userDn");
            
            if ($connection->auth()->attempt($userDn, $password)) {
                writeLog("Autenticación LDAP exitosa para profesor");
            } else {
                writeLog("Autenticación LDAP fallida para profesor");
            }
        } catch (Exception $e) {
            writeLog("Error autenticando con LDAP: " . $e->getMessage());
        }
    } catch (Exception $e) {
        writeLog("Error de conexión LDAP: " . $e->getMessage());
        writeLog("Trace: " . $e->getTraceAsString());
    }
    
} catch (Exception $e) {
    file_put_contents($logFile, "Error: " . $e->getMessage() . "\n", FILE_APPEND);
    file_put_contents($logFile, "Trace: " . $e->getTraceAsString() . "\n", FILE_APPEND);
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
} 