FROM webssh2

# Copiar los archivos de configuración
COPY app/config.json /usr/src/config.json
COPY config/administrator-id_ed25519 /usr/src/app/config/administrator-id_ed25519

# Configurar permisos
RUN chown -R root:root /usr/src && \
    chmod 600 /usr/src/app/config/administrator-id_ed25519 && \
    chmod 644 /usr/src/config.json

EXPOSE 2222/tcp
