FROM php:8.2-apache

# Instala dependencias necesarias
RUN apt-get update && apt-get install -y \
    libldap2-dev \
    ldap-utils \
    zip \
    unzip \
    libzip-dev \
    libonig-dev \
    libpng-dev \
    curl \
    git \
    libxml2-dev \
    netcat-openbsd \
    iputils-ping \
    iproute2 \
    net-tools \
    arp-scan \
    nmap \
    sudo \
    libssl-dev \
    && docker-php-ext-install ldap zip pdo pdo_mysql

# Instalar soporte ECDSA para Apache
RUN a2enmod ssl && \
    echo "SSLProtocol all -SSLv2 -SSLv3 -TLSv1 -TLSv1.1" >> /etc/apache2/conf-available/ssl-params.conf && \
    echo "SSLCipherSuite ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-ECDSA-AES128-SHA256:ECDHE-ECDSA-AES256-SHA384" >> /etc/apache2/conf-available/ssl-params.conf && \
    a2enconf ssl-params

# Instalar extensión pcntl para Laravel Reverb (Websockets)
RUN docker-php-ext-configure pcntl --enable-pcntl \
    && docker-php-ext-install pcntl

# Crear directorio para certificados SSL
RUN mkdir -p /etc/ssl/certs/site
RUN mkdir -p /etc/ssl/certs/ldap
RUN mkdir -p /etc/ldap

# Habilitar módulos de Apache para SSL
RUN a2enmod ssl

# Cambia el root de Apache a /public de Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# Configurar ServerName para evitar advertencias de Apache
RUN echo "ServerName laravel-app" >> /etc/apache2/apache2.conf

# Habilitar el módulo rewrite de Apache
RUN a2enmod rewrite

# Establecer variables de entorno para LDAP
ENV LDAP_HOST=openldap-osixia
ENV LDAP_PORT=636
ENV LDAP_BASE_DN=dc=tierno,dc=es
ENV LDAP_USERNAME=cn=admin,dc=tierno,dc=es
ENV LDAP_PASSWORD=admin
ENV LDAP_SSL=true
ENV LDAP_TLS=true
ENV LDAP_TIMEOUT=5
ENV LDAP_LOGGING=true
ENV LDAP_CACHE=false
ENV LDAP_AUTH_USERNAME_RULE=uid
ENV LDAP_AUTH_USERNAME_SCOPE=uid
ENV LDAP_AUTH_LOGIN_FALLBACK=false
ENV LDAP_DEBUG=true

# Establecer variables de entorno para Laravel Reverb (WebSockets)
ENV REVERB_APP_ID=proyectoDAreverb
ENV REVERB_APP_KEY=proyectoDArevkey
ENV REVERB_APP_SECRET=proyectoDAreverbsecret
ENV REVERB_HOST=0.0.0.0
ENV REVERB_PORT=8080
ENV REVERB_SCHEME=https
ENV BROADCAST_DRIVER=reverb

# Copiar el script de inicio
COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Copiar configuración de Apache
COPY docker/apache-config.conf /var/www/html/apache-config.conf

# Copia tu proyecto Laravel (ajusta si el path es otro)
WORKDIR /var/www/html
COPY proyecto/ .

# Asegurar que el archivo .env tiene las variables correctas
# Esta operación se realizará en el script de inicio para tomar la configuración más reciente

# Instala Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Instala dependencias de Laravel (y evita errores en container)
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Instalar Laravel Reverb
RUN composer require laravel/reverb
RUN php artisan vendor:publish --tag=reverb-config

RUN composer dump-autoload
RUN php artisan package:discover --ansi || true
RUN php artisan config:cache || true
RUN php artisan route:cache || true

# Crear directorios para documentos y establecer permisos
RUN mkdir -p /var/www/html/public/documentos/{general,programaciones,actas,horarios}
RUN chmod -R 777 /var/www/html/public/documentos
RUN chmod -R 777 storage bootstrap/cache

# Copiar archivos de acceso restringido a los directorios de documentos SI NO FUNCIONA ELIMINAR Y MANUAL
COPY proyecto/public/documentos/index.html /var/www/html/public/documentos/index.html
COPY proyecto/public/documentos/index.html /var/www/html/public/documentos/general/index.html
COPY proyecto/public/documentos/index.html /var/www/html/public/documentos/programaciones/index.html
COPY proyecto/public/documentos/index.html /var/www/html/public/documentos/actas/index.html
COPY proyecto/public/documentos/index.html /var/www/html/public/documentos/horarios/index.html

# Asegurar que el .htaccess de documentos exista
COPY proyecto/public/documentos/.htaccess /var/www/html/public/documentos/.htaccess

# Instala Node.js y npm
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - && \
    apt-get install -y nodejs

# Instala dependencias de frontend y compila assets
WORKDIR /var/www/html
RUN npm install && npm run build

# Puertos
EXPOSE 80
EXPOSE 443

# Comando para iniciar el contenedor
CMD ["/usr/local/bin/start.sh"]