import os
import platform
import psutil
import socket
import time
import subprocess
import requests
import urllib3
import shutil
import sqlite3
from flask import Flask, jsonify, request
from datetime import datetime
from threading import Thread

# Deshabilitar advertencias de SSL
urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

app = Flask(__name__)

# URL base de Laravel - Usar IP directa en lugar de DNS
LARAVEL_URL = "https://172.20.0.6"  # Cambiado a HTTPS

# --- Utilidades ---
def get_ip():
    try:
        s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
        s.connect(("8.8.8.8", 80))
        ip = s.getsockname()[0]
        s.close()
        return ip
    except Exception:
        return "127.0.0.1"

def get_mac():
    try:
        # En Linux, podemos usar /sys/class/net
        if os.name != 'nt':
            for iface in os.listdir('/sys/class/net'):
                if iface != 'lo':  # Ignorar loopback
                    try:
                        with open(f'/sys/class/net/{iface}/address') as f:
                            mac = f.read().strip()
                            if mac and mac != "00:00:00:00:00:00":
                                return mac
                    except:
                        continue
        else:
            # En Windows, usar psutil
            for iface, addrs in psutil.net_if_addrs().items():
                for addr in addrs:
                    if hasattr(psutil, 'AF_LINK') and addr.family == psutil.AF_LINK:
                        mac = addr.address
                        if mac and mac != "00:00:00:00:00:00":
                            return mac
    except Exception as e:
        print(f"Error obteniendo MAC: {str(e)}")
    return None

def get_uptime():
    return int(time.time() - psutil.boot_time())

def get_users():
    users = []
    try:
        for u in psutil.users():
            users.append({
                'username': u.name,
                'terminal': u.terminal,
                'from': u.host or 'local',
                'login_time': time.strftime('%Y-%m-%d %H:%M', time.localtime(u.started))
            })
    except Exception:
        pass
    return users

def get_processes(top_n=10):
    procs = []
    try:
        all_procs = []
        for p in psutil.process_iter(['pid', 'name', 'username', 'cpu_percent', 'memory_percent', 'status', 'create_time', 'cmdline']):
            try:
                all_procs.append({
                    'pid': p.info['pid'],
                    'name': p.info['name'],
                    'user': p.info['username'],
                    'cpu_percent': p.info['cpu_percent'],
                    'memory_percent': p.info['memory_percent'],
                    'status': p.info['status'],
                    'uptime': time.strftime('%Hh %Mm', time.gmtime(time.time() - p.info['create_time'])),
                    'cmdline': ' '.join(p.info['cmdline']) if p.info['cmdline'] else ''
                })
            except Exception:
                continue
        # Ordenar por uso de CPU y RAM
        all_procs.sort(key=lambda x: (x['cpu_percent'], x['memory_percent']), reverse=True)
        procs = all_procs[:top_n]
    except Exception:
        pass
    return procs

def get_network_info():
    info = {'interfaces': [], 'gateway': None, 'dns': []}
    try:
        # Interfaces
        for name, addrs in psutil.net_if_addrs().items():
            iface = {'name': name, 'ip': None, 'mac': None, 'status': 'down', 'rx_bytes': 0, 'tx_bytes': 0}
            for addr in addrs:
                if addr.family == socket.AF_INET:
                    iface['ip'] = addr.address
                elif hasattr(psutil, 'AF_LINK') and addr.family == psutil.AF_LINK:
                    iface['mac'] = addr.address
            # Estado y tráfico
            stats = psutil.net_if_stats().get(name)
            if stats:
                iface['status'] = 'up' if stats.isup else 'down'
            counters = psutil.net_io_counters(pernic=True).get(name)
            if counters:
                iface['rx_bytes'] = counters.bytes_recv
                iface['tx_bytes'] = counters.bytes_sent
            info['interfaces'].append(iface)
        # Gateway y DNS (solo Linux/Unix)
        if os.name != 'nt':
            try:
                with os.popen('ip route') as f:
                    for line in f:
                        if line.startswith('default'):
                            info['gateway'] = line.split()[2]
                with open('/etc/resolv.conf') as f:
                    for line in f:
                        if line.startswith('nameserver'):
                            info['dns'].append(line.split()[1])
            except Exception:
                pass
        else:
            # Windows: obtener gateway y DNS
            try:
                import wmi
                c = wmi.WMI()
                for gw in c.Win32_NetworkAdapterConfiguration(IPEnabled=True):
                    if gw.DefaultIPGateway:
                        info['gateway'] = gw.DefaultIPGateway[0]
                    if gw.DNSServerSearchOrder:
                        info['dns'] = list(gw.DNSServerSearchOrder)
            except Exception:
                pass
    except Exception:
        pass
    return info

