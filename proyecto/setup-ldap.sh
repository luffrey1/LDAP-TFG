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

# Crear archivos LDIF en el contenedor
echo "Creando archivos LDIF en el contenedor..."

# OU's - uno por uno para mayor claridad
echo "Creando OUs..."
docker exec -it openldap-osixia bash -c "cat > /tmp/01-ou-people.ldif << EOF
dn: ou=people,dc=test,dc=tierno,dc=es
objectClass: organizationalUnit
ou: people
EOF"

docker exec -it openldap-osixia bash -c "cat > /tmp/02-ou-groups.ldif << EOF
dn: ou=groups,dc=test,dc=tierno,dc=es
objectClass: organizationalUnit
ou: groups
EOF"

# Importar OUs una por una
echo "Importando OU people..."
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w "admin" -f /tmp/01-ou-people.ldif
echo "Importando OU groups..."
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w "admin" -f /tmp/02-ou-groups.ldif

# Usuario ldap-admin
echo "Creando usuario ldap-admin..."
docker exec -it openldap-osixia bash -c "cat > /tmp/03-ldap-admin.ldif << EOF
dn: uid=ldap-admin,ou=people,dc=test,dc=tierno,dc=es
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
homeDirectory: /home/ldap-admin
EOF"

# Importar usuario ldap-admin
echo "Importando usuario ldap-admin..."
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w "admin" -f /tmp/03-ldap-admin.ldif

# Grupo ldapadmins
echo "Creando grupo ldapadmins..."
docker exec -it openldap-osixia bash -c "cat > /tmp/04-ldapadmins.ldif << EOF
dn: cn=ldapadmins,ou=groups,dc=test,dc=tierno,dc=es
objectClass: top
objectClass: posixGroup
objectClass: groupOfUniqueNames
cn: ldapadmins
uniqueMember: uid=ldap-admin,ou=people,dc=test,dc=tierno,dc=es
gidNumber: 9001
EOF"

# Importar grupo ldapadmins
echo "Importando grupo ldapadmins..."
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w "admin" -f /tmp/04-ldapadmins.ldif

# Usuario profesor
echo "Creando usuario profesor..."
docker exec -it openldap-osixia bash -c "cat > /tmp/05-profesor.ldif << EOF
dn: uid=profesor,ou=people,dc=test,dc=tierno,dc=es
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
homeDirectory: /home/profesor
EOF"

# Importar usuario profesor
echo "Importando usuario profesor..."
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w "admin" -f /tmp/05-profesor.ldif

# Usuario alumno
echo "Creando usuario alumno..."
docker exec -it openldap-osixia bash -c "cat > /tmp/06-alumno.ldif << EOF
dn: uid=alumno,ou=people,dc=test,dc=tierno,dc=es
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
homeDirectory: /home/alumno
EOF"

# Importar usuario alumno
echo "Importando usuario alumno..."
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w "admin" -f /tmp/06-alumno.ldif

# Grupo profesores
echo "Creando grupo profesores..."
docker exec -it openldap-osixia bash -c "cat > /tmp/07-profesores.ldif << EOF
dn: cn=profesores,ou=groups,dc=test,dc=tierno,dc=es
objectClass: top
objectClass: posixGroup
objectClass: groupOfUniqueNames
cn: profesores
uniqueMember: uid=profesor,ou=people,dc=test,dc=tierno,dc=es
gidNumber: 10001
EOF"

# Importar grupo profesores
echo "Importando grupo profesores..."
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w "admin" -f /tmp/07-profesores.ldif

# Grupo alumnos
echo "Creando grupo alumnos..."
docker exec -it openldap-osixia bash -c "cat > /tmp/08-alumnos.ldif << EOF
dn: cn=alumnos,ou=groups,dc=test,dc=tierno,dc=es
objectClass: top
objectClass: posixGroup
objectClass: groupOfUniqueNames
cn: alumnos
uniqueMember: uid=alumno,ou=people,dc=test,dc=tierno,dc=es
gidNumber: 10002
EOF"

# Importar grupo alumnos
echo "Importando grupo alumnos..."
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w "admin" -f /tmp/08-alumnos.ldif

# Grupo everybody
echo "Creando grupo everybody..."
docker exec -it openldap-osixia bash -c "cat > /tmp/09-everybody.ldif << EOF
dn: cn=everybody,ou=groups,dc=test,dc=tierno,dc=es
objectClass: top
objectClass: posixGroup
objectClass: groupOfUniqueNames
cn: everybody
uniqueMember: uid=ldap-admin,ou=people,dc=test,dc=tierno,dc=es
uniqueMember: uid=profesor,ou=people,dc=test,dc=tierno,dc=es
uniqueMember: uid=alumno,ou=people,dc=test,dc=tierno,dc=es
gidNumber: 10000
EOF"

# Importar grupo everybody
echo "Importando grupo everybody..."
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w "admin" -f /tmp/09-everybody.ldif

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