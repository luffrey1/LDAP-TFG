from flask import Flask, request, jsonify
import subprocess
import re
import socket
from wakeonlan import send_magic_packet

app = Flask(__name__)

# Utilidades para obtener la MAC address

def get_mac_arp_scan(ip):
    try:
        result = subprocess.run([
            'arp-scan', '--interface=eth0', ip
        ], stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, timeout=5)
        match = re.search(r'([0-9a-fA-F]{2}(:[0-9a-fA-F]{2}){5})', result.stdout)
        if match:
            return match.group(1).lower()
    except Exception as e:
        pass
    return None

def get_mac_ip_neigh(ip):
    try:
        result = subprocess.run(['ip', 'neigh', 'show', ip], stdout=subprocess.PIPE, text=True, timeout=2)
        match = re.search(r'([0-9a-fA-F]{2}(:[0-9a-fA-F]{2}){5})', result.stdout)
        if match:
            return match.group(1).lower()
    except Exception as e:
        pass
    return None

def get_mac_arp(ip):
    try:
        result = subprocess.run(['arp', '-n', ip], stdout=subprocess.PIPE, text=True, timeout=2)
        match = re.search(r'([0-9a-fA-F]{2}(:[0-9a-fA-F]{2}){5})', result.stdout)
        if match:
            return match.group(1).lower()
    except Exception as e:
        pass
    return None

def get_mac_nmap(ip):
    try:
        result = subprocess.run(['nmap', '-sn', ip], stdout=subprocess.PIPE, text=True, timeout=10)
        match = re.search(r'MAC Address: ([0-9A-Fa-f:]{17})', result.stdout)
        if match:
            return match.group(1).lower()
    except Exception as e:
        pass
    return None

def get_hostname(ip):
    try:
        return socket.gethostbyaddr(ip)[0]
    except Exception:
        return None

@app.route('/scan', methods=['GET'])
def scan_mac():
    ip = request.args.get('ip')
    if not ip:
        return jsonify({'success': False, 'error': 'No IP provided'}), 400

    # 1. Forzar actualización ARP con ping
    ping_ok = False
    try:
        result = subprocess.run(['ping', '-c', '2', '-W', '1', ip], timeout=3)
        ping_ok = (result.returncode == 0)
    except Exception:
        pass

    # 2. Buscar MAC solo con arp tras ping
    mac = None
    try:
        result = subprocess.run(['arp', '-n', ip], stdout=subprocess.PIPE, text=True, timeout=2)
        match = re.search(r'([0-9a-fA-F]{2}(:[0-9a-fA-F]{2}){5})', result.stdout)
        if match:
            mac = match.group(1).lower()
    except Exception:
        pass

    if ping_ok:
        return jsonify({'success': True, 'ip': ip, 'mac': mac})
    else:
        return jsonify({'success': False, 'ip': ip, 'error': 'No ping response'})

@app.route('/scanall', methods=['GET'])
def scan_all():
    # Puedes ajustar el rango según tu red
    network = request.args.get('network', '172.20.0.0/24')
    try:
        # Usar nmap para escanear la red y obtener IP, MAC y hostname
        result = subprocess.run([
            'nmap', '-sn', network, '-R'
        ], stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, timeout=120)
        hosts = []
        current_ip = None
        for line in result.stdout.splitlines():
            # Detectar IP y hostname (si hay)
            m = re.match(r'Nmap scan report for (.+) \((\d+\.\d+\.\d+\.\d+)\)', line)
            if m:
                hostname = m.group(1)
                current_ip = m.group(2)
                current_hostname = hostname
            else:
                m = re.match(r'Nmap scan report for (\d+\.\d+\.\d+\.\d+)', line)
                if m:
                    current_ip = m.group(1)
                    current_hostname = ''
            # Detectar MAC
            m = re.match(r'MAC Address: ([0-9A-Fa-f:]{17})', line)
            if m and current_ip:
                mac = m.group(1).lower()
                hosts.append({'ip': current_ip, 'mac': mac, 'hostname': current_hostname if 'current_hostname' in locals() else ''})
                current_ip = None
                current_hostname = ''
        return jsonify({'success': True, 'hosts': hosts})
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)})

@app.route('/wol', methods=['POST'])
def wol():
    data = request.get_json(force=True)
    mac = data.get('mac')
    broadcast = data.get('broadcast', '255.255.255.255')
    if not mac:
        return jsonify({'success': False, 'error': 'No MAC provided'}), 400
    try:
        send_magic_packet(mac, ip_address=broadcast)
        return jsonify({'success': True, 'message': f'WoL enviado a {mac} (broadcast {broadcast})'})
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)})

@app.route('/scan-hostnames', methods=['POST'])
def scan_hostnames():
    data = request.get_json(force=True)
    aula = data.get('aula')
    columnas = data.get('columnas', ['A','B','C','D','E','F'])
    filas = data.get('filas', list(range(1, 7)))
    dominio = data.get('dominio', 'tierno.es')

    resultados = []
    import threading

    def check_host(hostname, resultados):
        fqdn = f"{hostname}.{dominio}"
        try:
            ip = socket.gethostbyname(fqdn)
            # Ping rápido
            ping = subprocess.run(['ping', '-c', '1', '-W', '1', fqdn], stdout=subprocess.DEVNULL)
            if ping.returncode == 0:
                # MAC opcional
                mac = get_mac_arp(ip) or get_mac_ip_neigh(ip)
                resultados.append({'hostname': fqdn, 'ip': ip, 'mac': mac})
        except Exception:
            pass

    threads = []
    for col in columnas:
        for fila in filas:
            hostname = f"{aula}-{col}{fila}"
            t = threading.Thread(target=check_host, args=(hostname, resultados))
            t.start()
            threads.append(t)
    for t in threads:
        t.join()

    return jsonify({'success': True, 'hosts': resultados})

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000) 