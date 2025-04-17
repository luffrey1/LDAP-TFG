FROM osixia/openldap:latest

# Configuración de entorno para LDAP
ENV LDAP_ORGANISATION="IES TIERNO GALVAN"
ENV LDAP_DOMAIN="test.tierno.es"
ENV LDAP_ADMIN_PASSWORD="admin"
ENV LDAP_CONFIG_PASSWORD="admin"
ENV LDAP_RFC2307BIS_SCHEMA="true"

# Crear directorio para archivos LDIF
RUN mkdir -p /container/service/slapd/assets/config/bootstrap/ldif/custom

# Copiar archivos LDIF al contenedor
COPY ldif-clean/*.ldif /container/service/slapd/assets/config/bootstrap/ldif/custom/

# Exponer puertos LDAP
EXPOSE 389 636

# Comando para iniciar el contenedor
CMD ["/container/tool/run"] 