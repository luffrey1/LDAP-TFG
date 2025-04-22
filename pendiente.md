# Pendiente

## Correcciones Prioritarias
- Cambiar `LDAP_DEFAULT_HOSTS=172.19.0.4` ya que actualmente hay que cambiar la IP del LDAP por defecto para cada nuevo usuario (está así por un bug de inicio de sesión exclusivo con LDAP-ADMIN)

## Tareas Inmediatas
1. **Gestión de usuarios YA FUNCIONA**
   - Revisar que funcione correctamente la creación de usuarios # funciona #
   - Verificar que se hashee la contraseña correctamente # funciona #
   - Comprobar que los usuarios se eliminen correctamente desde la interfaz # funciona #
   - Asegurar que la función de promover a administrador (icono de corona) funcione bien # funciona #

2. **Sistema de mensajería YA FUNCIONA**
   - Implementar funcionalidad para reenviar mensajes
   - Añadir opción para borrar mensajes
   - Permitir destacar mensajes importantes
   - Revisar otras funcionalidades de mensajería

3. **Calendario**
   - Verificar el correcto funcionamiento de los eventos
   - Comprobar que se muestren correctamente en el dashboard
   - Asegurar que los contadores y eventos próximos se actualicen adecuadamente

4. **Evaluación general**
   - Si todas las funcionalidades anteriores funcionan correctamente, avanzar a la siguiente fase
   - En caso contrario, revisar y solucionar los problemas pendientes

5. **Conectividad remota**
   - Establecer conexión remota mediante VPN

6. **Panel de administración**
   - Desarrollar área donde administradores (ldap-admin o administradores designados) puedan gestionar:
     - Activación/desactivación de mensajería
     - Control de documentos
     - Configuración del calendario
     - Requisitos de contraseña
   - Implementar sistema de avisos para contraseñas que no cumplan requisitos (7 días de plazo)
   - Crear interfaz para que los usuarios puedan cambiar su contraseña en su perfil

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