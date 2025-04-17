# 📋 Documentación de Configuración

![Laravel Logo](https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg)

## 📌 Requisitos

- 🐳 Docker y Docker Compose
- 🧰 PHP 8.0 o superior
- 💾 MySQL 8.0 o superior
- 🔐 Servidor LDAP (OpenLDAP)

## 🚀 Configuración Rápida

1. Clonar el repositorio
2. Ejecutar `docker-compose up -d`
3. Acceder a http://localhost:8000

---

## ⚠️ IMPORTANTE: Configuración del Entorno Docker

### 🔐 Servidor LDAP (OpenLDAP)

Para configurar el servidor LDAP de forma rápida, se proporcionan scripts de automatización:

> **Nota:** Es más seguro utilizar la configuración manual que se detalla más abajo.

**En Windows:**
```powershell
# Ejecutar el script de configuración LDAP para Windows
.\setup-ldap.ps1
```

**En Linux/macOS:**
```bash
# Ejecutar el script de configuración LDAP para sistemas Unix
chmod +x ./setup-ldap.sh
./setup-ldap.sh
```

**Configuración Manual (Recomendada):**

```bash
# Crear el contenedor de LDAP
docker-compose up -d openldap-osixia

# Importar los archivos LDIF al servidor LDAP
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f /tmp/01-ou.ldif
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f /tmp/02-ldap-admin-user.ldif
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f /tmp/03-ldapadmins-group.ldif
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f /tmp/04-everybody-group.ldif
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f /tmp/05-alumnos-users.ldif
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f /tmp/06-alumnos-groups.ldif
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f /tmp/07-profesor-users.ldif
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f /tmp/08-profesores-group.ldif
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f /tmp/09-docker-group.ldif
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f /tmp/10-lastUID-GID.ldif
docker exec -it openldap-osixia ldapadd -x -Y EXTERNAL -H ldapi:/// -f /tmp/11-sudo-schema.ldif
docker exec -it openldap-osixia ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f /tmp/12-sudo-profesores.ldif
docker exec -it openldap-osixia ldapmodify -x -Y EXTERNAL -H ldapi:/// -f /tmp/13-uniqueMember-index.ldif
docker exec -it openldap-osixia ldapmodify -x -Y EXTERNAL -H ldapi:/// -f /tmp/20_acl.ldif

# Verificar la configuración LDAP
docker exec -it openldap-osixia ldapsearch -x -b dc=test,dc=tierno,dc=es -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin
```

### 🌐 Configuración de la Red Docker

Es necesario asegurarse de que todos los contenedores estén en la misma red para que puedan comunicarse entre sí:

```bash
# Asegurarse de que todos los contenedores estén en la misma red
docker network connect docker_app-network openldap-osixia
```

### 🖥️ Servidor Apache

Para iniciar o reiniciar el servidor Apache en el contenedor de Laravel:

```bash
# Iniciar Apache
docker exec -it laravel-app service apache2 start

# Reiniciar Apache
docker exec -it laravel-app service apache2 restart

# Verificar el estado de Apache
docker exec -it laravel-app service apache2 status
```

### 📁 Directorios de Documentos

Configurar los directorios para los documentos en el contenedor Laravel:

```bash
# Crear directorios de documentos
docker exec -it laravel-app bash -c "mkdir -p /var/www/html/public/documentos/{general,programaciones,actas,horarios}"

# Asignar permisos a los directorios
docker exec -it laravel-app chmod -R 777 /var/www/html/public/documentos
```

---

## 🔑 Configuración LDAP con LdapRecord

