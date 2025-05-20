from flask import Flask, request, jsonify
import subprocess
import re

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

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000) 