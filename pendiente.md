# Pendiente

## Tareas Inmediatas
- **Revisar colores en toda la aplicación..**
### General
-**✅ Al importar de archivo(para alumnos), generar contraseñas aleatorias en JS y mostrarlas en tabla**
--esto habia que eliminar lo de alumnos y ponerlo directamente en gestion de usuarios y tambien importar profesores por csv a poder ser si se puede 
(Ambos)
- **Mensajería no deja subir archivos (posible problema SQL)**
- **Al crear un usuario comprobar que permite números en el nombre**
- **Arreglar sistema de mensajería: mostrar datos reales y todos los profesores de otra manera porque cuando haya muchos será raro quizás como gmail y tal buscar y que se vaya completando mientras busques o te sugiera sabes**
-**✅ Que los eventos se cuenten en el dashboard (eventos: 1...)**

### LDAP
(Dani)
- **✅Que el panel de usuario de creacion y ver lo puedan ver todos pero solo pueda dar admin otro admin**
- **✅ Mostrar UID, GID, directorio con autocompletado del nombre**
- **✅ Añadir shell a /bin/bash (por defecto)**
- **✅ Home directory autocompletado**
- **✅ Mostrar nombre de LDAP en formato uid=usuario,ou=people,dc=tierno,dc=es (CN)**
- **✅Al ver perfil de usuario, mostrar grupos en dos columnas o con grupos marcados**
(Anieto)
-**✅ Al crear usuario(para alumnos), añadir botón para generar contraseña aleatoria**
-**✅ Al importar de archivo(para alumnos), generar contraseñas aleatorias en JS y mostrarlas en tabla**
-**✅ Añadir botón para descargar tabla con contraseñas(para alumnos)**
-**✅ Indicar mensaje "Las contraseñas solo las puedes ver ahora, se almacenarán hasheadas"**
-**✅ Crear y editar grupos (GID y descripción opcional) - Solo ldapadmins**

### Seguridad(Anieto)
-**✅ Desactivación global de acceso SSH en Configuración de administración**
-**✅Quitar días de aviso en password**
-**✅ Aclarar "Rol Admin" en el perfil de usuario**

### Agente de Telemetría (Dani)
- **Agente con API REST usando Flask, se llama al pulsar botón "Comprobar ahora"**

### Equipos (Dani)
- **En detalles de equipo, mostrar aula con enlace a detalles del aula**
- **Al crear equipo, implementar dos tipos diferentes:**
  - **IP Fija: Se escribe IP y hostname**
  - **DHCP: Se escribe hostname, IP se detecta**
  - **En ambos casos, MAC se detecta**
- **Al crear equipo, mostrar botón DETECTAR:**
  - **Si hay éxito, mostrar botón GUARDAR**
  - **Si no, mostrar botón GUARDAR SIN COMPROBAR**
  - **El botón DETECTAR debe permanecer visible para repetir detección**
- **Escaneo de red: mover a sección al fondo llamada "Utilidades"**

### SSSD (Configuración en clientes)
```bash
apt-get install -y sssd-ldap ldap-utils libsss-sudo sssd-tools
# Configurar fichero /etc/sssd/sssd.conf como se muestra abajo
systemctl restart sssd.service
```

**Archivo de configuración para SSSD:**
```
# IES Enrique Tierno Galván

[sssd]
config_file_version = 2
domains = tierno.es

[domain/lan.tiernogalvan.es]
id_provider = ldap
auth_provider = ldap
ldap_uri = ldap://ldap.tierno.es
ldap_id_use_start_tls = true
ldap_schema = rfc2307bis
ldap_search_base = dc=tierno,dc=es
ldap_group_member = uniqueMember
cache_credentials = True

# Evitamos listar usuarios del ldap por privacidad
enumerate = false
```

## Tareas No Tan Inmediatas
- **Que se puedan ordenar clases**
- **Implementar filtrado al escribir en LDAP lo de gestion de usuarios**

