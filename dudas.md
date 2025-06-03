root@proyecto-ldap:~/proyectoDA/proyecto/storage/logs# docker logs openldap-osixia | grep -i "slapd starting"
***  INFO   | 2025-06-03 18:06:53 | CONTAINER_LOG_LEVEL = 4 (debug)
***  INFO   | 2025-06-03 18:06:53 | Search service in CONTAINER_SERVICE_DIR = /container/service :
***  INFO   | 2025-06-03 18:06:53 | link /container/service/:ssl-tools/startup.sh to /container/run/startup/:ssl-tools
***  INFO   | 2025-06-03 18:06:53 | link /container/service/slapd/startup.sh to /container/run/startup/slapd
***  INFO   | 2025-06-03 18:06:53 | link /container/service/slapd/process.sh to /container/run/process/slapd/run
***  DEBUG  | 2025-06-03 18:06:53 | Set environment for startup files
***  DEBUG  | 2025-06-03 18:06:53 | ignore : LANG = en_US.UTF-8 (keep LANG = en_US.UTF-8 )
***  DEBUG  | 2025-06-03 18:06:53 | ignore : LANGUAGE = en_US.UTF-8 (keep LANGUAGE = en_US:en )
***  INFO   | 2025-06-03 18:06:53 | Environment files will be proccessed in this order :
Caution: previously defined variables will not be overriden.
/container/environment/99-default/default.startup.yaml
/container/environment/99-default/default.yaml

