#!/bin/bash

# Colores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # Sin color

# Clave pública SSH del servidor web - Será reemplazada por el comando ssh:generate-key
SSH_PUBLIC_KEY="ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAACAQCdlOL09CguxdzaRj+mInOrDPOSBaQ7EQ8Q1Qf7iTeVliW5sH2fw2dSPD/avJJIkTPPEpoI9ocCuly54fyVr8o+vNiwyJVKnN8uDAq/8y+SNUR/71cyLWlS3Nf2kbUwG2gHjJud4hP8AEuZPtLzfVCnImJxL70QGniKTJ8bRxQ8eEyTGbU7v77LpOws6yF7X21IvjrZ2cxt4nzsLYE7LZPMrGScLfQtCVgEi8h+On9+2hDyYLolIY+i/EC7OopIPLFRon0pErI56AVRDZG6IF8ehN86iaqGtoX0PzHZVuT6aLyq7BnO1zB4W16qXGB3AsnoH6BAfZgVu36PBvdbNepC978g4n3qlglliX0xbD31m+xOEFAfYPOJOWF39tG2ojcZp0pL765Jl6qRk2gAY7CCaJJ10CKG5QF3qwCeAwyJ3hYKKN8nBsbTzLunsubCtHYH4F5TcHBoK/E36TDpMG/vN2aFJ1xSM/AzNJpeOCXE27qeOfgHUF5/2Lgk+YLAmJzOzgMYhkC1onUFY8gfB0MhFoN9tA7SXjCL/XbTbaAvgAcypetkPn5c//bQiUHT7mimRjW1XiI3z3lMjNXQktF8x0W1Pz9VUMkRO8PmkHWctQwvYDPy7HpxomfZr0j8yPVU4FgzuByJHecPek2gULXsjNb2zTz+Icqv+4+WlbTmxw== root@3bf09742979a"

# Funciones
print_section() {
    echo -e "\n${GREEN}==== $1 ====${NC}\n"
}

# Comprobar si se ejecuta como root
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}ERROR: Este script debe ejecutarse como root o con sudo${NC}"
    exit 1
fi

# Configuración - AQUÍ ES DONDE DEBES PONER TU SERVIDOR LDAP EXISTENTE
LDAP_HOST="172.19.0.4"
LDAP_PORT="389"
LDAP_BASE_DN="dc=test,dc=tierno,dc=es"
LDAP_BIND_DN="cn=admin,dc=test,dc=tierno,dc=es"
LDAP_ADMIN_PASSWORD="admin"

print_section "Configurando cliente LDAP para tu servidor existente"

# Instalar paquetes necesarios para cliente LDAP
export DEBIAN_FRONTEND=noninteractive
apt-get update
apt-get install -y libnss-ldap libpam-ldap nscd ldap-utils libpam-mkhomedir

# Configurar archivos NSS para usar LDAP
sed -i 's/passwd:.*compat/passwd:         files ldap/g' /etc/nsswitch.conf
sed -i 's/group:.*compat/group:          files ldap/g' /etc/nsswitch.conf
sed -i 's/shadow:.*compat/shadow:         files ldap/g' /etc/nsswitch.conf

# Configurar PAM para crear directorios home automáticamente
pam-auth-update --enable mkhomedir

# Configurar LDAP client
cat > /etc/ldap.conf <<EOF
base $LDAP_BASE_DN
uri ldap://$LDAP_HOST:$LDAP_PORT
ldap_version 3
rootbinddn $LDAP_BIND_DN
pam_password md5
bind_policy soft
EOF

# Configurar SSH para permitir login con LDAP
print_section "Configurando SSH para acceso remoto"
apt-get install -y openssh-server

# Configurar SSH para permitir PasswordAuthentication
sed -i 's/#PasswordAuthentication yes/PasswordAuthentication yes/g' /etc/ssh/sshd_config
sed -i 's/PasswordAuthentication no/PasswordAuthentication yes/g' /etc/ssh/sshd_config

# Reiniciar servicios para aplicar cambios
systemctl restart nscd
systemctl restart ssh

print_section "Instalando agente de monitorización"
# Crear directorio para el agente
mkdir -p /opt/monitoragent

# Instalar dependencias del agente
apt-get install -y jq curl bc net-tools

# Obtener IP del servidor
SERVER_IP=$(hostname -I | awk '{print $1}')

