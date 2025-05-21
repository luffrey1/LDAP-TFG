#!/usr/bin/env python3
import requests
import socket
import platform
import psutil
import time
import uuid
import os
import getpass
import subprocess

# CONFIGURA AQUÍ la URL de tu Laravel
LARAVEL_URL = "http://172.20.0.6:8000/api/telemetry/update"

def get_mac():
    # Obtiene la MAC real de la interfaz principal
    for iface, addrs in psutil.net_if_addrs().items():
        for addr in addrs:
            if addr.family == psutil.AF_LINK:
                mac = addr.address
                if mac and mac != "00:00:00:00:00:00":
                    return mac
    return None

def get_ip():
    # Obtiene la IP principal (no localhost)
    s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    try:
        s.connect(("8.8.8.8", 80))
        ip = s.getsockname()[0]
    except Exception:
        ip = "127.0.0.1"
    finally:
        s.close()
    return ip

def get_uptime():
    return int(time.time() - psutil.boot_time())

def get_users():
    users = []
    try:
        output = subprocess.check_output(['who'], universal_newlines=True)
        for line in output.strip().split('\n'):
            if not line.strip():
                continue
            parts = line.split()
            if len(parts) >= 4:
                username = parts[0]
                terminal = parts[1]
                login_time = ' '.join(parts[2:4])
                from_host = parts[4] if len(parts) > 4 else 'local'
                users.append({
                    'username': username,
                    'terminal': terminal,
                    'from': from_host.strip('()'),
                    'login_time': login_time
                })
    except Exception as e:
        pass
    return users

def main():
    hostname = socket.gethostname()
    ip_address = get_ip()
    mac_address = get_mac()
    status = "online"
    uptime = get_uptime()

    # CPU
    cpu_percent = psutil.cpu_percent(interval=1)
    # Memoria
    mem = psutil.virtual_memory()
    mem_percent = mem.percent
    # Disco
    disk = psutil.disk_usage('/')
    disk_percent = disk.percent

    # Usuarios conectados
    users = get_users()

    # Datos para Laravel
    data = {
        "hostname": hostname,
        "ip_address": ip_address,
        "mac_address": mac_address,
        "status": status,
        "uptime": f"{uptime // 3600}h {(uptime % 3600) // 60}m",
        "cpu_usage": {"percentage": cpu_percent},
        "memory_usage": {"percentage": mem_percent},
        "disk_usage": {"percentage": disk_percent},
        "users": users,
        "system_info": {
            "os": platform.platform(),
            "cpu_model": platform.processor(),
            "memory_total": f"{mem.total // (1024**2)} MB",
            "disk_total": f"{disk.total // (1024**3)} GB"
        }
    }

    try:
        resp = requests.post(LARAVEL_URL, json=data, timeout=5)
        print("Respuesta Laravel:", resp.status_code, resp.text)
    except Exception as e:
        print("Error enviando telemetría:", e)

if __name__ == "__main__":
    main()