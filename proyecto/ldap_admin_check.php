<?php

// Primero cargar el entorno de Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Log;

echo "Verificando usuario LDAPAdmin...\n";

try {
    // Crear una conexión LDAP usando la configuración del archivo
    $config = config('ldap.connections.default');
    $connection = new \LdapRecord\Connection($config);
    $connection->connect();
    
    if($connection->isConnected()) {
        echo "Conexión LDAP establecida correctamente\n";
        
        $baseDn = 'dc=test,dc=tierno,dc=es';
        $peopleOu = 'ou=people,' . $baseDn;
        $groupsOu = 'ou=groups,' . $baseDn;
        $adminGroupDn = 'cn=ldapadmins,' . $groupsOu;
        
        // Buscar al usuario LDAPAdmin
        echo "Buscando usuario LDAPAdmin...\n";
        $user = $connection->query()
            ->in($peopleOu)
            ->where('uid', '=', 'ldap-admin')
            ->first();
            
        if (!$user) {
            echo "Usuario ldap-admin no encontrado. Buscando usuario LDAPAdmin...\n";
            $user = $connection->query()
                ->in($peopleOu)
                ->where('uid', '=', 'LDAPAdmin')
                ->first();
        }
        
        if (!$user) {
            echo "¡ERROR! Usuario LDAPAdmin o ldap-admin no encontrado en LDAP\n";
            exit(1);
        }
        
        echo "Usuario encontrado: " . $user['dn'] . "\n";
        
        // Buscar el grupo de administradores
        echo "Buscando grupo ldapadmins...\n";
        $adminGroup = $connection->query()
            ->in($adminGroupDn)
            ->first();
            
        if (!$adminGroup) {
            echo "¡ERROR! Grupo ldapadmins no encontrado\n";
            exit(1);
        }
        
        echo "Grupo ldapadmins encontrado\n";
        
        // Verificar si el usuario ya está en el grupo
        $isAdmin = false;
        if (isset($adminGroup['uniquemember'])) {
            $members = is_array($adminGroup['uniquemember']) 
                ? $adminGroup['uniquemember'] 
                : [$adminGroup['uniquemember']];
                
            if (in_array($user['dn'], $members)) {
                echo "El usuario ya pertenece al grupo ldapadmins\n";
                $isAdmin = true;
            }
        }
        
        // Si no es administrador, añadirlo al grupo
        if (!$isAdmin) {
            echo "Añadiendo usuario al grupo ldapadmins...\n";
            
            try {
                // Obtener los miembros actuales
                $members = [];
                if (isset($adminGroup['uniquemember'])) {
                    $members = is_array($adminGroup['uniquemember']) 
                        ? $adminGroup['uniquemember'] 
                        : [$adminGroup['uniquemember']];
                }
                
                // Añadir el usuario a los miembros
                $members[] = $user['dn'];
                
                // Actualizar el grupo
                $connection->run(function ($ldap) use ($adminGroupDn, $members) {
                    ldap_modify($ldap, $adminGroupDn, [
                        'uniquemember' => $members
                    ]);
                });
                
                echo "¡Usuario añadido correctamente al grupo ldapadmins!\n";
                
                // Verificamos el cambio
                $adminGroup = $connection->query()
                    ->in($adminGroupDn)
                    ->first();
                    
                if (isset($adminGroup['uniquemember'])) {
                    $members = is_array($adminGroup['uniquemember']) 
                        ? $adminGroup['uniquemember'] 
                        : [$adminGroup['uniquemember']];
                        
                    echo "Miembros actuales del grupo:\n";
                    foreach ($members as $member) {
                        echo "- $member\n";
                    }
                }
                
            } catch (\Exception $e) {
                echo "¡ERROR al añadir el usuario al grupo! " . $e->getMessage() . "\n";
                exit(1);
            }
        }
        
    } else {
        echo "¡ERROR! No se pudo conectar a LDAP\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "¡ERROR! " . $e->getMessage() . "\n";
    exit(1);
}

echo "Operación completada.\n"; 