Para asegurar una integración correcta con LDAP, la aplicación utiliza [LdapRecord](https://ldaprecord.com), una biblioteca moderna y robusta para trabajar con LDAP en Laravel.

### Variables de Entorno `.env`

Es importante configurar correctamente las variables de entorno con el prefijo `LDAP_DEFAULT_` para que LdapRecord funcione adecuadamente:

```env
# Variables con prefijo LDAP_DEFAULT recomendadas por LdapRecord
LDAP_DEFAULT_HOSTS=172.19.0.4
LDAP_DEFAULT_PORT=389
LDAP_DEFAULT_BASE_DN=dc=test,dc=tierno,dc=es
LDAP_DEFAULT_USERNAME=cn=admin,dc=test,dc=tierno,dc=es
LDAP_DEFAULT_PASSWORD=admin
LDAP_DEFAULT_SSL=false
LDAP_DEFAULT_TLS=false
LDAP_DEFAULT_TIMEOUT=5
```

### Configuración LdapRecord (config/ldap.php)

La aplicación utiliza el archivo `config/ldap.php` para definir las conexiones LDAP. Asegúrese de que esté correctamente configurado:

```php
'connections' => [
    'default' => [
        'hosts' => [env('LDAP_DEFAULT_HOSTS', env('LDAP_HOST', '172.19.0.4'))],
        'username' => env('LDAP_DEFAULT_USERNAME', env('LDAP_USERNAME', 'cn=admin,dc=test,dc=tierno,dc=es')),
        'password' => env('LDAP_DEFAULT_PASSWORD', env('LDAP_PASSWORD', 'admin')),
        'port' => env('LDAP_DEFAULT_PORT', env('LDAP_PORT', 389)),
        'base_dn' => env('LDAP_DEFAULT_BASE_DN', env('LDAP_BASE_DN', 'dc=test,dc=tierno,dc=es')),
        'timeout' => env('LDAP_DEFAULT_TIMEOUT', env('LDAP_TIMEOUT', 5)),
        'use_ssl' => env('LDAP_DEFAULT_SSL', env('LDAP_SSL', false)),
        'use_tls' => env('LDAP_DEFAULT_TLS', env('LDAP_TLS', false)),
        // ...
    ],
],
```

### 🧪 Probar Conexión LDAP

Para verificar que la configuración LDAP esté funcionando correctamente:

```bash
# Limpiar cachés
docker exec -it laravel-app php artisan config:clear
docker exec -it laravel-app php artisan cache:clear

# Probar conexión LDAP con LdapRecord
docker exec -it laravel-app php artisan ldap:test
```

---

## 📂 Estructura del Proyecto

```
proyecto/
├── app/                    # Código de la aplicación
│   ├── Console/            # Comandos de consola
│   ├── Exceptions/         # Manejadores de excepciones
│   ├── Http/               # Controladores, Middleware, Requests
│   │   ├── Controllers/    # Controladores de la aplicación
│   │   └── Middleware/     # Middleware
│   ├── Models/             # Modelos de la aplicación
│   └── Providers/          # Proveedores de servicios
├── bootstrap/              # Archivos de bootstrap
├── config/                 # Archivos de configuración
│   ├── app.php             # Configuración general
│   ├── auth.php            # Configuración de autenticación
│   ├── ldap.php            # Configuración de LDAP
│   └── ...
├── database/               # Migraciones y seeders
├── ldap/                   # Archivos de configuración LDAP
│   └── ldif/              # Archivos LDIF para OpenLDAP
├── public/                 # Archivos públicos
│   ├── documentos/         # Directorio para almacenar documentos físicos
│   └── ...
├── resources/              # Vistas, assets, etc.
│   ├── views/              # Vistas Blade
│   └── ...
├── routes/                 # Definición de rutas
│   ├── api.php             # Rutas API
│   └── web.php             # Rutas web
├── storage/                # Archivos generados por la aplicación
├── docker-compose.yml      # Configuración de Docker Compose
├── Dockerfile              # Configuración del contenedor Laravel
└── README.md              # Este archivo
```

## 👥 Usuarios LDAP

El sistema incluye los siguientes usuarios LDAP preconfigurados:

| Usuario | Contraseña | Rol |
|---------|------------|-----|
| `ldap-admin` | `admin` | Administrador |
| `profesor` | `password` | Profesor |
| `alumno` | `password` | Alumno |

## 🔄 Rutas de la Aplicación

La aplicación utiliza las siguientes rutas principales:

| Ruta | Descripción |
|------|-------------|
| `/dashboard` | Panel principal de la aplicación |
| `/gestion-documental` | Gestión de documentos (anteriormente en `/documentos`) |
| `/mensajes` | Sistema de mensajería interna |
| `/calendario` | Calendario de eventos |
| `/admin/usuarios` | Administración de usuarios LDAP (solo para administradores) |

## 🛠️ Scripts de Utilidad

Para facilitar la administración, depuración y configuración, se incluyen varios scripts:

| Script | Descripción |
|--------|-------------|
| `test-ldap.php` | Prueba la conexión al servidor LDAP |
| `check-apache.php` | Verifica la configuración de Apache |
| `ldap_admin_check.php` | Comprueba los permisos del usuario administrador LDAP |
| `update-env.php` | Actualiza las variables de entorno en el archivo .env |

---

## ⚠️ Problemas Conocidos y Soluciones

### Variables de Entorno en Docker

Las variables de entorno dentro de los contenedores pueden no coincidir con las definidas en el archivo `.env`. Para solucionarlo, se recomienda lo siguiente en el Dockerfile:

```dockerfile
# Actualizar el archivo .env dentro del contenedor
COPY .env /var/www/html/.env
RUN chmod 644 /var/www/html/.env

# Asegurar que las variables de entorno estén correctamente configuradas
ENV LDAP_HOST=openldap-osixia
ENV LDAP_PORT=389
ENV LDAP_BASE_DN=dc=test,dc=tierno,dc=es
ENV LDAP_USERNAME=cn=admin,dc=test,dc=tierno,dc=es
ENV LDAP_PASSWORD=admin

# Variables LdapRecord
ENV LDAP_DEFAULT_HOSTS=172.19.0.4
ENV LDAP_DEFAULT_PORT=389
ENV LDAP_DEFAULT_BASE_DN=dc=test,dc=tierno,dc=es
ENV LDAP_DEFAULT_USERNAME=cn=admin,dc=test,dc=tierno,dc=es
ENV LDAP_DEFAULT_PASSWORD=admin
```

### 🧹 Limpieza de Caché

Si modifica configuraciones o rutas, es recomendable limpiar las cachés de Laravel:

```bash
docker exec -it laravel-app php artisan cache:clear
docker exec -it laravel-app php artisan config:clear
docker exec -it laravel-app php artisan route:clear
docker exec -it laravel-app php artisan view:clear
```

---

<div align="center">
    <p>🚀 <b>Desarrollado con Laravel, Docker y LdapRecord</b> 🚀</p>
    <p>Para cualquier consulta o problema, por favor abra un <i>issue</i> en el repositorio.</p>
</div>


