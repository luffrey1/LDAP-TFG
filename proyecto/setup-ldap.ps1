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

# Crear OUs - estructura básica
Write-Host "Creando OUs..."
docker exec -it openldap-osixia bash -c 'echo "dn: ou=people,dc=test,dc=tierno,dc=es
objectClass: organizationalUnit
ou: people" > /tmp/01-ou-people.ldif'

docker exec -it openldap-osixia bash -c 'echo "dn: ou=groups,dc=test,dc=tierno,dc=es
objectClass: organizationalUnit
ou: groups" > /tmp/02-ou-groups.ldif'

# Importar OUs una por una
Write-Host "Importando OU people..."
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w "admin" -f /tmp/01-ou-people.ldif
Write-Host "Importando OU groups..."
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w "admin" -f /tmp/02-ou-groups.ldif

# Crear usuario ldap-admin
Write-Host "Creando usuario ldap-admin..."
docker exec -it openldap-osixia bash -c 'echo "dn: uid=ldap-admin,ou=people,dc=test,dc=tierno,dc=es
givenName: LDAP
sn: Admin
uid: ldap-admin
mail: ldap-admin@test.tierno.es
cn: LDAPAdmin
objectClass: person
objectClass: inetOrgPerson
objectClass: posixAccount
userPassword: {SSHA}yKyX1tTYqQ9CmdCs4Vt/VE5vqGaYvlZ5
uidNumber: 9001
gidNumber: 9001
loginShell: /bin/bash
homeDirectory: /home/ldap-admin" > /tmp/03-ldap-admin.ldif'

# Importar usuario ldap-admin
Write-Host "Importando usuario ldap-admin..."
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w "admin" -f /tmp/03-ldap-admin.ldif

# Crear grupo ldapadmins
Write-Host "Creando grupo ldapadmins..."
docker exec -it openldap-osixia bash -c 'echo "dn: cn=ldapadmins,ou=groups,dc=test,dc=tierno,dc=es
objectClass: top
objectClass: posixGroup
objectClass: groupOfUniqueNames
cn: ldapadmins
uniqueMember: uid=ldap-admin,ou=people,dc=test,dc=tierno,dc=es
gidNumber: 9001" > /tmp/04-ldapadmins.ldif'

# Importar grupo ldapadmins
Write-Host "Importando grupo ldapadmins..."
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w "admin" -f /tmp/04-ldapadmins.ldif

# Crear usuario profesor
Write-Host "Creando usuario profesor..."
docker exec -it openldap-osixia bash -c 'echo "dn: uid=profesor,ou=people,dc=test,dc=tierno,dc=es
givenName: Profesor
sn: Ejemplo
uid: profesor
mail: profesor@test.tierno.es
cn: Profesor Ejemplo
objectClass: person
objectClass: inetOrgPerson
objectClass: posixAccount
userPassword: {SSHA}yKyX1tTYqQ9CmdCs4Vt/VE5vqGaYvlZ5
uidNumber: 10001
gidNumber: 10001
loginShell: /bin/bash
homeDirectory: /home/profesor" > /tmp/05-profesor.ldif'

# Importar usuario profesor
Write-Host "Importando usuario profesor..."
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w "admin" -f /tmp/05-profesor.ldif

# Crear usuario alumno
Write-Host "Creando usuario alumno..."
docker exec -it openldap-osixia bash -c 'echo "dn: uid=alumno,ou=people,dc=test,dc=tierno,dc=es
givenName: Alumno
sn: Test
uid: alumno
mail: alumno@test.tierno.es
cn: Alumno Test
objectClass: person
objectClass: inetOrgPerson
objectClass: posixAccount
userPassword: {SSHA}yKyX1tTYqQ9CmdCs4Vt/VE5vqGaYvlZ5
uidNumber: 10002
gidNumber: 10002
loginShell: /bin/bash
homeDirectory: /home/alumno" > /tmp/06-alumno.ldif'

# Importar usuario alumno
Write-Host "Importando usuario alumno..."
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w "admin" -f /tmp/06-alumno.ldif

# Crear grupo profesores
Write-Host "Creando grupo profesores..."
docker exec -it openldap-osixia bash -c 'echo "dn: cn=profesores,ou=groups,dc=test,dc=tierno,dc=es
objectClass: top
objectClass: posixGroup
objectClass: groupOfUniqueNames
cn: profesores
uniqueMember: uid=profesor,ou=people,dc=test,dc=tierno,dc=es
gidNumber: 10001" > /tmp/07-profesores.ldif'

# Importar grupo profesores
Write-Host "Importando grupo profesores..."
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w "admin" -f /tmp/07-profesores.ldif

# Crear grupo alumnos
Write-Host "Creando grupo alumnos..."
docker exec -it openldap-osixia bash -c 'echo "dn: cn=alumnos,ou=groups,dc=test,dc=tierno,dc=es
objectClass: top
objectClass: posixGroup
objectClass: groupOfUniqueNames
cn: alumnos
uniqueMember: uid=alumno,ou=people,dc=test,dc=tierno,dc=es
gidNumber: 10002" > /tmp/08-alumnos.ldif'

# Importar grupo alumnos
Write-Host "Importando grupo alumnos..."
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w "admin" -f /tmp/08-alumnos.ldif

# Crear grupo everybody
Write-Host "Creando grupo everybody..."
docker exec -it openldap-osixia bash -c 'echo "dn: cn=everybody,ou=groups,dc=test,dc=tierno,dc=es
objectClass: top
objectClass: posixGroup
objectClass: groupOfUniqueNames
cn: everybody
uniqueMember: uid=ldap-admin,ou=people,dc=test,dc=tierno,dc=es
uniqueMember: uid=profesor,ou=people,dc=test,dc=tierno,dc=es
uniqueMember: uid=alumno,ou=people,dc=test,dc=tierno,dc=es
gidNumber: 10000" > /tmp/09-everybody.ldif'

# Importar grupo everybody
Write-Host "Importando grupo everybody..."
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w "admin" -f /tmp/09-everybody.ldif

# Verificar usuarios y grupos
Write-Host "Verificando usuarios y grupos..."
docker exec -it openldap-osixia ldapsearch -x -b "dc=test,dc=tierno,dc=es" -H ldap://localhost "(objectClass=person)" uid

# Reiniciar el servicio de Laravel
Write-Host "Reiniciando el servicio de Laravel..."
docker restart laravel-app

Write-Host "Configuración completada. Ahora puedes acceder con los siguientes usuarios LDAP:"
Write-Host "- ldap-admin (contraseña: password)"
Write-Host "- profesor (contraseña: password)"
Write-Host "- alumno (contraseña: password)" 