***  DEBUG  | 2025-06-03 18:06:53 | process environment file : /container/environment/99-default/default.startup.yaml
***  DEBUG  | 2025-06-03 18:06:53 | ignore : LDAP_ORGANISATION = Example Inc. (keep LDAP_ORGANISATION = IES TIERNO GALVAN )
***  DEBUG  | 2025-06-03 18:06:53 | ignore : LDAP_DOMAIN = example.org (keep LDAP_DOMAIN = tierno.es )
***  DEBUG  | 2025-06-03 18:06:53 | ignore : LDAP_ADMIN_PASSWORD = admin (keep LDAP_ADMIN_PASSWORD = admin )
***  DEBUG  | 2025-06-03 18:06:53 | ignore : LDAP_CONFIG_PASSWORD = config (keep LDAP_CONFIG_PASSWORD = admin )
***  DEBUG  | 2025-06-03 18:06:53 | ignore : LDAP_READONLY_USER = False (keep LDAP_READONLY_USER = false )
***  DEBUG  | 2025-06-03 18:06:53 | ignore : LDAP_RFC2307BIS_SCHEMA = False (keep LDAP_RFC2307BIS_SCHEMA = true )
***  DEBUG  | 2025-06-03 18:06:53 | ignore : LDAP_TLS = True (keep LDAP_TLS = true )
***  DEBUG  | 2025-06-03 18:06:53 | ignore : LDAP_TLS_CRT_FILENAME = ldap.crt (keep LDAP_TLS_CRT_FILENAME = cert.pem )
***  DEBUG  | 2025-06-03 18:06:53 | ignore : LDAP_TLS_KEY_FILENAME = ldap.key (keep LDAP_TLS_KEY_FILENAME = privkey.pem )
***  DEBUG  | 2025-06-03 18:06:53 | ignore : LDAP_TLS_CA_CRT_FILENAME = ca.crt (keep LDAP_TLS_CA_CRT_FILENAME = chain.pem )
***  DEBUG  | 2025-06-03 18:06:53 | ignore : LDAP_TLS_ENFORCE = False (keep LDAP_TLS_ENFORCE = false )
***  DEBUG  | 2025-06-03 18:06:53 | ignore : LDAP_TLS_CIPHER_SUITE = SECURE256:+SECURE128:-VERS-TLS-ALL:+VERS-TLS1.2:-RSA:-DHE-DSS:-CAMELLIA-128-CBC:-CAMELLIA-256-CBC (keep LDAP_TLS_CIPHER_SUITE = SECURE256:-VERS-SSL3.0 )
***  DEBUG  | 2025-06-03 18:06:53 | ignore : LDAP_TLS_VERIFY_CLIENT = demand (keep LDAP_TLS_VERIFY_CLIENT = never )
***  DEBUG  | 2025-06-03 18:06:53 | ignore : LDAP_REMOVE_CONFIG_AFTER_SETUP = True (keep LDAP_REMOVE_CONFIG_AFTER_SETUP = false )
***  DEBUG  | 2025-06-03 18:06:53 | process environment file : /container/environment/99-default/default.yaml
***  DEBUG  | 2025-06-03 18:06:53 | Run commands before startup...
***  INFO   | 2025-06-03 18:06:53 | Running /container/run/startup/:ssl-tools...
***  DEBUG  | 2025-06-03 18:06:53 | ------------ Environment dump ------------
***  DEBUG  | 2025-06-03 18:06:53 | PATH = /usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
***  DEBUG  | 2025-06-03 18:06:53 | HOSTNAME = 337aed640cd8
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_TLS = true
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_TLS_VERIFY_CLIENT = never
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_TLS_KEY_FILENAME = privkey.pem
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_BOOTSTRAP = true
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_ORGANISATION = IES TIERNO GALVAN
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_TLS_ENFORCE = false
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_READONLY_USER = false
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_CONFIG_PASSWORD = admin
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_TLS_CIPHER_SUITE = SECURE256:-VERS-SSL3.0
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_ADMIN_PASSWORD = admin
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_TLS_CA_CRT_FILENAME = chain.pem
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_REMOVE_CONFIG_AFTER_SETUP = false
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_BOOTSTRAP_LDIF_ORDER = 01-config-password.ldif,01-ou.ldif,02-ldap-admin-user.ldif,20_acl.ldif,03-ldapadmins-group.ldif,04-everybody-group.ldif,05-alumnos-users.ldif,06-alumnos-groups.ldif,07-profesor-users.ldif,08-profesores-group.ldif,09-docker-group.ldif,10-lastUID-GID.ldif,11-sudo-schema.ldif,12-sudo-profesores.ldif,13-uniqueMember-index.ldif
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_FORCE_BOOTSTRAP = true
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_RFC2307BIS_SCHEMA = true
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_TLS_PROTOCOL_MIN = 3.1
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_DOMAIN = tierno.es
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_TLS_CRT_FILENAME = cert.pem
***  DEBUG  | 2025-06-03 18:06:53 | LANG = en_US.UTF-8
***  DEBUG  | 2025-06-03 18:06:53 | LANGUAGE = en_US:en
***  DEBUG  | 2025-06-03 18:06:53 | LC_ALL = en_US.UTF-8
***  DEBUG  | 2025-06-03 18:06:53 | HOME = /root
***  DEBUG  | 2025-06-03 18:06:53 | CONTAINER_SERVICE_DIR = /container/service
***  DEBUG  | 2025-06-03 18:06:53 | CONTAINER_STATE_DIR = /container/run/state
***  DEBUG  | 2025-06-03 18:06:53 | CONTAINER_LOG_LEVEL = 4
***  DEBUG  | 2025-06-03 18:06:53 | INITRD = no
***  DEBUG  | 2025-06-03 18:06:53 | LC_CTYPE = en_US.UTF-8
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_BASE_DN =
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_READONLY_USER_USERNAME = readonly
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_READONLY_USER_PASSWORD = readonly
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_BACKEND = mdb
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_TLS_DH_PARAM_FILENAME = dhparam.pem
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_REPLICATION = False
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_REPLICATION_CONFIG_SYNCPROV = binddn="cn=admin,cn=config" bindmethod=simple credentials="$LDAP_CONFIG_PASSWORD" searchbase="cn=config" type=refreshAndPersist retry="60 +" timeout=1 starttls=critical
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_REPLICATION_DB_SYNCPROV = binddn="cn=admin,$LDAP_BASE_DN" bindmethod=simple credentials="$LDAP_ADMIN_PASSWORD" searchbase="$LDAP_BASE_DN" type=refreshAndPersist interval=00:00:00:10 retry="60 +" timeout=1 starttls=critical
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_REPLICATION_HOSTS = #COMPLEX_BASH_ENV:TABLE: LDAP_REPLICATION_HOSTS_ROW_1 LDAP_REPLICATION_HOSTS_ROW_2
***  DEBUG  | 2025-06-03 18:06:53 | KEEP_EXISTING_CONFIG = False
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_SSL_HELPER_PREFIX = ldap
***  DEBUG  | 2025-06-03 18:06:53 | SSL_HELPER_AUTO_RENEW_SERVICES_IMPACTED = slapd
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_SEED_INTERNAL_LDAP_TLS_CRT_FILE =
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_SEED_INTERNAL_LDAP_TLS_KEY_FILE =
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_SEED_INTERNAL_LDAP_TLS_CA_CRT_FILE =
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_SEED_INTERNAL_LDAP_TLS_DH_PARAM_FILE =
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_SEED_INTERNAL_LDIF_PATH =
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_SEED_INTERNAL_SCHEMA_PATH =
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_LOG_LEVEL = 256
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_NOFILE = 1024
***  DEBUG  | 2025-06-03 18:06:53 | DISABLE_CHOWN = False
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_PORT = 389
***  DEBUG  | 2025-06-03 18:06:53 | LDAPS_PORT = 636
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_REPLICATION_HOSTS_ROW_1 = ldap://ldap.example.org
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_REPLICATION_HOSTS_ROW_2 = ldap://ldap2.example.org
***  DEBUG  | 2025-06-03 18:06:53 | ------------------------------------------
***  INFO   | 2025-06-03 18:06:53 | Running /container/run/startup/slapd...
***  DEBUG  | 2025-06-03 18:06:53 | ------------ Environment dump ------------
***  DEBUG  | 2025-06-03 18:06:53 | CONTAINER_LOG_LEVEL = 4
***  DEBUG  | 2025-06-03 18:06:53 | CONTAINER_SERVICE_DIR = /container/service
***  DEBUG  | 2025-06-03 18:06:53 | CONTAINER_STATE_DIR = /container/run/state
***  DEBUG  | 2025-06-03 18:06:53 | DISABLE_CHOWN = False
***  DEBUG  | 2025-06-03 18:06:53 | HOME = /root
***  DEBUG  | 2025-06-03 18:06:53 | HOSTNAME = 337aed640cd8
***  DEBUG  | 2025-06-03 18:06:53 | INITRD = no
***  DEBUG  | 2025-06-03 18:06:53 | KEEP_EXISTING_CONFIG = False
***  DEBUG  | 2025-06-03 18:06:53 | LANG = en_US.UTF-8
***  DEBUG  | 2025-06-03 18:06:53 | LANGUAGE = en_US:en
***  DEBUG  | 2025-06-03 18:06:53 | LC_ALL = en_US.UTF-8
***  DEBUG  | 2025-06-03 18:06:53 | LC_CTYPE = en_US.UTF-8
***  DEBUG  | 2025-06-03 18:06:53 | LDAPS_PORT = 636
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_ADMIN_PASSWORD = admin
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_BACKEND = mdb
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_BASE_DN =
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_BOOTSTRAP = true
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_BOOTSTRAP_LDIF_ORDER = 01-config-password.ldif,01-ou.ldif,02-ldap-admin-user.ldif,20_acl.ldif,03-ldapadmins-group.ldif,04-everybody-group.ldif,05-alumnos-users.ldif,06-alumnos-groups.ldif,07-profesor-users.ldif,08-profesores-group.ldif,09-docker-group.ldif,10-lastUID-GID.ldif,11-sudo-schema.ldif,12-sudo-profesores.ldif,13-uniqueMember-index.ldif
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_CONFIG_PASSWORD = admin
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_DOMAIN = tierno.es
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_FORCE_BOOTSTRAP = true
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_LOG_LEVEL = 256
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_NOFILE = 1024
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_ORGANISATION = IES TIERNO GALVAN
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_PORT = 389
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_READONLY_USER = false
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_READONLY_USER_PASSWORD = readonly
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_READONLY_USER_USERNAME = readonly
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_REMOVE_CONFIG_AFTER_SETUP = false
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_REPLICATION = False
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_REPLICATION_CONFIG_SYNCPROV = binddn="cn=admin,cn=config" bindmethod=simple credentials="$LDAP_CONFIG_PASSWORD" searchbase="cn=config" type=refreshAndPersist retry="60 +" timeout=1 starttls=critical
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_REPLICATION_DB_SYNCPROV = binddn="cn=admin,$LDAP_BASE_DN" bindmethod=simple credentials="$LDAP_ADMIN_PASSWORD" searchbase="$LDAP_BASE_DN" type=refreshAndPersist interval=00:00:00:10 retry="60 +" timeout=1 starttls=critical
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_REPLICATION_HOSTS = #COMPLEX_BASH_ENV:TABLE: LDAP_REPLICATION_HOSTS_ROW_1 LDAP_REPLICATION_HOSTS_ROW_2
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_REPLICATION_HOSTS_ROW_1 = ldap://ldap.example.org
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_REPLICATION_HOSTS_ROW_2 = ldap://ldap2.example.org
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_RFC2307BIS_SCHEMA = true
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_SEED_INTERNAL_LDAP_TLS_CA_CRT_FILE =
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_SEED_INTERNAL_LDAP_TLS_CRT_FILE =
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_SEED_INTERNAL_LDAP_TLS_DH_PARAM_FILE =
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_SEED_INTERNAL_LDAP_TLS_KEY_FILE =
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_SEED_INTERNAL_LDIF_PATH =
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_SEED_INTERNAL_SCHEMA_PATH =
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_SSL_HELPER_PREFIX = ldap
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_TLS = true
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_TLS_CA_CRT_FILENAME = chain.pem
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_TLS_CIPHER_SUITE = SECURE256:-VERS-SSL3.0
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_TLS_CRT_FILENAME = cert.pem
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_TLS_DH_PARAM_FILENAME = dhparam.pem
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_TLS_ENFORCE = false
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_TLS_KEY_FILENAME = privkey.pem
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_TLS_PROTOCOL_MIN = 3.1
***  DEBUG  | 2025-06-03 18:06:53 | LDAP_TLS_VERIFY_CLIENT = never
***  DEBUG  | 2025-06-03 18:06:53 | PATH = /usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
***  DEBUG  | 2025-06-03 18:06:53 | SSL_HELPER_AUTO_RENEW_SERVICES_IMPACTED = slapd
***  DEBUG  | 2025-06-03 18:06:53 | ------------------------------------------
  Backing up /etc/ldap/slapd.d in /var/backups/slapd-2.4.57+dfsg-1~bpo10+1... done.
  Creating initial configuration... done.
  Creating LDAP directory... done.
