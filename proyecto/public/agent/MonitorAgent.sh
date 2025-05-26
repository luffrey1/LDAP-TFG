#!/bin/bash
# Agente de Monitorización para Linux
# Recopila información del sistema y la envía al servidor de monitorización
# Soporta ejecución de comandos remotos y recopilación avanzada de telemetría
# Uso: bash MonitorAgent.sh -s "https://tuservidor.com"

# Parámetros por defecto
SERVER_URL="https://localhost:8000"
INTERVAL=300  # Intervalo de envío en segundos (por defecto 5 minutos)
RUN_ONCE=false
AGENT_VERSION="1.2.0"
COMMAND_CHECK_INTERVAL=60  # Verificar comandos cada minuto
TOKEN_FILE=".monitor_token"
AGENT_ID=""

# Función para mostrar ayuda
usage() {
    echo "Uso: $0 [opciones]"
    echo "Opciones:"
    echo "  -s, --server URL    URL del servidor de monitoreo (por defecto: https://localhost:8000)"
    echo "  -i, --interval N    Intervalo de envío en segundos (por defecto: 300)"
    echo "  -c, --commands N    Intervalo para verificar comandos en segundos (por defecto: 60)"
    echo "  -t, --token TOKEN   Token de autenticación para el servidor"
    echo "  -o, --once          Ejecutar una sola vez y terminar"
    echo "  -h, --help          Mostrar esta ayuda"
    exit 1
}

# Parsear argumentos
while [[ $# -gt 0 ]]; do
    case "$1" in
        -s|--server)
            SERVER_URL="$2"
            shift 2
            ;;
        -i|--interval)
            INTERVAL="$2"
            shift 2
            ;;
        -c|--commands)
            COMMAND_CHECK_INTERVAL="$2"
            shift 2
            ;;
        -t|--token)
            echo "$2" > "$TOKEN_FILE"
            shift 2
            ;;
        -o|--once)
            RUN_ONCE=true
            shift
            ;;
        -h|--help)
            usage
            ;;
        *)
            echo "Opción desconocida: $1"
            usage
            ;;
    esac
done

# Función para obtener información del sistema
get_system_info() {
    # Obtener hostname
    HOSTNAME=$(hostname)
    
    # Obtener dirección IP
    IP_ADDRESS=$(hostname -I | awk '{print $1}')
    
    # Obtener dirección MAC (método mejorado)
    INTERFACE=$(ip route | grep default | awk '{print $5}')
    MAC_ADDRESS=$(cat /sys/class/net/${INTERFACE}/address 2>/dev/null || 
                 ip link show ${INTERFACE} | grep link/ether | awk '{print $2}' 2>/dev/null || 
                 echo "00:00:00:00:00:00")
    
    # Información del sistema operativo
    OS_NAME=$(cat /etc/os-release | grep "PRETTY_NAME" | cut -d "=" -f 2 | tr -d '"')
    OS_VERSION=$(uname -r)
    
    # Información del CPU
    CPU_MODEL=$(cat /proc/cpuinfo | grep "model name" | head -1 | cut -d ":" -f 2 | sed 's/^[ \t]*//')
    CPU_CORES=$(grep -c ^processor /proc/cpuinfo)
    
    # Información de la RAM
    TOTAL_RAM_KB=$(cat /proc/meminfo | grep "MemTotal" | awk '{print $2}')
    TOTAL_RAM_GB=$(echo "scale=2; $TOTAL_RAM_KB/1024/1024" | bc)
    
    # Último arranque
    LAST_BOOT=$(uptime -s)
    UPTIME=$(uptime -p)
    
    # Detalles adicionales
    SYSTEM_TYPE=$(uname -m)
    KERNEL_VERSION=$(uname -r)
    
    # Intentar obtener información de temperatura si está disponible
    if command -v sensors &> /dev/null; then
        CPU_TEMP=$(sensors | grep -i "Core 0" | awk '{print $3}' | tr -d '+°C' || echo "N/A")
    else
        CPU_TEMP="N/A"
    fi
    
    # Crear el JSON
    echo "{"
    echo "  \"hostname\": \"$HOSTNAME\","
    echo "  \"ip_address\": \"$IP_ADDRESS\","
    echo "  \"mac_address\": \"$MAC_ADDRESS\","
    echo "  \"system_info\": {"
    echo "    \"os_name\": \"$OS_NAME\","
    echo "    \"os_version\": \"$OS_VERSION\","
    echo "    \"kernel\": \"$KERNEL_VERSION\","
    echo "    \"cpu_model\": \"$CPU_MODEL\","
    echo "    \"cpu_cores\": $CPU_CORES,"
    echo "    \"total_ram_gb\": $TOTAL_RAM_GB,"
    echo "    \"details\": {"
    echo "      \"system_type\": \"$SYSTEM_TYPE\","
    echo "      \"architecture\": \"$(uname -m)\","
    echo "      \"processor\": \"$CPU_MODEL\","
    echo "      \"processor_cores\": $CPU_CORES,"
    echo "      \"cpu_temperature\": \"$CPU_TEMP\""
    echo "    }"
    echo "  },"
    echo "  \"uptime\": \"$UPTIME\","
    echo "  \"last_boot\": \"$LAST_BOOT\""
    echo "}"
}

