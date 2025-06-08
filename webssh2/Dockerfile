FROM node:18-alpine

RUN apk update && apk add bash openssh-client

WORKDIR /usr/src/app

# Copiar la clave SSH
COPY config/administrator-id_ed25519 /usr/src/app/config/administrator-id_ed25519

# Copiar el resto de la aplicación
COPY app/ /usr/src/app/

# Instalar dependencias
RUN npm ci --audit=false --bin-links=false --fund=false

# Configurar permisos
RUN chown -R root:root /usr/src && \
    chmod 600 /usr/src/app/config/administrator-id_ed25519

EXPOSE 2222/tcp
ENTRYPOINT [ "/usr/local/bin/node", "index.js" ]
