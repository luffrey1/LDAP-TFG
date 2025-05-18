FROM osixia/openldap:latest

# Configuración de entorno para LDAP
ENV LDAP_ORGANISATION="IES TIERNO GALVAN"
ENV LDAP_DOMAIN="test.tierno.es"
ENV LDAP_ADMIN_PASSWORD="admin"
ENV LDAP_CONFIG_PASSWORD="admin"
ENV LDAP_RFC2307BIS_SCHEMA="true"

# Copiar archivos LDIF al contenedor
COPY ldif/*.ldif /tmp/

# Crear script de inicialización
RUN echo '#!/bin/bash\n\
# Esperar a que el servicio LDAP esté en funcionamiento\n\
sleep 5\n\
# Procesar archivos LDIF en orden\n\
for file in $(ls -v /tmp/*.ldif); do\n\
  echo "Procesando $file..."\n\
  ldapadd -x -D "cn=admin,dc=test,dc=tierno,dc=es" -w admin -f $file || echo "Error procesando $file"\n\
done\n\
# Mantener el contenedor en ejecución\n\
exec "$@"' > /container/service/ldap-init.sh && \
chmod +x /container/service/ldap-init.sh

# Exponer puertos LDAP
EXPOSE 389 636

# Comando para iniciar el contenedor con el script de inicialización
ENTRYPOINT ["/container/service/ldap-init.sh"]
CMD ["/container/tool/run"] 