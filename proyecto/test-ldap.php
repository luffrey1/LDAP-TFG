<?php

require __DIR__ . '/vendor/autoload.php';

use LdapRecord\Connection;
use LdapRecord\Auth\BindException;

echo "Verificando el archivo .env:\n";
$envContent = file_get_contents(__DIR__ . '/.env');
echo "Contenido de .env para LDAP_HOST: ";
preg_match('/LDAP_HOST=(.*)/', $envContent, $matches);
echo $matches[0] . "\n";

// Mostrar todas las variables de entorno
echo "\nVariables de entorno del sistema:\n";
$envVars = getenv();
foreach ($envVars as $key => $value) {
    if (strpos($key, 'LDAP_') === 0) {
        echo "$key = $value\n";
    }
}

// Cargar variables de entorno manualmente desde .env
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

echo "\nVariables cargadas directamente del archivo .env:\n";
foreach ($env as $key => $value) {
    if (strpos($key, 'LDAP_') === 0) {
        echo "$key = $value\n";
    }
}

// Configuración de conexión LDAP usando las variables cargadas manualmente
$ldapHost = $env['LDAP_HOST'] ?? 'openldap-osixia';
$ldapPort = $env['LDAP_PORT'] ?? '389';
$ldapBaseDn = $env['LDAP_BASE_DN'] ?? 'dc=test,dc=tierno,dc=es';
$ldapUsername = $env['LDAP_USERNAME'] ?? 'cn=admin,dc=test,dc=tierno,dc=es';
$ldapPassword = $env['LDAP_PASSWORD'] ?? 'admin';

echo "\nConfiguración LDAP a utilizar:\n";
echo "LDAP_HOST: $ldapHost\n";
echo "LDAP_PORT: $ldapPort\n";
echo "LDAP_BASE_DN: $ldapBaseDn\n";
echo "LDAP_USERNAME: $ldapUsername\n";
echo "LDAP_PASSWORD: $ldapPassword\n";

$connection = new Connection([
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
    // Conectarse al servidor LDAP
    echo "\nIntentando conectar a LDAP...\n";
    $connection->connect();

    echo "Conexión LDAP exitosa.\n";

    // Probar autenticación con usuario ldap-admin
    try {
        $userDn = 'uid=ldap-admin,ou=people,' . $ldapBaseDn;
        $password = 'password';
        
        echo "\nIntentando autenticar: $userDn\n";
        
        if ($connection->auth()->attempt($userDn, $password)) {
            echo "Autenticación exitosa para ldap-admin.\n";
        } else {
            echo "Autenticación fallida para ldap-admin.\n";
        }
    } catch (BindException $e) {
        echo "Error autenticando ldap-admin: " . $e->getMessage() . "\n";
    }

    // Probar autenticación con usuario profesor
    try {
        $userDn = 'uid=profesor,ou=people,' . $ldapBaseDn;
        $password = 'password';
        
        echo "\nIntentando autenticar: $userDn\n";
        
        if ($connection->auth()->attempt($userDn, $password)) {
            echo "Autenticación exitosa para profesor.\n";
        } else {
            echo "Autenticación fallida para profesor.\n";
        }
    } catch (BindException $e) {
        echo "Error autenticando profesor: " . $e->getMessage() . "\n";
    }

    // Listar usuarios
    echo "\nListando usuarios en LDAP:\n";
    $results = $connection->query()
        ->where('objectClass', '=', 'person')
        ->get();
    
    echo "Total de usuarios encontrados: " . count($results) . "\n";
    foreach ($results as $item) {
        echo "DN: " . $item['dn'] . "\n";
        echo "UID: " . ($item['uid'][0] ?? 'N/A') . "\n";
        echo "CN: " . ($item['cn'][0] ?? 'N/A') . "\n\n";
    }

} catch (Exception $e) {
    echo "Error de conexión LDAP: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
} 