invoke-rc.d: could not determine current runlevel
invoke-rc.d: policy-rc.d denied execution of restart.
config file testing succeeded
config file testing succeeded
683f39be slapd starting
***  ERROR  | 2025-06-03 18:06:54 | /container/run/startup/slapd failed with status 50

***  DEBUG  | 2025-06-03 18:06:54 | Run commands before finish...
***  INFO   | 2025-06-03 18:06:54 | Killing all processes...
***  INFO   | 2025-06-03 18:06:55 | CONTAINER_LOG_LEVEL = 4 (debug)
***  INFO   | 2025-06-03 18:06:55 | Search service in CONTAINER_SERVICE_DIR = /container/service :
***  INFO   | 2025-06-03 18:06:55 | link /container/service/:ssl-tools/startup.sh to /container/run/startup/:ssl-tools
*** WARNING | 2025-06-03 18:06:55 | failed to link /container/service/:ssl-tools/startup.sh to /container/run/startup/:ssl-tools: [Errno 17] File exists: '/container/service/:ssl-tools/startup.sh' -> '/container/run/startup/:ssl-tools'
***  INFO   | 2025-06-03 18:06:55 | link /container/service/slapd/startup.sh to /container/run/startup/slapd
*** WARNING | 2025-06-03 18:06:55 | failed to link /container/service/slapd/startup.sh to /container/run/startup/slapd: [Errno 17] File exists: '/container/service/slapd/startup.sh' -> '/container/run/startup/slapd'
***  INFO   | 2025-06-03 18:06:55 | link /container/service/slapd/process.sh to /container/run/process/slapd/run
*** WARNING | 2025-06-03 18:06:55 | directory /container/run/process/slapd already exists
*** WARNING | 2025-06-03 18:06:55 | failed to link /container/service/slapd/process.sh to /container/run/process/slapd/run : [Errno 17] File exists: '/container/service/slapd/process.sh' -> '/container/run/process/slapd/run'
***  DEBUG  | 2025-06-03 18:06:55 | Set environment for startup files
***  DEBUG  | 2025-06-03 18:06:55 | ignore : LANG = en_US.UTF-8 (keep LANG = en_US.UTF-8 )
***  DEBUG  | 2025-06-03 18:06:55 | ignore : LANGUAGE = en_US.UTF-8 (keep LANGUAGE = en_US:en )
***  INFO   | 2025-06-03 18:06:55 | Environment files will be proccessed in this order :
Caution: previously defined variables will not be overriden.
/container/environment/99-default/default.startup.yaml
/container/environment/99-default/default.yaml