# Función para obtener uso de disco
get_disk_usage() {
    # Obtener información de discos
    echo "{"
    echo "  \"drives\": {"
    
    DRIVES_COUNT=$(df -h | grep '^/dev/' | wc -l)
    CURRENT_DRIVE=1
    
    df -h | grep '^/dev/' | while read -r line; do
        DEVICE=$(echo $line | awk '{print $1}')
        MOUNT=$(echo $line | awk '{print $6}')
        TOTAL=$(echo $line | awk '{print $2}' | sed 's/G//')
        USED=$(echo $line | awk '{print $3}' | sed 's/G//')
        FREE=$(echo $line | awk '{print $4}' | sed 's/G//')
        PERCENT=$(echo $line | awk '{print $5}' | sed 's/%//')
        
        echo "    \"$MOUNT\": {"
        echo "      \"device\": \"$DEVICE\","
        echo "      \"total_gb\": \"$TOTAL\","
        echo "      \"free_gb\": \"$FREE\","
        echo "      \"used_gb\": \"$USED\","
        echo "      \"used_percent\": $PERCENT"
        
        if [[ $CURRENT_DRIVE -eq $DRIVES_COUNT ]]; then
            echo "    }"
        else
            echo "    },"
            CURRENT_DRIVE=$((CURRENT_DRIVE + 1))
        fi
    done
    
    echo "  },"
    
    # Totales
    TOTAL_SIZE=$(df -h --total | grep 'total' | awk '{print $2}' | sed 's/G//')
    TOTAL_USED=$(df -h --total | grep 'total' | awk '{print $3}' | sed 's/G//')
    TOTAL_FREE=$(df -h --total | grep 'total' | awk '{print $4}' | sed 's/G//')
    TOTAL_PERCENT=$(df -h --total | grep 'total' | awk '{print $5}' | sed 's/%//')
    
    echo "  \"total\": \"$TOTAL_SIZE\","
    echo "  \"free\": \"$TOTAL_FREE\","
    echo "  \"used\": \"$TOTAL_USED\","
    echo "  \"used_percent\": $TOTAL_PERCENT"
    echo "}"
}

# Función para obtener uso de memoria
get_memory_usage() {
    # Obtener estadísticas de memoria
    TOTAL_MEM=$(free -m | grep "Mem:" | awk '{print $2}')
    FREE_MEM=$(free -m | grep "Mem:" | awk '{print $4+$6}')
    USED_MEM=$(free -m | grep "Mem:" | awk '{print $3}')
    USED_PERCENT=$(echo "scale=2; ($USED_MEM/$TOTAL_MEM)*100" | bc)
    
    # Obtener uso de swap
    TOTAL_SWAP=$(free -m | grep "Swap:" | awk '{print $2}')
    FREE_SWAP=$(free -m | grep "Swap:" | awk '{print $4}')
    USED_SWAP=$(free -m | grep "Swap:" | awk '{print $3}')
    
    if [ "$TOTAL_SWAP" -gt 0 ]; then
        SWAP_PERCENT=$(echo "scale=2; ($USED_SWAP/$TOTAL_SWAP)*100" | bc)
    else
        SWAP_PERCENT="0"
    fi
    
    echo "{"
    echo "  \"total\": $TOTAL_MEM,"
    echo "  \"free\": $FREE_MEM,"
    echo "  \"used\": $USED_MEM,"
    echo "  \"used_percent\": $USED_PERCENT,"
    echo "  \"swap\": {"
    echo "    \"total\": $TOTAL_SWAP,"
    echo "    \"used\": $USED_SWAP,"
    echo "    \"free\": $FREE_SWAP,"
    echo "    \"used_percent\": $SWAP_PERCENT"
    echo "  }"
    echo "}"
}

