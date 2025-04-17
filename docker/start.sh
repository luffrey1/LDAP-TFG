#!/bin/bash

# Mostrar información de configuración
echo "Iniciando configuración del contenedor..."

# Asegurarse de que la base de datos está disponible
echo "Esperando a que MySQL esté disponible..."
until nc -z -v -w30 mysql 3306; do
  echo "Esperando conexión a MySQL..."
  sleep 5
done
echo "MySQL está disponible."

# Esperar a que LDAP esté disponible
echo "Esperando a que LDAP esté disponible..."
until nc -z -v -w30 openldap-osixia 389; do
  echo "Esperando conexión a LDAP (openldap-osixia)..."
  sleep 5
done
echo "LDAP está disponible."

# Verificar conectividad de red
echo "Verificando red..."
ping -c 2 openldap-osixia || echo "No se puede hacer ping a LDAP, pero seguimos con la configuración"

# Asegurar que las variables de entorno en .env son correctas
echo "Actualizando variables de entorno en .env..."
sed -i "s/LDAP_HOST=.*/LDAP_HOST=openldap-osixia/" /var/www/html/.env
sed -i "s/LDAP_PORT=.*/LDAP_PORT=389/" /var/www/html/.env
sed -i "s/LDAP_BASE_DN=.*/LDAP_BASE_DN=dc=test,dc=tierno,dc=es/" /var/www/html/.env
sed -i "s/LDAP_USERNAME=.*/LDAP_USERNAME=cn=admin,dc=test,dc=tierno,dc=es/" /var/www/html/.env
sed -i "s/LDAP_PASSWORD=.*/LDAP_PASSWORD=admin/" /var/www/html/.env
sed -i "s/LDAP_AUTH_LOGIN_FALLBACK=.*/LDAP_AUTH_LOGIN_FALLBACK=false/" /var/www/html/.env

# Configuración de Laravel
cd /var/www/html

# Instalar dependencias
echo "Instalando dependencias de Composer..."
composer install --no-interaction --no-dev --optimize-autoloader

# Generar la clave de aplicación si no existe
if [ -z "$APP_KEY" ]; then
    echo "Generando clave de aplicación..."
    php artisan key:generate
fi

# Limpiar caché
echo "Limpiando caché..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Ejecutar migraciones
echo "Ejecutando migraciones..."
php artisan migrate --force

# Crear usuario de prueba si no existe
php artisan db:seed --class=UserSeeder

# Establecer permisos adecuados
echo "Estableciendo permisos..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Asegurar que los directorios de documentos existen y tienen permisos correctos
echo "Configurando directorios de documentos..."
mkdir -p /var/www/html/public/documentos/{general,programaciones,actas,horarios}
chmod -R 777 /var/www/html/public/documentos
chown -R www-data:www-data /var/www/html/public/documentos

# Verificar configuración LDAP
echo "Verificando configuración LDAP..."
php -r "
try {
    require __DIR__ . '/vendor/autoload.php';
    
    \$connection = new \LdapRecord\Connection([
        'hosts' => ['openldap-osixia'],
        'port' => 389,
        'base_dn' => 'dc=test,dc=tierno,dc=es',
        'username' => 'cn=admin,dc=test,dc=tierno,dc=es',
        'password' => 'admin',
    ]);
    \$connection->connect();
    echo 'Conexión LDAP exitosa!' . PHP_EOL;
} catch (Exception \$e) {
    echo 'Error de conexión LDAP: ' . \$e->getMessage() . PHP_EOL;
}
"

# Iniciar el servidor
echo "Iniciando servidor Apache..."
apache2-foreground 