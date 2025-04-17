#!/bin/bash

# Script para configurar el servidor LDAP (OpenLDAP) en el entorno Docker

echo "Iniciando configuración de LDAP..."

# Verificar si el contenedor de LDAP está ejecutándose
if ! docker ps | grep openldap-osixia > /dev/null; then
    echo "El contenedor LDAP no está ejecutándose. Iniciando..."
    docker-compose -f docker/docker-compose.yml up -d ldap
    sleep 5  # Esperar a que se inicie
fi

# Copiar los archivos LDIF al contenedor
echo "Copiando archivos LDIF al contenedor LDAP..."
for file in ldap/ldif-clean/*.ldif; do
    echo "Copiando $file..."
    docker cp $file openldap-osixia:/tmp/
done

# Listar los archivos LDIF disponibles
echo "Archivos LDIF disponibles en el contenedor:"
docker exec -it openldap-osixia ls -la /tmp/*.ldif

# Importar los archivos LDIF en orden
echo "Importando archivos LDIF..."

# Unidades organizativas
echo "Importando OUs..."
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f /tmp/01-ou.ldif || true

# Usuarios administradores
echo "Importando usuario admin LDAP..."
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f /tmp/02-ldap-admin-user.ldif || true

# Grupos administradores
echo "Importando grupo de admins LDAP..."
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f /tmp/03-ldapadmins-group.ldif || true

# Grupo everybody
echo "Importando grupo everybody..."
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f /tmp/04-everybody-group.ldif || true

# Usuarios alumnos
echo "Importando usuarios alumnos..."
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f /tmp/05-alumnos-users.ldif || true

# Grupo alumnos
echo "Importando grupo alumnos..."
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f /tmp/06-alumnos-groups.ldif || true

# Usuarios profesores
echo "Importando usuarios profesores..."
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f /tmp/07-profesor-users.ldif || true

# Grupo profesores
echo "Importando grupo profesores..."
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f /tmp/08-profesores-group.ldif || true

# Verificar la configuración
echo "Verificando la configuración LDAP..."
docker exec -it openldap-osixia ldapsearch -x -b dc=test,dc=tierno,dc=es -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin

echo "Configuración de LDAP completada."
echo ""
echo "Usuarios LDAP disponibles:"
echo "Usuario: ldap-admin    Contraseña: password    Rol: Administrador"
echo "Usuario: profesor      Contraseña: password    Rol: Profesor"
echo "Usuario: alumno        Contraseña: password    Rol: Alumno"
echo ""
echo "Para probar la conexión LDAP, ejecute:"
echo "docker exec -it laravel-app php /var/www/html/test-ldap.php" 