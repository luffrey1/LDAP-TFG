FROM osixia/openldap:1.5.0

# Configuración de entorno para LDAP
ENV LDAP_ORGANISATION="IES TIERNO GALVAN"
ENV LDAP_DOMAIN="tierno.es"
ENV LDAP_ADMIN_PASSWORD="admin"
ENV LDAP_CONFIG_PASSWORD="admin"
ENV LDAP_RFC2307BIS_SCHEMA="true"
ENV LDAP_TLS_ENFORCE="true"
ENV LDAP_TLS_VERIFY_CLIENT="never"
ENV LDAP_TLS_CIPHER_SUITE="SECURE256:-VERS-SSL3.0"
ENV LDAP_TLS_PROTOCOL_MIN="3.1"
ENV LDAP_TLS_CRT_FILENAME="cert.pem"
ENV LDAP_TLS_KEY_FILENAME="privkey.pem"
ENV LDAP_TLS_CA_CRT_FILENAME="fullchain.pem"

# Crear directorios necesarios
RUN mkdir -p /container/service/slapd/assets/certs && \
    mkdir -p /container/service/slapd/assets/config/tls && \
    mkdir -p /container/service/slapd/assets/config/bootstrap/ldif/custom

# Copiar certificados
COPY certs/cert-tierno.es/cert.pem /container/service/slapd/assets/certs/
COPY certs/cert-tierno.es/privkey.pem /container/service/slapd/assets/certs/
COPY certs/cert-tierno.es/fullchain.pem /container/service/slapd/assets/certs/

# Establecer permisos correctos
RUN chown -R 911:911 /container/service/slapd/assets/certs && \
    chmod 600 /container/service/slapd/assets/certs/privkey.pem && \
    chmod 644 /container/service/slapd/assets/certs/cert.pem && \
    chmod 644 /container/service/slapd/assets/certs/fullchain.pem

# Crear script de inicialización personalizado
RUN echo '#!/bin/bash\n\
echo "Esperando a que el servidor LDAP esté listo..."\n\
sleep 20\n\
echo "Procesando archivos LDIF..."\n\
for file in /container/service/slapd/assets/config/bootstrap/ldif/custom/*.ldif; do\n\
    if [ -f "$file" ]; then\n\
        echo "Procesando $file..."\n\
        ldapadd -x -H ldaps://localhost:636 -D "cn=admin,dc=tierno,dc=es" -w admin -f "$file" -ZZ\n\
        if [ $? -eq 0 ]; then\n\
            echo "Archivo $file procesado correctamente"\n\
        else\n\
            echo "Error procesando $file"\n\
        fi\n\
    fi\n\
done\n\
echo "Procesamiento de LDIF completado."\n\
/container/tool/run' > /container/service/slapd/assets/init-ldif.sh && \
    chmod +x /container/service/slapd/assets/init-ldif.sh

# Exponer puertos LDAP
EXPOSE 389 636

# Usar nuestro script personalizado
CMD ["/container/service/slapd/assets/init-ldif.sh"] 