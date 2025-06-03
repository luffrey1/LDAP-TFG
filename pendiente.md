# Pendiente





IMPORTANTE **No funciona boton de CREAR EVENTO EN /calendario , arreglar la creacion de eventos en calendario** IMPORTANTE


ARREGLADO COLORES ✅
- **✅ El usuario ldap-admin no debe poderse quitar del grupo ldapadmins**


- **✅Cambiar "Gestión de Grupos LDAP" por "Grupos LDAP" y subir debajo de Usuarios LDAP.**

- Monitor de equipos: sección de Discos con porcentaje individual de llenado en barra horizontal. El chart junto a CPU y Memoria que muestre el llenado total de entre todos los discos.

- Rectificar explicaicón Configuración telemetría: es Laravel quien pide los datos a los agentes, no al revés.

SANTI: os pasa la clave privada/pública para el usuario administrator.

Crear usuario LDAP:

- Nombre de usuario debajo de Apellido.

- Username y email que se autocompleten pero tambi'en se puedan modificar a mano.

Selecci'on de tipo de usuario profesor o alumno arriba del todo. Que se modifique tambi'en el GID por defecto buscando ese GID del que tenga el grupo "profesores" o "alumnos" haciendo una consulta ldap.

Que se marque usuario tipo alumno por defecto.

Poner el texto "Automático" al UID como placeholder cuando el formulario está vacío.

el DN que no sea en formulario. Que aparezca debajo del username como texto sincronizado con el username.

Filtrar usuarios LDAP por grupo no actualiza los grupos actuales. Hacer consulta al ldap.✅

Poder modificar passwords a un grupo de usuarios.

EQUIPOS:

Al añadir equipos, que funcione con otros edificios estilo A3-B5.  ✅

Criterio: si hay qun guion en el nombre, la primera parte es el grupo. Si no hay guion, va a Sin grupo. ✅

Quitar dos botones de "Actualizar Estado" y "Actualizar Routers". Poner un solo boton de "Actualizar grupo" en la sección de cada grupo.✅

Cuando se pulsa "Actualizar grupo" se cambia por un texto sin botón que ponga "Escaneando hosntames..." o "Escaneando IPs...".✅

En Escanear po IP quitar las tres opciones en escaneo de la red. Quitar también lo de DHCP 1y DHCP2.✅

LDAP:

al mostrar lista usuarios que se pueda pinchar en la etiqueta de un grupo para filtrar.✅

Actualizar lastUID y lastGID al añadir usuario o grupo de usuarios. Igual para añadir grupo. IMPORTANTE!!!!!!

LdapUserController.php:2175 poner $maxUID = 1000;

## Tareas Inmediatas
- **Revisar colores en toda la aplicación..**
**quitar seccion de gestion academica y adaptar los csv con contraseña en gestion de usuarios**
**que los logs de usuarios se guarden bien en la database**
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