# Función para obtener uso de CPU
get_cpu_usage() {
    # Obtener uso de CPU
    CPU_USAGE=$(top -bn1 | grep "Cpu(s)" | sed "s/.*, *\([0-9.]*\)%* id.*/\1/" | awk '{print 100 - $1}')
    
    # Obtener carga del sistema
    LOAD_AVG=$(cat /proc/loadavg | awk '{print $1, $2, $3}')
    LOAD_1=$(echo $LOAD_AVG | awk '{print $1}')
    LOAD_5=$(echo $LOAD_AVG | awk '{print $2}')
    LOAD_15=$(echo $LOAD_AVG | awk '{print $3}')
    
    echo "{"
    echo "  \"percent\": $CPU_USAGE,"
    echo "  \"load\": {"
    echo "    \"1min\": $LOAD_1,"
    echo "    \"5min\": $LOAD_5,"
    echo "    \"15min\": $LOAD_15"
    echo "  }"
    echo "}"
}

# Función para obtener información de red
get_network_info() {
    # Obtener interfaces de red activas
    echo "{"
    echo "  \"interfaces\": {"
    
    INTERFACES=$(ip -j link show | jq -r '.[] | select(.link_type == "ether") | .ifname')
    INTERFACE_COUNT=$(echo "$INTERFACES" | wc -l)
    CURRENT_INTERFACE=1
    
    for iface in $INTERFACES; do
        # Obtener dirección IP, MAC y estadísticas
        IP=$(ip -j addr show dev $iface | jq -r '.[0].addr_info[] | select(.family == "inet") | .local' 2>/dev/null || echo "")
        MAC=$(ip -j link show dev $iface | jq -r '.[0].address' 2>/dev/null || echo "")
        
        RX_BYTES=$(cat /sys/class/net/$iface/statistics/rx_bytes 2>/dev/null || echo "0")
        TX_BYTES=$(cat /sys/class/net/$iface/statistics/tx_bytes 2>/dev/null || echo "0")
        RX_MB=$(echo "scale=2; $RX_BYTES/1024/1024" | bc)
        TX_MB=$(echo "scale=2; $TX_BYTES/1024/1024" | bc)
        
        # Estado de enlace
        STATUS=$(cat /sys/class/net/$iface/operstate 2>/dev/null || echo "unknown")
        
        echo "    \"$iface\": {"
        echo "      \"ip\": \"$IP\","
        echo "      \"mac\": \"$MAC\","
        echo "      \"status\": \"$STATUS\","
        echo "      \"rx_mb\": $RX_MB,"
        echo "      \"tx_mb\": $TX_MB"
        
        if [[ $CURRENT_INTERFACE -eq $INTERFACE_COUNT ]]; then
            echo "    }"
        else
            echo "    },"
            CURRENT_INTERFACE=$((CURRENT_INTERFACE + 1))
        fi
    done
    
    echo "  },"
    
    # Estadísticas generales de red
    NETSTAT=$(netstat -s | grep -i "total packets received" | awk '{print $4}' 2>/dev/null || echo "0")
    CONNECTIONS=$(netstat -nat | grep ESTABLISHED | wc -l)
    
    echo "  \"total_connections\": $CONNECTIONS,"
    echo "  \"packets_received\": $NETSTAT"
    echo "}"
}

# Función para obtener los procesos principales
get_top_processes() {
    echo "{"
    echo "  \"processes\": ["
    
    # Obtener top 10 procesos por uso de CPU
    ps aux --sort=-%cpu | head -11 | tail -10 | while read -r line; do
        PID=$(echo $line | awk '{print $2}')
        USER=$(echo $line | awk '{print $1}')
        CPU=$(echo $line | awk '{print $3}')
        MEM=$(echo $line | awk '{print $4}')
        START=$(echo $line | awk '{print $9}')
        COMMAND=$(echo $line | awk '{for(i=11;i<=NF;i++) printf "%s ", $i; printf "\n"}' | sed 's/"/\\"/g' | cut -c 1-50)
        
        echo "    {"
        echo "      \"pid\": $PID,"
        echo "      \"user\": \"$USER\","
        echo "      \"cpu\": $CPU,"
        echo "      \"memory\": $MEM,"
        echo "      \"started\": \"$START\","
        echo "      \"command\": \"$COMMAND\""
        
        if [[ $PID == $(ps aux --sort=-%cpu | head -11 | tail -10 | tail -1 | awk '{print $2}') ]]; then
            echo "    }"
        else
            echo "    },"
        fi
    done
    
    echo "  ],"
    
    # Total de procesos
    TOTAL_PROCESSES=$(ps aux | wc -l)
    TOTAL_PROCESSES=$((TOTAL_PROCESSES - 1))  # Restar la línea de cabecera
    
    echo "  \"total_count\": $TOTAL_PROCESSES"
    echo "}"
}