def get_temperatures():
    temps = {}
    try:
        if hasattr(psutil, 'sensors_temperatures'):
            t = psutil.sensors_temperatures()
            for k, v in t.items():
                temps[k] = []
                for x in v:
                    if x.current > 0:  # Solo incluir temperaturas válidas
                        temps[k].append({
                            'label': x.label or k,
                            'current': round(x.current, 1),
                            'high': round(x.high, 1) if x.high else None,
                            'critical': round(x.critical, 1) if x.critical else None
                        })
    except Exception as e:
        print(f"Error obteniendo temperaturas: {str(e)}")
    return temps

def get_services():
    services = []
    try:
        # Servicios críticos para Ubuntu
        critical_services = [
            'ssh', 'apache2', 'nginx', 'mysql', 'postgresql', 'docker',
            'cron', 'systemd', 'network-manager', 'ufw', 'snapd',
            'cups', 'bluetooth', 'avahi-daemon', 'cups-browsed'
        ]
        
        for svc in critical_services:
            try:
                res = subprocess.run(['systemctl', 'is-active', svc], capture_output=True, text=True, timeout=2)
                status = res.stdout.strip()
                if status == 'active':
                    services.append({'name': svc, 'status': 'running', 'color': 'success'})
                elif status == 'inactive':
                    services.append({'name': svc, 'status': 'stopped', 'color': 'danger'})
                else:
                    services.append({'name': svc, 'status': status, 'color': 'warning'})
            except Exception:
                services.append({'name': svc, 'status': 'unknown', 'color': 'secondary'})
    except Exception as e:
        print(f"Error obteniendo servicios: {str(e)}")
    return services

def get_browser_history():
    history = {
        'recent': [],
        'suspicious': []
    }
    
    try:
        # Patrones de sitios sospechosos
        suspicious_patterns = [
            'youtube.com', 'facebook.com', 'twitter.com', 'instagram.com',
            'tiktok.com', 'reddit.com', 'pinterest.com', 'twitch.tv',
            'game', 'juego', 'play', 'gaming', 'casino', 'bet', 'poker'
        ]
        
        # Obtener historial de Firefox
        firefox_path = os.path.expanduser('~/.mozilla/firefox/*.default/places.sqlite')
        if os.path.exists(firefox_path):
            cmd = f"sqlite3 {firefox_path} 'SELECT url, title, last_visit_date FROM moz_places ORDER BY last_visit_date DESC LIMIT 10;'"
            result = subprocess.run(cmd, shell=True, capture_output=True, text=True)
            
            for line in result.stdout.split('\n'):
                if line.strip():
                    url, title, timestamp = line.split('|')
                    if not title:
                        title = url
                    
                    try:
                        visit_time = time.strftime('%Y-%m-%d %H:%M:%S', time.localtime(int(timestamp)/1000000))
                    except:
                        visit_time = 'Desconocido'
                    
                    history['recent'].append({
                        'url': url,
                        'title': title,
                        'time': visit_time,
                        'browser': 'Firefox'
                    })
                    
                    if any(pattern in url.lower() for pattern in suspicious_patterns):
                        history['suspicious'].append({
                            'url': url,
                            'title': title,
                            'time': visit_time,
                            'browser': 'Firefox'
                        })
        
        # Obtener historial de Chrome
        chrome_path = os.path.expanduser('~/.config/google-chrome/Default/History')
        if os.path.exists(chrome_path):
            # Crear una copia temporal del archivo de historial (Chrome lo bloquea mientras está en uso)
            temp_path = chrome_path + '.temp'
            subprocess.run(['cp', chrome_path, temp_path])
            
            cmd = f"sqlite3 {temp_path} 'SELECT url, title, last_visit_time FROM urls ORDER BY last_visit_time DESC LIMIT 10;'"
            result = subprocess.run(cmd, shell=True, capture_output=True, text=True)
            
            for line in result.stdout.split('\n'):
                if line.strip():
                    url, title, timestamp = line.split('|')
                    if not title:
                        title = url
                    
                    try:
                        # Chrome usa un formato de timestamp diferente
                        visit_time = time.strftime('%Y-%m-%d %H:%M:%S', time.localtime(int(timestamp)/1000000-11644473600))
                    except:
                        visit_time = 'Desconocido'
                    
                    history['recent'].append({
                        'url': url,
                        'title': title,
                        'time': visit_time,
                        'browser': 'Chrome'
                    })
                    
                    if any(pattern in url.lower() for pattern in suspicious_patterns):
                        history['suspicious'].append({
                            'url': url,
                            'title': title,
                            'time': visit_time,
                            'browser': 'Chrome'
                        })
            
            # Limpiar archivo temporal
            os.remove(temp_path)
        
        # Ordenar todo el historial por fecha
        history['recent'].sort(key=lambda x: x['time'] if x['time'] != 'Desconocido' else '', reverse=True)
        history['suspicious'].sort(key=lambda x: x['time'] if x['time'] != 'Desconocido' else '', reverse=True)
        
        # Limitar a 10 entradas más recientes
        history['recent'] = history['recent'][:10]
        
    except Exception as e:
        print(f"Error obteniendo historial: {str(e)}")
    
    return history

