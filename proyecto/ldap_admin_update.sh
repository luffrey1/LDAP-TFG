#!/bin/bash

echo "Actualizando permisos de usuario LDAPAdmin..."

# Crear archivo LDIF para añadir usuario al grupo
cat > /tmp/add_admin.ldif << EOF
dn: cn=ldapadmins,ou=groups,dc=test,dc=tierno,dc=es
changetype: modify
add: memberUid
memberUid: LDAPAdmin
EOF

# Mostrar contenido del archivo LDIF
echo "Contenido del archivo LDIF:"
cat /tmp/add_admin.ldif
echo ""

# Ejecutar ldapmodify
echo "Ejecutando ldapmodify para añadir LDAPAdmin..."
ldapmodify -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -H ldap://localhost -f /tmp/add_admin.ldif || echo "Posible error si el usuario ya es miembro del grupo"

# Verificar los miembros del grupo
echo "Verificando miembros del grupo ldapadmins..."
ldapsearch -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -H ldap://localhost -b "cn=ldapadmins,ou=groups,dc=test,dc=tierno,dc=es" -s base

echo "Operación completada." 