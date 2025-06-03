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
LDAP_PORT="636"  # Cambiado a 636 para LDAPS
echo "Esperando a que OpenLDAP esté disponible..."
until nc -z -v -w30 $LDAP_HOST $LDAP_PORT; do
  echo "Esperando conexión a OpenLDAP..."
  sleep 5
done
echo "OpenLDAP está disponible."

# Verificar conectividad de red
echo "Verificando red..."
ping -c 2 $LDAP_HOST || echo "No se puede hacer ping a LDAP, pero seguimos con la configuración"

# Verificar que existen los certificados SSL
echo "Verificando certificados SSL..."
if [ -f "/etc/ssl/certs/site/certificate.crt" ] && [ -f "/etc/ssl/certs/site/private.key" ]; then
  echo "Certificados SSL encontrados."
  
  # Verificar certificado intermedio
  if [ -f "/etc/ssl/certs/site/ca_bundle.crt" ]; then
    echo "Certificado intermedio encontrado."
  else
    echo "ADVERTENCIA: No se encontró certificado intermedio."
  fi
  
  # Copiar la configuración de Apache con SSL
  echo "Copiando configuración de Apache con SSL..."
  cp /var/www/html/apache-config.conf /etc/apache2/sites-available/000-default.conf
  
  # Configurar permisos de los certificados
  chmod 644 /etc/ssl/certs/site/certificate.crt
  chmod 644 /etc/ssl/certs/site/ca_bundle.crt
  chmod 600 /etc/ssl/certs/site/private.key
  
  echo "Configuración SSL completada para ldap.tierno.es"
else
  echo "ADVERTENCIA: No se encontraron certificados SSL. Usando configuración sin SSL."
  
  # Crear una configuración simple sin SSL
  cat > /etc/apache2/sites-available/000-default.conf << 'EOF'
<VirtualHost *:80>
    ServerAdmin webmaster@localhost
    ServerName tierno.es
    ServerAlias www.tierno.es
    DocumentRoot /var/www/html/public

    <Directory /var/www/html/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
EOF
fi

# Habilitar módulos de Apache necesarios para SSL
echo "Habilitando módulos de Apache para SSL..."
a2enmod ssl
a2enmod rewrite

# Asegurar que las variables de entorno en .env son correctas
echo "Actualizando variables de entorno en .env..."
sed -i "s/LDAP_HOST=.*/LDAP_HOST=$LDAP_HOST/" /var/www/html/.env
sed -i "s/LDAP_PORT=.*/LDAP_PORT=$LDAP_PORT/" /var/www/html/.env
sed -i "s/LDAP_BASE_DN=.*/LDAP_BASE_DN=dc=tierno,dc=es/" /var/www/html/.env
sed -i "s/LDAP_USERNAME=.*/LDAP_USERNAME=cn=admin,dc=tierno,dc=es/" /var/www/html/.env
sed -i "s/LDAP_PASSWORD=.*/LDAP_PASSWORD=admin/" /var/www/html/.env
sed -i "s/LDAP_AUTH_LOGIN_FALLBACK=.*/LDAP_AUTH_LOGIN_FALLBACK=false/" /var/www/html/.env

# Variables LdapRecord específicas
sed -i "s/LDAP_DEFAULT_HOSTS=.*/LDAP_DEFAULT_HOSTS=$LDAP_HOST/" /var/www/html/.env
sed -i "s/LDAP_DEFAULT_PORT=.*/LDAP_DEFAULT_PORT=$LDAP_PORT/" /var/www/html/.env
sed -i "s/LDAP_DEFAULT_BASE_DN=.*/LDAP_DEFAULT_BASE_DN=dc=tierno,dc=es/" /var/www/html/.env
sed -i "s/LDAP_DEFAULT_USERNAME=.*/LDAP_DEFAULT_USERNAME=cn=admin,dc=tierno,dc=es/" /var/www/html/.env
sed -i "s/LDAP_DEFAULT_SSL=.*/LDAP_DEFAULT_SSL=true/" /var/www/html/.env
sed -i "s/LDAP_DEFAULT_TLS=.*/LDAP_DEFAULT_TLS=true/" /var/www/html/.env

sed -i "s/DB_HOST=.*/DB_HOST=$DB_HOST/" /var/www/html/.env
sed -i "s/DB_PORT=.*/DB_PORT=$DB_PORT/" /var/www/html/.env
sed -i "s#APP_URL=.*#APP_URL=https://ldap.tierno.es#" /var/www/html/.env

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

# Crear usuario de prueba solo si no existe
php artisan tinker --execute="if(DB::table('users')->where('username', 'profesor')->count() == 0) { exit(1); }" || php artisan db:seed --class=UserSeeder

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
        'base_dn' => 'dc=tierno,dc=es',
        'username' => 'cn=admin,dc=tierno,dc=es',
        'password' => 'admin',
        'use_ssl' => true,
        'use_tls' => true,
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