def get_hardware_info():
    info = {}
    try:
        info['platform'] = platform.platform()
        info['os'] = platform.system()
        info['os_version'] = platform.version()
        info['cpu_model'] = platform.processor()
        info['cpu_cores'] = psutil.cpu_count(logical=False)
        info['cpu_threads'] = psutil.cpu_count(logical=True)
        info['memory_total'] = f"{psutil.virtual_memory().total // (1024**2)} MB"
        info['disk_total'] = f"{psutil.disk_usage('/').total // (1024**3)} GB"
        info['hostname'] = socket.gethostname()
        
        # Serial/modelo (Linux) - con manejo de permisos
        if os.name != 'nt':
            try:
                # Intentar leer el modelo
                try:
                    with open('/sys/class/dmi/id/product_name') as f:
                        info['model'] = f.read().strip()
                except:
                    info['model'] = "Desconocido"
                
                # Intentar leer el serial
                try:
                    with open('/sys/class/dmi/id/product_serial') as f:
                        info['serial'] = f.read().strip()
                except:
                    info['serial'] = "Desconocido"
            except Exception as e:
                print(f"Error obteniendo información de hardware: {str(e)}")
    except Exception as e:
        print(f"Error en get_hardware_info: {str(e)}")
    return info

def get_disk_info():
    disks = []
    try:
        # Lista de puntos de montaje a ignorar
        ignore_mounts = [
            '/snap',  # Snap packages
            '/media',  # Unidades removibles
            '/dev',   # Dispositivos
            '/run',   # Sistema runtime
            '/sys',   # Sistema
            '/proc',  # Procesos
            '/tmp',   # Archivos temporales
            '/var/lib/snapd'  # Snapd
        ]
        
        for partition in psutil.disk_partitions():
            # Ignorar puntos de montaje no relevantes
            if any(partition.mountpoint.startswith(ignore) for ignore in ignore_mounts):
                continue
                
            if partition.fstype:  # Solo particiones con sistema de archivos
                try:
                    usage = psutil.disk_usage(partition.mountpoint)
                    # Solo incluir si tiene espacio total significativo (> 1GB)
                    if usage.total > (1024**3):  # 1GB en bytes
                        total = usage.total // (1024**3)  # Convertir a GB
                        used = usage.used // (1024**3)
                        free = usage.free // (1024**3)
                        percentage = usage.percent
                        
                        disks.append({
                            'mount': partition.mountpoint,
                            'device': partition.device,
                            'fstype': partition.fstype,
                            'total': total,
                            'used': used,
                            'free': free,
                            'percentage': percentage
                        })
                except Exception as e:
                    print(f"Error obteniendo información del disco {partition.mountpoint}: {str(e)}")
                    continue
    except Exception as e:
        print(f"Error en get_disk_info: {str(e)}")
    return disks

def get_memory_info():
    try:
        mem = psutil.virtual_memory()
        swap = psutil.swap_memory()
        
        memory_info = {
            'total': round(mem.total / (1024**2), 2),  # MB
            'available': round(mem.available / (1024**2), 2),  # MB
            'used': round(mem.used / (1024**2), 2),  # MB
            'free': round(mem.free / (1024**2), 2),  # MB
            'percent': mem.percent,
            'swap_total': round(swap.total / (1024**2), 2),  # MB
            'swap_used': round(swap.used / (1024**2), 2),  # MB
            'swap_free': round(swap.free / (1024**2), 2),  # MB
            'swap_percent': swap.percent
        }
        
        print(f"Memoria RAM: {memory_info['used']}MB / {memory_info['total']}MB ({memory_info['percent']}%)")
        print(f"Swap: {memory_info['swap_used']}MB / {memory_info['swap_total']}MB ({memory_info['swap_percent']}%)")
        
        return memory_info
    except Exception as e:
        print(f"Error obteniendo información de memoria: {str(e)}")
        return None

