from flask import Flask, request, jsonify
import subprocess
import re
import socket

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

    # 1. Forzar actualización ARP
    try:
        subprocess.run(['ping', '-c', '2', '-W', '1', ip], timeout=3)
    except Exception:
        pass

    # 2. Buscar MAC con diferentes métodos
    mac = get_mac_arp_scan(ip)
    if not mac:
        mac = get_mac_ip_neigh(ip)
    if not mac:
        mac = get_mac_arp(ip)
    if not mac:
        mac = get_mac_nmap(ip)

    if mac:
        return jsonify({'success': True, 'ip': ip, 'mac': mac})
    else:
        return jsonify({'success': False, 'ip': ip, 'error': 'MAC not found'})

@app.route('/scanall', methods=['GET'])
def scan_all():
    # Puedes ajustar el rango según tu red
    network = request.args.get('network', '172.20.0.0/24')
    try:
        result = subprocess.run([
            'arp-scan', '--interface=eth0', network
        ], stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True, timeout=30)
        hosts = []
        for line in result.stdout.splitlines():
            # Formato típico: 172.20.0.10  bc:24:11:32:30:1b  SomeHost
            parts = line.strip().split()
            if len(parts) >= 2 and re.match(r'\d+\.\d+\.\d+\.\d+', parts[0]):
                ip = parts[0]
                mac = parts[1].lower()
                hostname = get_hostname(ip)
                hosts.append({'ip': ip, 'mac': mac, 'hostname': hostname or ''})
        return jsonify({'success': True, 'hosts': hosts})
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)})

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000) 