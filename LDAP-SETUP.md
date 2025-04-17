# Configuración del Servidor LDAP

## IMPORTANTE: Configuración Manual de LDAP

Para el servidor LDAP (osixia/openldap), es necesario copiar manualmente los archivos LDIF al directorio `/tmp` dentro del contenedor porque da error 65 al intentar hacerlo en un Dockerfile personalizado.

## Instrucciones Paso a Paso

### 1. Preparar los Archivos LDIF

Los archivos LDIF se encuentran en el directorio `ldap/ldif-clean/` y deben importarse en el siguiente orden:

1. `01-ou.ldif` - Define las unidades organizativas (people, groups)
2. `02-ldap-admin-user.ldif` - Define el usuario administrador LDAP
3. `03-ldapadmins-group.ldif` - Define el grupo de administradores LDAP
4. `04-everybody-group.ldif` - Define el grupo que contiene a todos los usuarios
5. `05-alumnos-users.ldif` - Define los usuarios alumnos
6. `06-alumnos-groups.ldif` - Define el grupo de alumnos
7. `07-profesor-users.ldif` - Define los usuarios profesores
8. `08-profesores-group.ldif` - Define el grupo de profesores

### 2. Script de Configuración Automática

Se proporcionan dos scripts para configurar automáticamente el servidor LDAP:

- Para Linux/Mac: `setup-ldap.sh`
- Para Windows: `setup-ldap.ps1`

#### En Linux/Mac:

```bash
chmod +x setup-ldap.sh
./setup-ldap.sh
```

#### En Windows:

```powershell
Set-ExecutionPolicy -Scope Process -ExecutionPolicy Bypass
.\setup-ldap.ps1
```

### 3. Configuración Manual

Si prefieres configurar manualmente, sigue estos pasos:

#### Iniciar el Contenedor LDAP

```bash
docker-compose -f docker/docker-compose.yml up -d ldap
```

#### Copiar los Archivos LDIF al Contenedor

```bash
docker cp ldap/ldif-clean/01-ou.ldif openldap-osixia:/tmp/
docker cp ldap/ldif-clean/02-ldap-admin-user.ldif openldap-osixia:/tmp/
docker cp ldap/ldif-clean/03-ldapadmins-group.ldif openldap-osixia:/tmp/
docker cp ldap/ldif-clean/04-everybody-group.ldif openldap-osixia:/tmp/
docker cp ldap/ldif-clean/05-alumnos-users.ldif openldap-osixia:/tmp/
docker cp ldap/ldif-clean/06-alumnos-groups.ldif openldap-osixia:/tmp/
docker cp ldap/ldif-clean/07-profesor-users.ldif openldap-osixia:/tmp/
docker cp ldap/ldif-clean/08-profesores-group.ldif openldap-osixia:/tmp/
```

#### Importar los Archivos LDIF

```bash
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f /tmp/01-ou.ldif
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f /tmp/02-ldap-admin-user.ldif
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f /tmp/03-ldapadmins-group.ldif
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f /tmp/04-everybody-group.ldif
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f /tmp/05-alumnos-users.ldif
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f /tmp/06-alumnos-groups.ldif
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f /tmp/07-profesor-users.ldif
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f /tmp/08-profesores-group.ldif
```

#### Verificar la Configuración

```bash
docker exec -it openldap-osixia ldapsearch -x -b dc=test,dc=tierno,dc=es -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin
```

### 4. Usuarios LDAP Disponibles

| Usuario   | Contraseña | Rol          |
|-----------|------------|--------------|
| ldap-admin| password   | Administrador|
| profesor  | password   | Profesor     |
| alumno    | password   | Alumno       |

### 5. Probar la Conexión LDAP

Para verificar que la autenticación LDAP funciona correctamente:

```bash
docker exec -it laravel-app php /var/www/html/test-ldap.php
```

### 6. Solución de Problemas

1. **Problema**: No se pueden importar los archivos LDIF
   **Solución**: Verificar que la ruta a los archivos LDIF es correcta y que el servidor LDAP está en ejecución

2. **Problema**: No se puede conectar a LDAP desde Laravel
   **Solución**: Asegurarse de que ambos contenedores están en la misma red Docker
   ```bash
   docker network connect docker_app-network openldap-osixia
   ```

3. **Problema**: Errores de autenticación a pesar de tener credenciales correctas
   **Solución**: Verificar que las variables de entorno en el contenedor Laravel coinciden con la configuración LDAP
   ```bash
   docker exec -it laravel-app php /var/www/html/update-env.php
   docker restart laravel-app
   ``` 