def get_telemetry_interval():
    try:
        # Obtener el intervalo configurado en Laravel
        response = requests.get(f"{LARAVEL_URL}/api/config/telemetry-interval", verify=False, timeout=5)
        if response.status_code == 200:
            data = response.json()
            return int(data.get('interval', 60)) * 60  # Convertir minutos a segundos
    except Exception as e:
        print(f"Error obteniendo intervalo de telemetría: {str(e)}")
    return 3600  # Valor por defecto: 1 hora

def send_telemetry_data():
    try:
        # Recolectar datos básicos primero
        basic_data = {
            'hostname': socket.gethostname(),
            'ip_address': get_ip(),
            'mac_address': get_mac(),
            'status': 'online',
            'uptime': f"{get_uptime() // 3600}h {(get_uptime() % 3600) // 60}m",
            'last_boot': time.strftime('%Y-%m-%d %H:%M:%S', time.localtime(psutil.boot_time())),
        }
        
        # Recolectar datos de rendimiento
        performance_data = {
            'cpu_usage': {'percentage': psutil.cpu_percent(interval=1)},
            'memory_usage': get_memory_info(),
            'disk_usage': {
                'percentage': psutil.disk_usage('/').percent,
                'used': psutil.disk_usage('/').used // (1024**3),
                'total': psutil.disk_usage('/').total // (1024**3)
            }
        }
        
        # Recolectar datos del sistema
        system_data = {
            'users': get_users(),
            'processes': get_processes(),
            'network_info': get_network_info(),
            'temperatures': get_temperatures(),
            'services': get_services(),
            'browser_history': get_browser_history(),
            'system_info': {
                **get_hardware_info(),
                'disks': get_disk_info()
            }
        }
        
        # Combinar todos los datos
        data = {**basic_data, **performance_data, **system_data}
        
        # Enviar datos al servidor Laravel
        headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }
        
        response = requests.post(
            f"{LARAVEL_URL}/api/telemetry/update",
            json=data,
            headers=headers,
            verify=False,
            timeout=5
        )
        
        if response.status_code == 200:
            print(f"Datos enviados correctamente: {time.strftime('%Y-%m-%d %H:%M:%S')}")
            return True, data
        else:
            print(f"Error al enviar datos: {response.status_code} - {response.text}")
            return False, None
            
    except Exception as e:
        print(f"Error en el envío de telemetría: {str(e)}")
        return False, None

@app.route('/telemetry', methods=['GET'])
def telemetry():
    """Endpoint para obtener datos de telemetría bajo demanda"""
    success, data = send_telemetry_data()
    if success:
        return jsonify({'success': True, 'data': data})
    return jsonify({'success': False, 'error': 'Error al obtener datos de telemetría'})

if __name__ == '__main__':
    print("Iniciando agente de telemetría...")
    print(f"URL del servidor: {LARAVEL_URL}")
    
    # Configurar SSL
    ssl_context = None
    try:
        ssl_context = ('cert.pem', 'key.pem')
        print("Certificados SSL cargados correctamente")
    except Exception as e:
        print(f"Error cargando certificados SSL: {str(e)}")
        print("Ejecutando sin SSL (no recomendado para producción)")
    
    # Iniciar el servidor Flask en un hilo separado
    def run_flask():
        try:
            print("Iniciando servidor Flask en puerto 5001...")
            app.run(host='0.0.0.0', port=5001, ssl_context=ssl_context, debug=False)
        except Exception as e:
            print(f"Error iniciando servidor Flask: {str(e)}")
    
    flask_thread = Thread(target=run_flask, daemon=True)
    flask_thread.start()
    print("Servidor Flask iniciado en segundo plano")
    
    # Esperar un momento para asegurar que el servidor Flask está corriendo
    time.sleep(2)
    
    while True:
        try:
            # Obtener el intervalo actualizado
            interval = get_telemetry_interval()
            print(f"Intervalo de telemetría: {interval//60} minutos")
            
            # Enviar datos
            success, _ = send_telemetry_data()
            if success:
                print(f"Datos enviados correctamente: {time.strftime('%Y-%m-%d %H:%M:%S')}")
            else:
                print("Error al enviar datos")
                
        except Exception as e:
            print(f"Error en el ciclo de telemetría: {str(e)}")
            
        # Esperar el intervalo configurado
        time.sleep(interval) 