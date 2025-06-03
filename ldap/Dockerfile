FROM osixia/openldap:1.5.0

# Copiar archivos de configuración
COPY ldif/01-config-password.ldif /container/service/slapd/assets/config/bootstrap/ldif/
COPY ldif/tls-enable.ldif /container/service/slapd/assets/config/tls/
COPY certs/cert-tierno.es /container/service/slapd/assets/certs/

# Asegurar permisos correctos
RUN chown -R openldap:openldap /container/service/slapd/assets/config/bootstrap/ldif/ \
    && chown -R openldap:openldap /container/service/slapd/assets/config/tls/ \
    && chown -R openldap:openldap /container/service/slapd/assets/certs/

# Exponer puertos LDAP y LDAPS
EXPOSE 389 636

# Usar el comando por defecto de la imagen
CMD ["/container/tool/run"] 