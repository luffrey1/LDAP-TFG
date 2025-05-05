#!/bin/bash
# Agente de Monitorización para Linux
# Recopila información del sistema y la envía al servidor de monitorización
# Uso: bash MonitorAgent.sh -s "http://tuservidor.com"

# Parámetros por defecto
SERVER_URL="http://localhost:8000"
INTERVAL=300  # Intervalo de envío en segundos (por defecto 5 minutos)
RUN_ONCE=false
AGENT_VERSION="1.0.0"

# Función para mostrar ayuda
usage() {
    echo "Uso: $0 [opciones]"
    echo "Opciones:"
    echo "  -s, --server URL   URL del servidor de monitoreo (por defecto: http://localhost:8000)"
    echo "  -i, --interval N   Intervalo de envío en segundos (por defecto: 300)"
    echo "  -o, --once         Ejecutar una sola vez y terminar"
    echo "  -h, --help         Mostrar esta ayuda"
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
    
    # Obtener dirección MAC
    INTERFACE=$(ip route | grep default | awk '{print $5}')
    MAC_ADDRESS=$(cat /sys/class/net/${INTERFACE}/address 2>/dev/null || echo "00:00:00:00:00:00")
    
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
    
    # Detalles adicionales
    SYSTEM_TYPE=$(uname -m)
    
    # Crear el JSON
    echo "{"
    echo "  \"hostname\": \"$HOSTNAME\","
    echo "  \"ip_address\": \"$IP_ADDRESS\","
    echo "  \"mac_address\": \"$MAC_ADDRESS\","
    echo "  \"system_info\": {"
    echo "    \"os_name\": \"$OS_NAME\","
    echo "    \"os_version\": \"$OS_VERSION\","
    echo "    \"cpu_model\": \"$CPU_MODEL\","
    echo "    \"cpu_cores\": $CPU_CORES,"
    echo "    \"total_ram_gb\": $TOTAL_RAM_GB,"
    echo "    \"details\": {"
    echo "      \"system_type\": \"$SYSTEM_TYPE\","
    echo "      \"architecture\": \"$(uname -m)\","
    echo "      \"processor\": \"$CPU_MODEL\","
    echo "      \"processor_cores\": $CPU_CORES"
    echo "    }"
    echo "  },"
    echo "  \"last_boot\": \"$LAST_BOOT\""
    echo "}"
}

# Función para obtener uso de disco
get_disk_usage() {
    # Obtener información de discos
    echo "{"
    echo "  \"drives\": {"
    
    df -h | grep '^/dev/' | while read -r line; do
        MOUNT=$(echo $line | awk '{print $6}')
        TOTAL=$(echo $line | awk '{print $2}' | sed 's/G//')
        USED=$(echo $line | awk '{print $3}' | sed 's/G//')
        FREE=$(echo $line | awk '{print $4}' | sed 's/G//')
        PERCENT=$(echo $line | awk '{print $5}' | sed 's/%//')
        
        echo "    \"$MOUNT\": {"
        echo "      \"total_gb\": $TOTAL,"
        echo "      \"free_gb\": $FREE,"
        echo "      \"used_gb\": $USED,"
        echo "      \"used_percent\": $PERCENT"
        if [[ $(df -h | grep '^/dev/' | wc -l) -eq 1 || $(echo $line | awk '{print $6}') == "/" ]]; then
            echo "    }"
        else
            echo "    },"
        fi
    done
    
    echo "  },"
    
    # Totales
    TOTAL_SIZE=$(df -h --total | grep 'total' | awk '{print $2}' | sed 's/G//')
    TOTAL_USED=$(df -h --total | grep 'total' | awk '{print $3}' | sed 's/G//')
    TOTAL_FREE=$(df -h --total | grep 'total' | awk '{print $4}' | sed 's/G//')
    TOTAL_PERCENT=$(df -h --total | grep 'total' | awk '{print $5}' | sed 's/%//')
    
    echo "  \"total\": $TOTAL_SIZE,"
    echo "  \"free\": $TOTAL_FREE,"
    echo "  \"used\": $TOTAL_USED,"
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
    
    echo "{"
    echo "  \"total\": $TOTAL_MEM,"
    echo "  \"free\": $FREE_MEM,"
    echo "  \"used\": $USED_MEM,"
    echo "  \"used_percent\": $USED_PERCENT"
    echo "}"
}

# Función para obtener uso de CPU
get_cpu_usage() {
    # Obtener uso de CPU
    CPU_USAGE=$(top -bn1 | grep "Cpu(s)" | sed "s/.*, *\([0-9.]*\)%* id.*/\1/" | awk '{print 100 - $1}')
    
    echo "{"
    echo "  \"percent\": $CPU_USAGE"
    echo "}"
}

# Función para enviar datos al servidor
send_telemetry_data() {
    # Recopilar datos
    SYS_INFO=$(get_system_info)
    DISK_USAGE=$(get_disk_usage)
    MEM_USAGE=$(get_memory_usage)
    CPU_USAGE=$(get_cpu_usage)
    
    # Construir JSON con los datos
    JSON_DATA="{"
    JSON_DATA+="\"hostname\":$(echo "$SYS_INFO" | jq -r .hostname | jq -Rs .),"
    JSON_DATA+="\"ip_address\":$(echo "$SYS_INFO" | jq -r .ip_address | jq -Rs .),"
    JSON_DATA+="\"mac_address\":$(echo "$SYS_INFO" | jq -r .mac_address | jq -Rs .),"
    JSON_DATA+="\"status\":\"online\","
    JSON_DATA+="\"system_info\":$(echo "$SYS_INFO" | jq .system_info),"
    JSON_DATA+="\"disk_usage\":$DISK_USAGE,"
    JSON_DATA+="\"memory_usage\":$MEM_USAGE,"
    JSON_DATA+="\"cpu_usage\":$CPU_USAGE,"
    JSON_DATA+="\"last_boot\":$(echo "$SYS_INFO" | jq -r .last_boot | jq -Rs .)"
    JSON_DATA+="}"
    
    # Enviar datos al servidor
    API_ENDPOINT="$SERVER_URL/api/telemetry/update"
    
    echo "Enviando datos a $API_ENDPOINT"
    
    RESPONSE=$(curl -s -X POST \
        -H "Content-Type: application/json" \
        -H "Accept: application/json" \
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
    
    # Verificar dependencias
    check_dependencies
    
    if [[ "$RUN_ONCE" = true ]]; then
        echo "Ejecutando una sola vez..."
        
        # Recopilar y enviar datos
        if send_telemetry_data; then
            echo "Datos enviados correctamente"
        else
            echo "Error al enviar datos"
            exit 1
        fi
    else
        echo "Ejecutando en modo continuo. Presione Ctrl+C para detener."
        
        while true; do
            TIMESTAMP=$(date +"%Y-%m-%d %H:%M:%S")
            echo "[$TIMESTAMP] Recopilando datos..."
            
            # Recopilar y enviar datos
            if send_telemetry_data; then
                echo "[$TIMESTAMP] Datos enviados correctamente"
            else
                echo "[$TIMESTAMP] Error al enviar datos"
            fi
            
            # Esperar el intervalo especificado
            echo "Esperando $INTERVAL segundos hasta la próxima actualización..."
            sleep $INTERVAL
        done
    fi
}

# Iniciar el agente
main 