# Pendiente

## Correcciones Prioritarias
- Cambiar `LDAP_DEFAULT_HOSTS=172.19.0.4` ya que actualmente hay que cambiar la IP del LDAP por defecto para cada nuevo usuario (está así por un bug de inicio de sesión exclusivo con LDAP-ADMIN)

## Tareas Inmediatas
1- **mensajeria arreglarlo**
2- **Que funcionen los logs, los olvidamos y con todos los cambios estan totalmente diferentes**
3- **El panel de configuracion donde se desactivan secciones arreglarlo porque con tantos cambios se ha ido**

##No tan inmediatas
3- **que se puedan eliminar equipos, no funciona y redirige al dashboard, mirar temas de rutas lo más posible o alguna configuracion sql**

4- **Hacer lo de acceder por vpn y tal**

5- **Que funcione la terminal con websockets y reverb**


6- Implementar sistema de avisos para contraseñas que no cumplan requisitos (7 días de plazo)NO FUNCIONA
7- Crear interfaz para que los usuarios puedan cambiar su contraseña en su perfil NO IMPLEMENTADO

## Futuras Implementaciones

### Gestión de equipos y usuarios
- Organizar ordenadores por clases


### Monitorización básica
- Implementar sistema de ping a ordenadores
- Visualizar equipos activos en tiempo real
- Mostrar características técnicas de los equipos conectados

### Administración remota
- Permitir ejecución masiva de comandos en equipos remotos

## Ideas Conceptuales

### Perfiles móviles avanzados
- Permitir inicio de sesión independiente del ordenador físico
- Implementar carga de archivos personales entre sesiones
- Sistema de sincronización de configuraciones personales
- Asignar perfiles LDAP personalizados a cada alumno

### Sistema de seguridad proactivo
- Monitorizar y notificar acciones críticas que puedan afectar negativamente a equipos
- Detección de comportamientos sospechosos
- Sistema predictivo de problemas basado en patrones de uso

### Modo examen inteligente
- Detectar acceso a sitios no permitidos (IA, webs específicas)
- Bloquear ordenador automáticamente si se detectan infracciones
- Capturar pantalla como evidencia antes del bloqueo
- Enviar notificación con sonido al profesor que tenga iniciada la sesión

### Analítica de uso avanzada
- Monitorizar actividad detallada de cada usuario
- Controlar descargas y uso del espacio
- Generar informes periódicos de actividad por aula/grupo

---
*Más funcionalidades por definir próximamente...*
