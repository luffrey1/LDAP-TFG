<?php

// Este script actualiza el archivo .env dentro del contenedor para reflejar la configuración correcta

$envFile = __DIR__ . '/.env';
$envContent = file_get_contents($envFile);

// Actualizar las variables LDAP
$envContent = preg_replace('/LDAP_HOST=.*/', 'LDAP_HOST=openldap-osixia', $envContent);
$envContent = preg_replace('/LDAP_USERNAME=.*/', 'LDAP_USERNAME=cn=admin,dc=test,dc=tierno,dc=es', $envContent);
$envContent = preg_replace('/LDAP_PASSWORD=.*/', 'LDAP_PASSWORD=admin', $envContent);

// Guardar el archivo actualizado
file_put_contents($envFile, $envContent);

echo "Archivo .env actualizado correctamente.\n";

// Leer y mostrar las variables actualizadas
$envUpdated = file_get_contents($envFile);
preg_match('/LDAP_HOST=(.*)/', $envUpdated, $host);
preg_match('/LDAP_PORT=(.*)/', $envUpdated, $port);
preg_match('/LDAP_BASE_DN=(.*)/', $envUpdated, $baseDn);
preg_match('/LDAP_USERNAME=(.*)/', $envUpdated, $username);
preg_match('/LDAP_PASSWORD=(.*)/', $envUpdated, $password);

echo "Configuración LDAP actualizada: \n";
echo "LDAP_HOST: " . ($host[1] ?? 'No encontrado') . "\n";
echo "LDAP_PORT: " . ($port[1] ?? 'No encontrado') . "\n";
echo "LDAP_BASE_DN: " . ($baseDn[1] ?? 'No encontrado') . "\n";
echo "LDAP_USERNAME: " . ($username[1] ?? 'No encontrado') . "\n";
echo "LDAP_PASSWORD: " . ($password[1] ?? 'No encontrado') . "\n"; 