# Función para obtener información de los usuarios conectados
get_users_info() {
    echo "{"
    echo "  \"users\": ["
    
    USERS_COUNT=$(who | wc -l)
    CURRENT_USER=1
    
    who | while read -r line; do
        USERNAME=$(echo $line | awk '{print $1}')
        TTY=$(echo $line | awk '{print $2}')
        LOGIN_TIME=$(echo $line | awk '{print $3, $4}')
        FROM=$(echo $line | awk '{print $5}' | sed 's/(//g; s/)//g')
        
        echo "    {"
        echo "      \"username\": \"$USERNAME\","
        echo "      \"terminal\": \"$TTY\","
        echo "      \"login_time\": \"$LOGIN_TIME\","
        echo "      \"from\": \"$FROM\""
        
        if [[ $CURRENT_USER -eq $USERS_COUNT ]]; then
            echo "    }"
        else
            echo "    },"
            CURRENT_USER=$((CURRENT_USER + 1))
        fi
    done
    
    echo "  ],"
    
    # Último login
    LAST_LOGIN=$(last -n 1 | head -1 | awk '{print $1, "desde", $3, "el", $4, $5, $6, $7}')
    echo "  \"last_login\": \"$LAST_LOGIN\""
    echo "}"
}

# Función para ejecutar un comando y devolver su salida
execute_command() {
    local CMD="$1"
    
    # Registrar el comando para propósitos de seguridad
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] Ejecutando: $CMD" >> command_log.txt
    
    # Lista de comandos peligrosos (podría expandirse)
    DANGEROUS_COMMANDS=("rm -rf /" "mkfs" "dd if=/dev/zero" ":(){ :|:& };:" "> /dev/sda" "mv /* /dev/null")
    
    # Comprobar si el comando es peligroso
    for dangerous in "${DANGEROUS_COMMANDS[@]}"; do
        if [[ "$CMD" == *"$dangerous"* ]]; then
            echo "Comando potencialmente peligroso detectado. Abortando ejecución."
            return 1
        fi
    done
    
    # Ejecutar el comando y capturar la salida
    OUTPUT=$(eval "$CMD" 2>&1)
    EXIT_CODE=$?
    
    # Crear JSON con la respuesta
    echo "{"
    echo "  \"command\": \"$CMD\","
    echo "  \"exit_code\": $EXIT_CODE,"
    echo "  \"output\": \"$(echo "$OUTPUT" | sed 's/"/\\"/g' | tr '\n' ' ')\""
    echo "}"
}

# Función para verificar comandos pendientes en el servidor
check_pending_commands() {
    # Verificar si tenemos un token de autenticación
    if [[ ! -f "$TOKEN_FILE" ]]; then
        echo "No se encontró token de autenticación. Omitiendo verificación de comandos."
        return 0
    fi
    
    AUTH_TOKEN=$(cat "$TOKEN_FILE")
    
    echo "Verificando comandos pendientes..."
    
    # Consultar al servidor por comandos pendientes
    COMMANDS_URL="${SERVER_URL}/api/agent/commands?hostname=$(hostname)&ip=$(hostname -I | awk '{print $1}')"
    
    RESPONSE=$(curl -s -X GET \
        -H "Content-Type: application/json" \
        -H "Authorization: Bearer $AUTH_TOKEN" \
        "$COMMANDS_URL")
    
    # Comprobar si hay algún comando para ejecutar
    COMMAND_COUNT=$(echo "$RESPONSE" | jq -r '.commands | length')
    
    if [[ "$COMMAND_COUNT" -gt 0 ]]; then
        echo "Se encontraron $COMMAND_COUNT comandos pendientes."
        
        # Procesar cada comando
        for i in $(seq 0 $((COMMAND_COUNT-1))); do
            CMD_ID=$(echo "$RESPONSE" | jq -r ".commands[$i].id")
            CMD=$(echo "$RESPONSE" | jq -r ".commands[$i].command")
            
            echo "Ejecutando comando ID $CMD_ID: $CMD"
            
            # Ejecutar el comando
            RESULT=$(execute_command "$CMD")
            
            # Enviar el resultado de vuelta al servidor
            RESULT_URL="${SERVER_URL}/api/agent/command-result"
            
            curl -s -X POST \
                -H "Content-Type: application/json" \
                -H "Authorization: Bearer $AUTH_TOKEN" \
                -d "{\"command_id\": $CMD_ID, \"result\": $RESULT}" \
                "$RESULT_URL" > /dev/null
                
            echo "Resultado enviado al servidor."
        done
    else
        echo "No hay comandos pendientes."
    fi
}

