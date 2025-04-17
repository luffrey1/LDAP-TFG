#!/bin/bash

# Asegurarse de que la base de datos está disponible
echo "Esperando a que MySQL esté disponible..."
until nc -z -v -w30 mysql 3306; do
  echo "Esperando conexión a MySQL..."
  sleep 5
done
echo "MySQL está disponible."

# Esperar a que LDAP esté disponible
echo "Esperando a que LDAP esté disponible..."
until nc -z -v -w30 ldap 389; do
  echo "Esperando conexión a LDAP..."
  sleep 5
done
echo "LDAP está disponible."

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

# Iniciar el servidor
echo "Iniciando servidor Apache..."
exec apache2-foreground 