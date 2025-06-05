FROM node:16-alpine

RUN apk update && apk add bash openssh-client

WORKDIR /usr/src

# Crear directorios necesarios
RUN mkdir -p /usr/src/app/config

# Copiar los archivos de configuración
COPY app/config.json /usr/src/config.json
COPY app/config/administrator-id_ed25519 /usr/src/app/config/administrator-id_ed25519

# Copiar el resto de la aplicación
COPY app/ /usr/src/
RUN npm ci --audit=false --bin-links=false --fund=false

# Configurar permisos
RUN chown -R node:node /usr/src/app/config && \
    chmod 600 /usr/src/app/config/administrator-id_ed25519 && \
    chmod 644 /usr/src/config.json

# Cambiar al usuario node
USER node

EXPOSE 2222/tcp
ENTRYPOINT [ "/usr/local/bin/node", "index.js" ]