# Función para registrar el agente en el servidor
register_agent() {
    echo "Registrando agente en el servidor..."
    
    # Recopilar información básica
    SYS_INFO=$(get_system_info)
    HOSTNAME=$(echo "$SYS_INFO" | jq -r .hostname)
    IP_ADDRESS=$(echo "$SYS_INFO" | jq -r .ip_address)
    MAC_ADDRESS=$(echo "$SYS_INFO" | jq -r .mac_address)
    
    # Crear JSON de registro
    REG_DATA="{"
    REG_DATA+="\"hostname\":\"$HOSTNAME\","
    REG_DATA+="\"ip_address\":\"$IP_ADDRESS\","
    REG_DATA+="\"mac_address\":\"$MAC_ADDRESS\","
    REG_DATA+="\"agent_version\":\"$AGENT_VERSION\","
    REG_DATA+="\"os_info\":$(echo "$SYS_INFO" | jq .system_info)"
    REG_DATA+="}"
    
    # Enviar solicitud de registro
    REGISTER_URL="${SERVER_URL}/api/agent/register"
    
    RESPONSE=$(curl -s -X POST \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        -d "$REG_DATA" \
        "$REGISTER_URL")
    
    # Procesar respuesta
    if [[ $? -eq 0 ]]; then
        # Extraer token si está disponible
        TOKEN=$(echo "$RESPONSE" | jq -r '.token // empty')
        AGENT_ID=$(echo "$RESPONSE" | jq -r '.agent_id // empty')
        
        if [[ -n "$TOKEN" ]]; then
            echo "$TOKEN" > "$TOKEN_FILE"
            echo "Agente registrado correctamente. Token guardado."
        fi
        
        if [[ -n "$AGENT_ID" ]]; then
            AGENT_ID="$AGENT_ID"
            echo "ID del agente: $AGENT_ID"
        fi
        
        return 0
    else
        echo "Error al registrar el agente: $RESPONSE"
        return 1
    fi
}

# Función para enviar datos al servidor
send_telemetry_data() {
    # Recopilar datos
    SYS_INFO=$(get_system_info)
    DISK_USAGE=$(get_disk_usage)
    MEM_USAGE=$(get_memory_usage)
    CPU_USAGE=$(get_cpu_usage)
    NETWORK_INFO=$(get_network_info)
    PROCESSES_INFO=$(get_top_processes)
    USERS_INFO=$(get_users_info)
    
    # Obtener token si existe
    AUTH_HEADER=""
    if [[ -f "$TOKEN_FILE" ]]; then
        AUTH_TOKEN=$(cat "$TOKEN_FILE")
        AUTH_HEADER="-H \"Authorization: Bearer $AUTH_TOKEN\""
    fi
    
    # Construir JSON con los datos
    JSON_DATA="{"
    if [[ -n "$AGENT_ID" ]]; then
        JSON_DATA+="\"agent_id\":\"$AGENT_ID\","
    fi
    JSON_DATA+="\"hostname\":$(echo "$SYS_INFO" | jq -r .hostname | jq -Rs .),"
    JSON_DATA+="\"ip_address\":$(echo "$SYS_INFO" | jq -r .ip_address | jq -Rs .),"
    JSON_DATA+="\"mac_address\":$(echo "$SYS_INFO" | jq -r .mac_address | jq -Rs .),"
    JSON_DATA+="\"status\":\"online\","
    JSON_DATA+="\"system_info\":$(echo "$SYS_INFO" | jq .system_info),"
    JSON_DATA+="\"disk_usage\":$DISK_USAGE,"
    JSON_DATA+="\"memory_usage\":$MEM_USAGE,"
    JSON_DATA+="\"cpu_usage\":$CPU_USAGE,"
    JSON_DATA+="\"network_info\":$NETWORK_INFO,"
    JSON_DATA+="\"processes\":$PROCESSES_INFO,"
    JSON_DATA+="\"users\":$USERS_INFO,"
    JSON_DATA+="\"uptime\":$(echo "$SYS_INFO" | jq -r .uptime | jq -Rs .),"
    JSON_DATA+="\"last_boot\":$(echo "$SYS_INFO" | jq -r .last_boot | jq -Rs .),"
    JSON_DATA+="\"agent_version\":\"$AGENT_VERSION\""
    JSON_DATA+="}"
    
    # Enviar datos al servidor
    API_ENDPOINT="$SERVER_URL/api/telemetry/update"
    
    echo "Enviando datos a $API_ENDPOINT"
    
    RESPONSE=$(curl -s -X POST \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
        $AUTH_HEADER \
        -d "$JSON_DATA" \
        "$API_ENDPOINT")
    
    if [[ $? -eq 0 ]]; then
        echo "Respuesta del servidor: $RESPONSE"
        return 0
    else
        echo "Error al enviar datos: $RESPONSE"
        return 1
    fi
}

