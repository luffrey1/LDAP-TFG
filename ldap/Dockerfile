FROM osixia/openldap:latest

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
RUN mkdir -p /container/service/slapd/assets/config/tls

# Copiar archivos LDIF al contenedor
COPY ldif/*.ldif /tmp/

# Crear script de inicialización
RUN echo '#!/bin/bash\n\
# Esperar a que el servicio LDAP esté en funcionamiento\n\
sleep 5\n\
# Procesar archivos LDIF en orden\n\
for file in $(ls -v /tmp/*.ldif); do\n\
  echo "Procesando $file..."\n\
  ldapadd -x -D "cn=admin,dc=tierno,dc=es" -w admin -f $file || echo "Error procesando $file"\n\
done\n\
# Mantener el contenedor en ejecución\n\
exec "$@"' > /container/service/ldap-init.sh && \
chmod +x /container/service/ldap-init.sh

# Exponer puertos LDAP
EXPOSE 389 636

# Comando para iniciar el contenedor con el script de inicialización
ENTRYPOINT ["/container/service/ldap-init.sh"]
CMD ["/container/tool/run"] 