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

# Crear script de inicialización personalizado
RUN echo '#!/bin/bash\n\
echo "Esperando a que el servidor LDAP esté listo..."\n\
sleep 30\n\
echo "Procesando archivos LDIF..."\n\
LDIF_DIR="/container/service/slapd/assets/config/bootstrap/ldif/custom"\n\
for file in $(ls -v $LDIF_DIR/*.ldif); do\n\
    if [ -f "$file" ]; then\n\
        echo "Procesando $file..."\n\
        ldapadd -x -H ldap://localhost:389 -D "cn=admin,dc=tierno,dc=es" -w admin -f "$file"\n\
        if [ $? -eq 0 ]; then\n\
            echo "Archivo $file procesado correctamente"\n\
        else\n\
            echo "Error procesando $file"\n\
            sleep 5\n\
        fi\n\
    fi\n\
done\n\
echo "Procesamiento de LDIF completado."\n\
/container/tool/run' > /container/service/slapd/assets/init-ldif.sh && \
    chmod +x /container/service/slapd/assets/init-ldif.sh

# Exponer puertos LDAP y LDAPS
EXPOSE 389 636

# Usar el comando por defecto de la imagen
CMD ["/container/tool/run"] 