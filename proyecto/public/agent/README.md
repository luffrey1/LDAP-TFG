# Agentes de Monitoreo de Equipos

## Agente para Linux (Bash)

### Requisitos
- Linux (Debian, Ubuntu, CentOS, etc.)
- Bash
- Paquetes: jq, curl, bc

### Instalación de dependencias
```bash
# En Debian/Ubuntu
sudo apt-get update
sudo apt-get install -y jq curl bc

# En CentOS/RHEL
sudo yum install -y jq curl bc
```

### Uso básico
```bash
# Dar permisos de ejecución
chmod +x MonitorAgent.sh

# Ejecutar una sola vez
./MonitorAgent.sh --once

# Enviar a un servidor específico
./MonitorAgent.sh --server "http://miservidor.com"

# Cambiar el intervalo de envío a 60 segundos
./MonitorAgent.sh --interval 60
```

### Configuración como servicio
Para configurar el agente como un servicio en Linux (systemd):

1. Crear archivo de servicio
```bash
sudo nano /etc/systemd/system/monitor-agent.service
```

2. Añadir configuración
```
[Unit]
Description=Monitor Agent Service
After=network.target

[Service]
ExecStart=/bin/bash /ruta/a/MonitorAgent.sh --server "http://miservidor.com"
Restart=always
User=root
Group=root
Environment=PATH=/usr/bin:/usr/local/bin
WorkingDirectory=/ruta/a/directorio

[Install]
WantedBy=multi-user.target
```

3. Activar y arrancar el servicio
```bash
sudo systemctl daemon-reload
sudo systemctl enable monitor-agent
sudo systemctl start monitor-agent
```

## Información recopilada

Los agentes recopilan la siguiente información:
- Nombre del host
- Dirección IP y MAC
- Sistema operativo y versión
- Información de CPU (modelo, núcleos)
- Memoria RAM (total, utilizada, libre)
- Espacio en disco (total, utilizado, libre, por unidad)
- Uso actual de CPU
- Fecha del último arranque

Esta información se envía al servidor en formato JSON mediante una solicitud HTTP POST al endpoint `/api/telemetry/update`. 