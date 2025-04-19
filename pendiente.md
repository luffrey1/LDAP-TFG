**PENDIENTE**

CAMBIAR ESTO:
LDAP_DEFAULT_HOSTS=172.19.0.4
xq sino hay que cambiar siempre la ip del ldap default cada persona que entra, (está asi por un bug de inicio de sesion solo por LDAP-ADMIN)

1: Revisar que funcione la creacion de usuarios y se hashee la contraseña correctamente, que se eliminen correctamente los usuarios que eligas en las pestañas, que el boton de la coronita de admin funcione bien.
2:Los mensajes que se puedan reenviar, borrar, destacar etc etc...
3: En el calendario que funcionen bien los eventos y se muestren en el dashboard correctamente con contador y eventos proximos.
4:Si todo lo anterior funciona y en general todo pasar al siguiente paso sino revisar
5:Crear la conexión remota con vpn
6:Añadir un apartado en el que los admins o el admin principal(ldap-admin o Santi directamente) puedan poner funcionales las caracteristicas 
de: mensajes,documentos,calendario, y el requisito en las contraseñas y que las contraseñas que no cumplan esos requisitos les salga un 
aviso durante 7 días sino un admin tendra que cambiarles la contraseña, un apartado del perfil propio para cambiar la contraseña lo más optimo.

7:Al acabar todas estas secciones empezar(algunas son ideas, la mayoria): 
-Ordenar los ordenadores por clases y si es posible que cada alumno tenga un perfil ldap asociado y que inicie sesión con este independiente del ordenador(carga de archivos opcional), y que si realiza acciones criticas que puedan afectar negativamente a los ordenadores que son de uso publico se notifique y sea visible.
-Telemetria, ping a ordenadores, ver cuales estan activos, revisar caracteristicas de estos
-Opción modo examen, trata de que si acceden a cualquier IA(o web o lo que se decida) mientras el modo examen esta activado se les bloqueara el ordenador y sacará una captura antes de esto y le aparecerá un mensaje con sonido al profesor que tenga iniciada la sesión en la web. 
-Ejecutar comandos masivamente remotamente

--Usuarios personalizados para cada alumno con su propia contraseña, para asi monetorizar que acciones hacen, que descargan, si utilizan de más el espacio o cosas sospechosas
- más funciones proximamente...