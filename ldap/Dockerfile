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

# Crear directorio para certificados TLS
RUN mkdir -p /container/service/slapd/assets/certs

# Copiar certificados
COPY certs/cert-tierno.es/cert.pem /container/service/slapd/assets/certs/
COPY certs/cert-tierno.es/privkey.pem /container/service/slapd/assets/certs/
COPY certs/cert-tierno.es/fullchain.pem /container/service/slapd/assets/certs/

# Crear directorio para archivos LDIF
RUN mkdir -p /container/service/slapd/assets/config/bootstrap/ldif/custom

# Copiar archivos LDIF personalizados
COPY ldif/ /container/service/slapd/assets/config/bootstrap/ldif/custom/

# Establecer permisos correctos
RUN chown -R 911:911 /container/service/slapd/assets/certs && \
    chmod 600 /container/service/slapd/assets/certs/privkey.pem && \
    chmod 644 /container/service/slapd/assets/certs/cert.pem && \
    chmod 644 /container/service/slapd/assets/certs/fullchain.pem

# Establecer permisos para los archivos LDIF
RUN chown -R 911:911 /container/service/slapd/assets/config/bootstrap/ldif/custom

# Exponer puertos LDAP
EXPOSE 389 636

# Usar el comando por defecto de la imagen
CMD ["/container/tool/run"] 