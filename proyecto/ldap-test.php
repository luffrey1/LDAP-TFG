<?php

require __DIR__.'/vendor/autoload.php';

try {
    // Crear una conexión LDAP directamente sin usar el contenedor de Laravel
    $connection = new \LdapRecord\Connection([
        'hosts' => ['172.18.0.2'],
        'base_dn' => 'dc=test,dc=tierno,dc=es',
        'username' => 'cn=admin,dc=test,dc=tierno,dc=es',
        'password' => 'ChangeMe12345',
        'port' => 389,
        'use_ssl' => false,
        'use_tls' => false,
        'timeout' => 5,
        'version' => 3,
    ]);
    
    // Conectar manualmente
    $connection->connect();
    
    if($connection->isConnected()) {
        echo 'Conexión LDAP exitosa' . PHP_EOL;
        
        // Buscar cualquier objeto en LDAP
        echo 'Buscando todos los objetos en el directorio LDAP:' . PHP_EOL;
        $query = $connection->query();
        $allObjects = $query->get();
        echo 'Objetos encontrados: ' . count($allObjects) . PHP_EOL;
        
        foreach ($allObjects as $object) {
            echo "- DN: " . $object['dn'] . PHP_EOL;
            if (isset($object['objectclass'])) {
                echo "  Clases: " . implode(', ', $object['objectclass']) . PHP_EOL;
            }
            if (isset($object['cn'])) {
                echo "  CN: " . implode(', ', $object['cn']) . PHP_EOL;
            }
            echo "  ---" . PHP_EOL;
        }
        
        // Buscar usuarios (objectClass=person)
        echo PHP_EOL . 'Buscando usuarios (objectClass=person):' . PHP_EOL;
        $usersQuery = $connection->query();
        $users = $usersQuery->where('objectClass', 'person')->get();
        echo 'Usuarios encontrados: ' . count($users) . PHP_EOL;
        
        foreach ($users as $user) {
            echo "- " . ($user['cn'][0] ?? 'Sin nombre') . " (" . ($user['uid'][0] ?? 'Sin UID') . ")" . PHP_EOL;
        }
    } else {
        echo 'No se pudo conectar a LDAP' . PHP_EOL;
    }
} catch (\Exception $e) {
    echo 'Error de conexión LDAP: ' . $e->getMessage() . PHP_EOL;
    echo 'Archivo: ' . $e->getFile() . ' en la línea ' . $e->getLine() . PHP_EOL;
} 