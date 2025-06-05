FROM node:18-alpine

RUN apk update && apk add bash openssh-client

WORKDIR /usr/src/app

# Copiar los archivos de configuración
COPY app/config.json /usr/src/app/config.json
COPY config/administrator-id_ed25519 /usr/src/app/config/administrator-id_ed25519

# Copiar el resto de la aplicación
COPY app/ /usr/src/app/

# Instalar dependencias
RUN npm ci --audit=false --bin-links=false --fund=false

# Configurar permisos
RUN chown -R root:root /usr/src && \
    chmod 600 /usr/src/app/config/administrator-id_ed25519 && \
    chmod 644 /usr/src/app/config.json

EXPOSE 2222/tcp
ENTRYPOINT [ "/usr/local/bin/node", "index.js" ]