# Verificar dependencias
check_dependencies() {
    MISSING_DEPS=0
    
    # Verificar jq
    if ! command -v jq &> /dev/null; then
        echo "Error: jq no está instalado. Es necesario para procesar JSON."
        echo "Instale jq con: sudo apt-get install jq (Debian/Ubuntu)"
        MISSING_DEPS=1
    fi
    
    # Verificar curl
    if ! command -v curl &> /dev/null; then
        echo "Error: curl no está instalado. Es necesario para enviar datos."
        echo "Instale curl con: sudo apt-get install curl (Debian/Ubuntu)"
        MISSING_DEPS=1
    fi
    
    # Verificar bc
    if ! command -v bc &> /dev/null; then
        echo "Error: bc no está instalado. Es necesario para cálculos."
        echo "Instale bc con: sudo apt-get install bc (Debian/Ubuntu)"
        MISSING_DEPS=1
    fi
    
    if [[ $MISSING_DEPS -ne 0 ]]; then
        echo "Por favor, instale las dependencias faltantes y vuelva a ejecutar el script."
        exit 1
    fi
}

# Función principal
main() {
    echo "=== Agente de Monitorización para Linux v$AGENT_VERSION ==="
    echo "URL del servidor: $SERVER_URL"
    echo "Intervalo de envío: $INTERVAL segundos"
    echo "Intervalo de verificación de comandos: $COMMAND_CHECK_INTERVAL segundos"
    
    # Verificar dependencias
    check_dependencies
    
    # Intentar registrar el agente
    register_agent
    
    if [[ "$RUN_ONCE" = true ]]; then
        echo "Ejecutando una sola vez..."
        
        # Recopilar y enviar datos
        if send_telemetry_data; then
            echo "Datos enviados correctamente"
        else
            echo "Error al enviar datos"
            exit 1
        fi
        
        # Verificar si hay comandos pendientes
        check_pending_commands
    else
        echo "Ejecutando en modo continuo. Presione Ctrl+C para detener."
        
        # Variables para controlar los intervalos
        LAST_TELEMETRY_TIME=0
        LAST_COMMAND_CHECK_TIME=0
        
        while true; do
            CURRENT_TIME=$(date +%s)
            TIMESTAMP=$(date +"%Y-%m-%d %H:%M:%S")
            
            # Comprobar si es momento de enviar telemetría
            if (( CURRENT_TIME - LAST_TELEMETRY_TIME >= INTERVAL )); then
                echo "[$TIMESTAMP] Recopilando datos de telemetría..."
                
                # Recopilar y enviar datos
                if send_telemetry_data; then
                    echo "[$TIMESTAMP] Datos enviados correctamente"
                else
                    echo "[$TIMESTAMP] Error al enviar datos"
                fi
                
                LAST_TELEMETRY_TIME=$CURRENT_TIME
            fi
            
            # Comprobar si es momento de verificar comandos
            if (( CURRENT_TIME - LAST_COMMAND_CHECK_TIME >= COMMAND_CHECK_INTERVAL )); then
                echo "[$TIMESTAMP] Verificando comandos pendientes..."
                
                # Verificar comandos pendientes
                check_pending_commands
                
                LAST_COMMAND_CHECK_TIME=$CURRENT_TIME
            fi
            
            # Dormir por un tiempo corto antes de la siguiente iteración
            sleep 10
        done
    fi
}

# Iniciar el agente
main 