print_section "Instalación completada"
echo -e "${GREEN}Cliente LDAP configurado:${NC}"
echo "   Servidor LDAP: $LDAP_HOST:$LDAP_PORT"
echo "   Base DN: $LDAP_BASE_DN"
echo ""
echo -e "${GREEN}Acceso SSH configurado:${NC}"
echo "   IP del servidor: $SERVER_IP"
echo ""
echo -e "${YELLOW}Para conectarte por SSH:${NC}"
echo "   ssh usuario_ldap@$SERVER_IP"
echo ""
echo -e "${YELLOW}Para transferir el agente de monitorización:${NC}"
echo "   scp MonitorAgent.sh usuario_ldap@$SERVER_IP:/opt/monitoragent/"
echo ""
echo -e "${GREEN}IMPORTANTE: Podrás iniciar sesión con cualquier usuario que exista en tu LDAP${NC}"

echo -e "${GREEN}==== Configurando SSH para comandos remotos ====${NC}"

# Instalar SSH
apt-get update
apt-get install -y openssh-server

# Permitir acceso root por SSH (necesario para ejecutar comandos remotos)
sed -i 's/#PermitRootLogin prohibit-password/PermitRootLogin yes/g' /etc/ssh/sshd_config

# Asegurar que se permite autenticación por contraseña
sed -i 's/#PasswordAuthentication yes/PasswordAuthentication yes/g' /etc/ssh/sshd_config
sed -i 's/PasswordAuthentication no/PasswordAuthentication yes/g' /etc/ssh/sshd_config

# Reiniciar SSH
systemctl restart ssh

# Cambiar contraseña de root (puedes modificarla por seguridad)
echo "root:password" | chpasswd

# Crear directorio para scripts
mkdir -p /opt/monitor-scripts
chmod 755 /opt/monitor-scripts

echo -e "${GREEN}==== Instalando dependencias para scripts ====${NC}"
# Instalar herramientas para monitoreo y ejecución de scripts
apt-get install -y jq curl bc net-tools sshpass

# Copiar scripts de monitoreo si están disponibles localmente
if [ -f "MonitorAgent.sh" ]; then
    cp MonitorAgent.sh /opt/monitor-scripts/
    chmod +x /opt/monitor-scripts/MonitorAgent.sh
    echo -e "${GREEN}Script MonitorAgent.sh copiado a /opt/monitor-scripts/${NC}"
fi

# Configurar acceso SSH sin contraseña desde el servidor web
print_section "Configurando acceso SSH sin contraseña desde el servidor web"
SSH_DIR="/root/.ssh"
mkdir -p $SSH_DIR
chmod 700 $SSH_DIR

# Crear/actualizar archivo authorized_keys
AUTHORIZED_KEYS_FILE="$SSH_DIR/authorized_keys"
touch $AUTHORIZED_KEYS_FILE
chmod 600 $AUTHORIZED_KEYS_FILE

# Si tenemos una clave SSH predefinida, añadirla
if [ -n "$SSH_PUBLIC_KEY" ]; then
    # Verificar si la clave ya está en el archivo
    if ! grep -q "$SSH_PUBLIC_KEY" "$AUTHORIZED_KEYS_FILE"; then
        echo -e "${GREEN}Añadiendo clave pública del servidor web al authorized_keys${NC}"
        echo "$SSH_PUBLIC_KEY" >> $AUTHORIZED_KEYS_FILE
        echo -e "${GREEN}Clave añadida correctamente.${NC}"
    else
        echo -e "${GREEN}La clave pública ya estaba configurada.${NC}"
    fi
else
    echo -e "${YELLOW}No se ha proporcionado clave pública del servidor web.${NC}"
    echo -e "${YELLOW}Para configurar acceso sin contraseña, deberá añadir manualmente la clave pública del servidor web a:${NC}"
    echo "$AUTHORIZED_KEYS_FILE"
fi

# Obtener y mostrar IP
IP=$(hostname -I | awk '{print $1}')

echo -e "${GREEN}==== Configuración completada ====${NC}"
echo ""
echo "IP del servidor: $IP"
echo "Usuario SSH: root"
echo "Contraseña: password (si la autenticación por clave no funciona)"
echo ""
echo -e "${YELLOW}Para configurar este host en la aplicación web:${NC}"
echo "1. Vaya a la sección de Monitoreo"
echo "2. Añada un nuevo host con esta IP: $IP"
echo "3. En la configuración SSH use:"
echo "   - Usuario: root"
echo "   - Puede usar autenticación por clave o contraseña (password)"
echo "   - Puerto: 22"
echo ""
echo -e "${YELLOW}Acceso SSH sin contraseña:${NC}"
if [ -n "$SSH_PUBLIC_KEY" ]; then
    echo "Se ha configurado acceso automático sin contraseña. Debería funcionar inmediatamente."
else
    echo "No se ha configurado acceso automático sin contraseña."
    echo "Debe ejecutar 'php artisan ssh:generate-key' en el servidor web y volver a descargar este script."
fi
echo ""
echo -e "${GREEN}Ahora podrá enviar comandos a este host desde la interfaz web${NC}"