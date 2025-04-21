# Script PowerShell para configurar LDAP

# Detener y eliminar el contenedor existente si está en ejecución
Write-Host "Deteniendo y eliminando el contenedor existente si está en ejecución..."
docker stop openldap-osixia 2>$null
docker rm openldap-osixia 2>$null

# Iniciar un nuevo contenedor con la configuración básica
Write-Host "Iniciando el contenedor LDAP..."
docker run --name openldap-osixia -p 389:389 -p 636:636 -d mi-openldap

# Esperar a que el contenedor esté en funcionamiento
Write-Host "Esperando a que el contenedor esté listo..."
Start-Sleep -Seconds 10

# Verificar que la carpeta ldif existe
Write-Host "Verificando acceso a los archivos LDIF..."
if (-not (Test-Path "ldap/ldif")) {
    Write-Host "Error: No se encuentra la carpeta ldap/ldif" -ForegroundColor Red
    exit 1
}

# Crear directorio destino en el contenedor
Write-Host "Creando directorio en el contenedor..."
docker exec -it openldap-osixia mkdir -p /tmp/ldif

# Copiar todos los archivos LDIF al contenedor
Write-Host "Copiando archivos LDIF al contenedor..."
$ldifFiles = Get-ChildItem -Path "ldap/ldif" -Filter "*.ldif" | Sort-Object Name

foreach ($file in $ldifFiles) {
    Write-Host "Copiando $($file.Name)..." -ForegroundColor Cyan
    docker cp "ldap/ldif/$($file.Name)" openldap-osixia:/tmp/ldif/
}

# Procesar archivos LDIF en orden numérico
Write-Host "Procesando archivos LDIF en orden..."
$ldifContainerFiles = docker exec -it openldap-osixia ls -v /tmp/ldif/ | Where-Object { $_ -match "\.ldif$" }

foreach ($file in $ldifContainerFiles) {
    Write-Host "Importando $file..." -ForegroundColor Green
    try {
        docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w "admin" -f "/tmp/ldif/$file"
    }
    catch {
        Write-Host "Advertencia: Error al importar $file. Continuando..." -ForegroundColor Yellow
    }
}

# Verificar usuarios y grupos
Write-Host "Verificando usuarios y grupos..."
docker exec -it openldap-osixia ldapsearch -x -b "dc=test,dc=tierno,dc=es" -H ldap://localhost "(objectClass=person)" uid

# Reiniciar el servicio de Laravel
Write-Host "Reiniciando el servicio de Laravel..."
docker restart laravel-app

Write-Host "Configuración completada. Ahora puedes acceder con los siguientes usuarios LDAP:" -ForegroundColor Green
Write-Host "- ldap-admin (contraseña: password)" -ForegroundColor White
Write-Host "- profesor (contraseña: password)" -ForegroundColor White
Write-Host "- alumno (contraseña: password)" -ForegroundColor White 