#!/bin/bash

# Detener y eliminar el contenedor existente si está en ejecución
echo "Deteniendo y eliminando el contenedor existente si está en ejecución..."
docker stop openldap-osixia 2>/dev/null
docker rm openldap-osixia 2>/dev/null

# Iniciar un nuevo contenedor con la configuración básica
echo "Iniciando el contenedor LDAP..."
docker run --name openldap-osixia -p 389:389 -p 636:636 -d mi-openldap

# Esperar a que el contenedor esté en funcionamiento
echo "Esperando a que el contenedor esté listo..."
sleep 10

# Verificar que la carpeta ldif existe
echo "Verificando acceso a los archivos LDIF..."
if [ ! -d "ldap/ldif" ]; then
    echo "Error: No se encuentra la carpeta ldap/ldif"
    exit 1
fi

# Copiar todos los archivos LDIF al contenedor
echo "Copiando archivos LDIF al contenedor..."
docker exec -it openldap-osixia mkdir -p /tmp/ldif
for ldif_file in ldap/ldif/*.ldif; do
    filename=$(basename "$ldif_file")
    echo "Copiando $filename..."
    docker cp "$ldif_file" openldap-osixia:/tmp/ldif/
done

# Procesar archivos LDIF en orden numérico
echo "Procesando archivos LDIF en orden..."
for ldif_file in $(docker exec -it openldap-osixia ls -v /tmp/ldif/ | grep "\.ldif$"); do
    echo "Importando $ldif_file..."
    docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w "admin" -f "/tmp/ldif/$ldif_file" || true
done

# Verificar usuarios y grupos
echo "Verificando usuarios y grupos..."
docker exec -it openldap-osixia ldapsearch -x -b "dc=test,dc=tierno,dc=es" -H ldap://localhost "(objectClass=person)" uid

# Reiniciar el servicio de Laravel
echo "Reiniciando el servicio de Laravel..."
docker restart laravel-app

echo "Configuración completada. Ahora puedes acceder con los siguientes usuarios LDAP:"
echo "- ldap-admin (contraseña: password)"
echo "- profesor (contraseña: password)"
echo "- alumno (contraseña: password)" 