***  DEBUG  | 2025-06-03 18:06:55 | process environment file : /container/environment/99-default/default.startup.yaml
***  DEBUG  | 2025-06-03 18:06:55 | ignore : LDAP_ORGANISATION = Example Inc. (keep LDAP_ORGANISATION = IES TIERNO GALVAN )
***  DEBUG  | 2025-06-03 18:06:55 | ignore : LDAP_DOMAIN = example.org (keep LDAP_DOMAIN = tierno.es )
***  DEBUG  | 2025-06-03 18:06:55 | ignore : LDAP_ADMIN_PASSWORD = admin (keep LDAP_ADMIN_PASSWORD = admin )
***  DEBUG  | 2025-06-03 18:06:55 | ignore : LDAP_CONFIG_PASSWORD = config (keep LDAP_CONFIG_PASSWORD = admin )
***  DEBUG  | 2025-06-03 18:06:55 | ignore : LDAP_READONLY_USER = False (keep LDAP_READONLY_USER = false )
***  DEBUG  | 2025-06-03 18:06:55 | ignore : LDAP_RFC2307BIS_SCHEMA = False (keep LDAP_RFC2307BIS_SCHEMA = true )
***  DEBUG  | 2025-06-03 18:06:55 | ignore : LDAP_TLS = True (keep LDAP_TLS = true )
***  DEBUG  | 2025-06-03 18:06:55 | ignore : LDAP_TLS_CRT_FILENAME = ldap.crt (keep LDAP_TLS_CRT_FILENAME = cert.pem )
***  DEBUG  | 2025-06-03 18:06:55 | ignore : LDAP_TLS_KEY_FILENAME = ldap.key (keep LDAP_TLS_KEY_FILENAME = privkey.pem )
***  DEBUG  | 2025-06-03 18:06:55 | ignore : LDAP_TLS_CA_CRT_FILENAME = ca.crt (keep LDAP_TLS_CA_CRT_FILENAME = chain.pem )
***  DEBUG  | 2025-06-03 18:06:55 | ignore : LDAP_TLS_ENFORCE = False (keep LDAP_TLS_ENFORCE = false )
***  DEBUG  | 2025-06-03 18:06:55 | ignore : LDAP_TLS_CIPHER_SUITE = SECURE256:+SECURE128:-VERS-TLS-ALL:+VERS-TLS1.2:-RSA:-DHE-DSS:-CAMELLIA-128-CBC:-CAMELLIA-256-CBC (keep LDAP_TLS_CIPHER_SUITE = SECURE256:-VERS-SSL3.0 )
***  DEBUG  | 2025-06-03 18:06:55 | ignore : LDAP_TLS_VERIFY_CLIENT = demand (keep LDAP_TLS_VERIFY_CLIENT = never )
***  DEBUG  | 2025-06-03 18:06:55 | ignore : LDAP_REMOVE_CONFIG_AFTER_SETUP = True (keep LDAP_REMOVE_CONFIG_AFTER_SETUP = false )
***  DEBUG  | 2025-06-03 18:06:55 | process environment file : /container/environment/99-default/default.yaml
***  DEBUG  | 2025-06-03 18:06:55 | Run commands before startup...
***  INFO   | 2025-06-03 18:06:55 | Running /container/run/startup/:ssl-tools...
***  DEBUG  | 2025-06-03 18:06:55 | ------------ Environment dump ------------
***  DEBUG  | 2025-06-03 18:06:55 | PATH = /usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin
***  DEBUG  | 2025-06-03 18:06:55 | HOSTNAME = 337aed640cd8
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_TLS = true
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_TLS_VERIFY_CLIENT = never
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_TLS_KEY_FILENAME = privkey.pem
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_BOOTSTRAP = true
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_ORGANISATION = IES TIERNO GALVAN
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_TLS_ENFORCE = false
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_READONLY_USER = false
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_CONFIG_PASSWORD = admin
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_TLS_CIPHER_SUITE = SECURE256:-VERS-SSL3.0
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_ADMIN_PASSWORD = admin
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_TLS_CA_CRT_FILENAME = chain.pem
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_REMOVE_CONFIG_AFTER_SETUP = false
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_BOOTSTRAP_LDIF_ORDER = 01-config-password.ldif,01-ou.ldif,02-ldap-admin-user.ldif,20_acl.ldif,03-ldapadmins-group.ldif,04-everybody-group.ldif,05-alumnos-users.ldif,06-alumnos-groups.ldif,07-profesor-users.ldif,08-profesores-group.ldif,09-docker-group.ldif,10-lastUID-GID.ldif,11-sudo-schema.ldif,12-sudo-profesores.ldif,13-uniqueMember-index.ldif
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_FORCE_BOOTSTRAP = true
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_RFC2307BIS_SCHEMA = true
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_TLS_PROTOCOL_MIN = 3.1
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_DOMAIN = tierno.es
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_TLS_CRT_FILENAME = cert.pem
***  DEBUG  | 2025-06-03 18:06:55 | LANG = en_US.UTF-8
***  DEBUG  | 2025-06-03 18:06:55 | LANGUAGE = en_US:en
***  DEBUG  | 2025-06-03 18:06:55 | LC_ALL = en_US.UTF-8
***  DEBUG  | 2025-06-03 18:06:55 | HOME = /root
***  DEBUG  | 2025-06-03 18:06:55 | CONTAINER_SERVICE_DIR = /container/service
***  DEBUG  | 2025-06-03 18:06:55 | CONTAINER_STATE_DIR = /container/run/state
***  DEBUG  | 2025-06-03 18:06:55 | CONTAINER_LOG_LEVEL = 4
***  DEBUG  | 2025-06-03 18:06:55 | INITRD = no
***  DEBUG  | 2025-06-03 18:06:55 | LC_CTYPE = en_US.UTF-8
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_BASE_DN =
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_READONLY_USER_USERNAME = readonly
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_READONLY_USER_PASSWORD = readonly
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_BACKEND = mdb
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_TLS_DH_PARAM_FILENAME = dhparam.pem
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_REPLICATION = False
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_REPLICATION_CONFIG_SYNCPROV = binddn="cn=admin,cn=config" bindmethod=simple credentials="$LDAP_CONFIG_PASSWORD" searchbase="cn=config" type=refreshAndPersist retry="60 +" timeout=1 starttls=critical
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_REPLICATION_DB_SYNCPROV = binddn="cn=admin,$LDAP_BASE_DN" bindmethod=simple credentials="$LDAP_ADMIN_PASSWORD" searchbase="$LDAP_BASE_DN" type=refreshAndPersist interval=00:00:00:10 retry="60 +" timeout=1 starttls=critical
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_REPLICATION_HOSTS = #COMPLEX_BASH_ENV:TABLE: LDAP_REPLICATION_HOSTS_ROW_1 LDAP_REPLICATION_HOSTS_ROW_2
***  DEBUG  | 2025-06-03 18:06:55 | KEEP_EXISTING_CONFIG = False
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_SSL_HELPER_PREFIX = ldap
***  DEBUG  | 2025-06-03 18:06:55 | SSL_HELPER_AUTO_RENEW_SERVICES_IMPACTED = slapd
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_SEED_INTERNAL_LDAP_TLS_CRT_FILE =
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_SEED_INTERNAL_LDAP_TLS_KEY_FILE =
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_SEED_INTERNAL_LDAP_TLS_CA_CRT_FILE =
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_SEED_INTERNAL_LDAP_TLS_DH_PARAM_FILE =
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_SEED_INTERNAL_LDIF_PATH =
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_SEED_INTERNAL_SCHEMA_PATH =
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_LOG_LEVEL = 256
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_NOFILE = 1024
***  DEBUG  | 2025-06-03 18:06:55 | DISABLE_CHOWN = False
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_PORT = 389
***  DEBUG  | 2025-06-03 18:06:55 | LDAPS_PORT = 636
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_REPLICATION_HOSTS_ROW_1 = ldap://ldap.example.org
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_REPLICATION_HOSTS_ROW_2 = ldap://ldap2.example.org
***  DEBUG  | 2025-06-03 18:06:55 | ------------------------------------------
***  INFO   | 2025-06-03 18:06:55 | Running /container/run/startup/slapd...
***  DEBUG  | 2025-06-03 18:06:55 | ------------ Environment dump ------------
***  DEBUG  | 2025-06-03 18:06:55 | CONTAINER_LOG_LEVEL = 4
***  DEBUG  | 2025-06-03 18:06:55 | CONTAINER_SERVICE_DIR = /container/service
***  DEBUG  | 2025-06-03 18:06:55 | CONTAINER_STATE_DIR = /container/run/state
***  DEBUG  | 2025-06-03 18:06:55 | DISABLE_CHOWN = False
***  DEBUG  | 2025-06-03 18:06:55 | HOME = /root
***  DEBUG  | 2025-06-03 18:06:55 | HOSTNAME = 337aed640cd8
***  DEBUG  | 2025-06-03 18:06:55 | INITRD = no
***  DEBUG  | 2025-06-03 18:06:55 | KEEP_EXISTING_CONFIG = False
***  DEBUG  | 2025-06-03 18:06:55 | LANG = en_US.UTF-8
***  DEBUG  | 2025-06-03 18:06:55 | LANGUAGE = en_US:en
***  DEBUG  | 2025-06-03 18:06:55 | LC_ALL = en_US.UTF-8
***  DEBUG  | 2025-06-03 18:06:55 | LC_CTYPE = en_US.UTF-8
***  DEBUG  | 2025-06-03 18:06:55 | LDAPS_PORT = 636
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_ADMIN_PASSWORD = admin
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_BACKEND = mdb
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_BASE_DN =
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_BOOTSTRAP = true
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_BOOTSTRAP_LDIF_ORDER = 01-config-password.ldif,01-ou.ldif,02-ldap-admin-user.ldif,20_acl.ldif,03-ldapadmins-group.ldif,04-everybody-group.ldif,05-alumnos-users.ldif,06-alumnos-groups.ldif,07-profesor-users.ldif,08-profesores-group.ldif,09-docker-group.ldif,10-lastUID-GID.ldif,11-sudo-schema.ldif,12-sudo-profesores.ldif,13-uniqueMember-index.ldif
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_CONFIG_PASSWORD = admin
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_DOMAIN = tierno.es
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_FORCE_BOOTSTRAP = true
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_LOG_LEVEL = 256
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_NOFILE = 1024
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_ORGANISATION = IES TIERNO GALVAN
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_PORT = 389
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_READONLY_USER = false
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_READONLY_USER_PASSWORD = readonly
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_READONLY_USER_USERNAME = readonly
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_REMOVE_CONFIG_AFTER_SETUP = false
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_REPLICATION = False
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_REPLICATION_CONFIG_SYNCPROV = binddn="cn=admin,cn=config" bindmethod=simple credentials="$LDAP_CONFIG_PASSWORD" searchbase="cn=config" type=refreshAndPersist retry="60 +" timeout=1 starttls=critical
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_REPLICATION_DB_SYNCPROV = binddn="cn=admin,$LDAP_BASE_DN" bindmethod=simple credentials="$LDAP_ADMIN_PASSWORD" searchbase="$LDAP_BASE_DN" type=refreshAndPersist interval=00:00:00:10 retry="60 +" timeout=1 starttls=critical
***  DEBUG  | 2025-06-03 18:06:55 | LDAP_REPLICATION_HOSTS = #COMPLEX_BASH_ENV:TAB