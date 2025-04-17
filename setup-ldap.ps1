# Script para configurar el servidor LDAP (OpenLDAP) en el entorno Docker en Windows

Write-Host "Iniciando configuración de LDAP..." -ForegroundColor Cyan

# Verificar si el contenedor de LDAP está ejecutándose
$ldapRunning = docker ps | Select-String "openldap-osixia"
if (-not $ldapRunning) {
    Write-Host "El contenedor LDAP no está ejecutándose. Iniciando..." -ForegroundColor Yellow
    docker-compose -f docker/docker-compose.yml up -d ldap
    Start-Sleep -Seconds 5  # Esperar a que se inicie
}

# Copiar los archivos LDIF al contenedor
Write-Host "Copiando archivos LDIF al contenedor LDAP..." -ForegroundColor Cyan
Get-ChildItem -Path ldap/ldif-clean/*.ldif | ForEach-Object {
    Write-Host "Copiando $($_.FullName)..." -ForegroundColor Green
    docker cp $_.FullName openldap-osixia:/tmp/
}

# Listar los archivos LDIF disponibles
Write-Host "Archivos LDIF disponibles en el contenedor:" -ForegroundColor Cyan
docker exec -it openldap-osixia ls -la /tmp/*.ldif

# Importar los archivos LDIF en orden
Write-Host "Importando archivos LDIF..." -ForegroundColor Cyan

# Unidades organizativas
Write-Host "Importando OUs..." -ForegroundColor Green
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f /tmp/01-ou.ldif

# Usuarios administradores
Write-Host "Importando usuario admin LDAP..." -ForegroundColor Green
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f /tmp/02-ldap-admin-user.ldif

# Grupos administradores
Write-Host "Importando grupo de admins LDAP..." -ForegroundColor Green
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f /tmp/03-ldapadmins-group.ldif

# Grupo everybody
Write-Host "Importando grupo everybody..." -ForegroundColor Green
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f /tmp/04-everybody-group.ldif

# Usuarios alumnos
Write-Host "Importando usuarios alumnos..." -ForegroundColor Green
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f /tmp/05-alumnos-users.ldif

# Grupo alumnos
Write-Host "Importando grupo alumnos..." -ForegroundColor Green
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f /tmp/06-alumnos-groups.ldif

# Usuarios profesores
Write-Host "Importando usuarios profesores..." -ForegroundColor Green
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f /tmp/07-profesor-users.ldif

# Grupo profesores
Write-Host "Importando grupo profesores..." -ForegroundColor Green
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f /tmp/08-profesores-group.ldif

# Verificar la configuración
Write-Host "Verificando la configuración LDAP..." -ForegroundColor Cyan
docker exec -it openldap-osixia ldapsearch -x -b dc=test,dc=tierno,dc=es -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin

Write-Host "Configuración de LDAP completada." -ForegroundColor Cyan
Write-Host ""
Write-Host "Usuarios LDAP disponibles:" -ForegroundColor Green
Write-Host "Usuario: ldap-admin    Contraseña: password    Rol: Administrador" -ForegroundColor White
Write-Host "Usuario: profesor      Contraseña: password    Rol: Profesor" -ForegroundColor White
Write-Host "Usuario: alumno        Contraseña: password    Rol: Alumno" -ForegroundColor White
Write-Host ""
Write-Host "Para probar la conexión LDAP, ejecute:" -ForegroundColor Cyan
Write-Host "docker exec -it laravel-app php /var/www/html/test-ldap.php" -ForegroundColor White 