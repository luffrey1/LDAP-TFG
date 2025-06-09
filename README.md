# 📋 Documentación de Configuración

## 📌 Requisitos

- Ubuntu 20.04 o superior
- Docker y Docker Compose
- Git

## 🚀 Despliegue

1. Iniciar los contenedores:
```bash
cd docker
docker compose up -d
```

2. Acceder a la aplicación:
- Web: https://localhost
- SSH: localhost:2222

## 🔧 Comandos Manuales si no funciona APACHE

### Configuración de Apache
```bash
docker exec laravel-app cp /var/www/html/apache-config.conf /etc/apache2/sites-available/000-default.conf
docker exec laravel-app a2enmod ssl
docker exec laravel-app a2enmod rewrite
docker exec laravel-app service apache2 reload
```

### Permisos y Migraciones
```bash
# Establecer permisos
docker exec laravel-app chown -R www-data:www-data /var/www/html
docker exec laravel-app chmod -R 755 /var/www/html
docker exec laravel-app chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
docker exec laravel-app chmod -R 775 /var/www/html/public/documentos

# Ejecutar migraciones
docker exec laravel-app php artisan migrate --force

# Limpiar caché
docker exec laravel-app php artisan config:clear
docker exec laravel-app php artisan cache:clear
docker exec laravel-app php artisan route:clear
docker exec laravel-app php artisan view:clear
```

### Directorios de Documentos
```bash
docker exec laravel-app mkdir -p /var/www/html/public/documentos/{general,programaciones,actas,horarios}
docker exec laravel-app chmod -R 777 /var/www/html/public/documentos
docker exec laravel-app chown -R www-data:www-data /var/www/html/public/documentos
```

## ⚠️ Solución de Problemas Comunes

### 1. Error de Permisos
Si encuentras errores de permisos, ejecuta:
```bash
docker exec laravel-app chown -R www-data:www-data /var/www/html
docker exec laravel-app chmod -R 755 /var/www/html
docker exec laravel-app chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
```

### 2. Error en Migraciones
Si las migraciones fallan:
```bash
# Esperar a que MySQL esté listo
sleep 10
docker exec laravel-app php artisan migrate --force
```

### 3. Error de SSL en Apache
Si hay problemas con SSL:
```bash
docker exec laravel-app a2enmod ssl
docker exec laravel-app a2enmod rewrite
docker exec laravel-app service apache2 reload
```

### 4. Limpieza de Docker
Si hay problemas de espacio o contenedores huérfanos:
```bash
# Limpiar contenedores, redes e imágenes no utilizadas
docker system prune -f

# Limpiar volúmenes no utilizados
docker volume prune -f
```

### 5. Reinicio de Servicios
Si algún servicio no responde:
```bash
# Reiniciar todos los servicios
docker compose restart

# Reiniciar un servicio específico
docker compose restart laravel
```

## 📝 Notas Importantes

1. Siempre ejecutar los comandos desde el directorio `/docker`
2. Usar `docker compose` en lugar de `docker-compose`
3. Si se modifica el archivo `.env`, reiniciar los contenedores
4. Mantener los certificados SSL actualizados
5. Realizar copias de seguridad periódicas de la base de datos

## 🔄 Mantenimiento

### Backup de Base de Datos
```bash
docker exec laravel-mysql mysqldump -u root -p laravel > backup.sql
```

### Actualización de Certificados
```bash
# Copiar nuevos certificados
docker cp /ruta/certificados/cert.pem laravel-app:/etc/ssl/certs/site/certificate.crt
docker cp /ruta/certificados/privkey.pem laravel-app:/etc/ssl/certs/site/private.key
docker cp /ruta/certificados/chain.pem laravel-app:/etc/ssl/certs/site/ca_bundle.crt

# Recargar Apache
docker exec laravel-app service apache2 reload
```

---

<div align="center">
    <p>🚀 <b>Desarrollado con Laravel y Docker</b> 🚀</p>
</div>


