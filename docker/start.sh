#!/bin/bash

set -e

# Mostrar información de configuración
echo "Iniciando configuración del contenedor..."

# Esperar a que MySQL esté disponible usando el nombre de servicio
DB_HOST="mysql"
DB_PORT="3306"
echo "Esperando a que MySQL esté disponible..."
until nc -z -v -w30 $DB_HOST $DB_PORT; do
  echo "Esperando conexión a MySQL..."
  sleep 5
done
echo "MySQL está disponible."

# Esperar a que OpenLDAP esté disponible usando el nombre de servicio
LDAP_HOST="ldap"
LDAP_PORT="389"
echo "Esperando a que OpenLDAP esté disponible..."
until nc -z -v -w30 $LDAP_HOST $LDAP_PORT; do
  echo "Esperando conexión a OpenLDAP..."
  sleep 5
done
echo "OpenLDAP está disponible."

# Verificar conectividad de red
echo "Verificando red..."
ping -c 2 $LDAP_HOST || echo "No se puede hacer ping a LDAP, pero seguimos con la configuración"

# Asegurar que las variables de entorno en .env son correctas
echo "Actualizando variables de entorno en .env..."
sed -i "s/LDAP_HOST=.*/LDAP_HOST=$LDAP_HOST/" /var/www/html/.env
sed -i "s/LDAP_PORT=.*/LDAP_PORT=$LDAP_PORT/" /var/www/html/.env
sed -i "s/LDAP_BASE_DN=.*/LDAP_BASE_DN=dc=test,dc=tierno,dc=es/" /var/www/html/.env
sed -i "s/LDAP_USERNAME=.*/LDAP_USERNAME=cn=admin,dc=test,dc=tierno,dc=es/" /var/www/html/.env
sed -i "s/LDAP_PASSWORD=.*/LDAP_PASSWORD=admin/" /var/www/html/.env
sed -i "s/LDAP_AUTH_LOGIN_FALLBACK=.*/LDAP_AUTH_LOGIN_FALLBACK=false/" /var/www/html/.env
sed -i "s/DB_HOST=.*/DB_HOST=$DB_HOST/" /var/www/html/.env
sed -i "s/DB_PORT=.*/DB_PORT=$DB_PORT/" /var/www/html/.env

# Ejecutar comandos de inicialización de Laravel
cd /var/www/html

# Instalar dependencias de PHP si es necesario
if [ -f composer.json ]; then
  composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Instalar dependencias de Node.js y compilar assets si es necesario
if [ -f package.json ]; then
  npm install
  npm run build
fi

# Limpiar y cachear configuración de Laravel
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan config:cache || true
php artisan route:cache || true

# Migrar base de datos si es necesario
php artisan migrate --force || true

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
        'hosts' => ['$LDAP_HOST'],
        'port' => $LDAP_PORT,
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

# Iniciar Apache en primer plano
echo "Iniciando servidor Apache..."
apache2-foreground 