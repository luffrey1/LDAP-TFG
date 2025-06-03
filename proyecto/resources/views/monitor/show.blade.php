{{-- resources/views/monitor/show.blade.php - Terminal SSH moderna con WebSockets --}}
@extends('layouts.dashboard')

@section('title', 'Detalles del Host: ' . $host->hostname)

@section('css')
    {{-- Terminal CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/xterm@5.3.0/css/xterm.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/xterm-addon-fit@0.8.0/lib/xterm-addon-fit.css" />
@endsection

@section('content')
<div class="alert alert-info" style="font-size:1.15em; font-weight:bold; border:2px solid #0056b3; background:#e9f5ff; color:#003366; margin-bottom:24px;">
    <i class="fab fa-github"></i> Para enviar o ejecutar scripts en los hosts, <span style="color:#0056b3;">usa siempre un repositorio <b>GitHub</b>:</span><br>
    <span style="font-size:0.95em; color:#222;">Ejecuta <code>git clone https://github.com/tuusuario/tu-repo.git</code> o <code>git pull</code> desde la terminal SSH web.<br>
    <b>¡No se pueden enviar scripts automáticamente desde Laravel!</b></span>
</div>
<section class="section">
    <div class="section-header">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">{{ $host->hostname }}</h1>
            <div>
                <button id="refreshBtn" class="btn btn-primary">
                    <i class="fas fa-sync-alt"></i> Comprobar ahora
                </button>
            </div>
        </div>
        <div class="section-header-breadcrumb d-flex align-items-center">
            <div class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></div>
            <div class="breadcrumb-item"><a href="{{ route('monitor.index') }}">Monitoreo</a></div>
            <div class="breadcrumb-item">{{ $host->hostname }}</div>
            <!-- Botón SSH a la derecha del nombre -->
            @if(\App\Models\SistemaConfig::obtenerConfig('modulo_ssh_activo', true))
            <button type="button" class="btn btn-primary btn-icon ms-3" id="open-ssh-terminal-header" title="Abrir terminal SSH">
                <i class="fas fa-terminal"></i> SSH
            </button>
            @endif
        </div>
    </div>

    <div class="section-body">
        <div class="row">
            {{-- Columna Izquierda: Información del host --}}
            <div class="col-12 col-md-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4>Información del Host</h4>
                    </div>
                    <div class="card-body text-center text-white">
                        <div id="status-badge-container" class="mb-3">
                            <span class="badge badge-{{ $host->status_color }} badge-pill">
                                {{ $host->status_text }}
                            </span>
                        </div>
                        <div>
                            @if($host->last_seen)
                                <p class="mb-0 text-white"><strong>Último contacto:</strong></p>
                                <p id="last-seen-text text-white">{{ $host->last_seen->format('d/m/Y H:i:s') }} ({{ $host->last_seen->diffForHumans() }})</p>
                            @else
                                <p id="last-seen-text text-white" class="text-white">Sin contacto previo</p>
                            @endif
                        </div>
                        <div class="mt-4">
                            <h6 class="text-center text-white">Información Básica</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <tr><th style="width: 40%;">Hostname</th><td id="info-hostname">{{ $host->hostname }}</td></tr>
                                    <tr><th>Dirección IP</th><td id="info-ip_address">{{ $host->ip_address }}</td></tr>
                                    <tr><th>MAC Address</th><td id="info-mac_address">{{ $host->mac_address ?? 'No disponible' }}</td></tr>
                                    <tr><th>Descripción</th><td id="info-description">{{ $host->description ?? 'Sin descripción' }}</td></tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Información del sistema en tarjeta --}}
                <div class="card mb-4">
                    <div class="card-header bg-info text-white"><i class="fas fa-desktop me-2"></i> Sistema</div>
                    <div class="card-body text-center text-white">
                        <div><strong>OS:</strong> {{ $host->system_info['os'] ?? 'N/A' }}</div>
                        <div><strong>CPU:</strong> {{ $host->system_info['cpu_model'] ?? 'N/A' }}</div>
                        <div><strong>RAM:</strong> {{ $host->system_info['memory_total'] ?? 'N/A' }}</div>
                        <div><strong>Disco:</strong> {{ $host->system_info['disk_total'] ?? 'N/A' }}</div>
                        @php
                            $usuarios = $host->users;
                            if (!is_array($usuarios) || count($usuarios) === 0) {
                                $usuarios = $host->system_info['users'] ?? [];
                            }
                        @endphp
                        @if(is_array($usuarios) && count($usuarios) > 0)
                        <div class="card mb-4">
                            <div class="card-header">
                                <h4><i class="fas fa-users mr-2"></i> Usuarios conectados</h4>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Usuario</th>
                                                <th>Terminal</th>
                                                <th>Desde</th>
                                                <th>Hora de login</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($usuarios as $user)
                                            <tr>
                                                <td>{{ $user['username'] ?? 'N/A' }}</td>
                                                <td>{{ $user['terminal'] ?? 'N/A' }}</td>
                                                <td>{{ $user['from'] ?? 'local' }}</td>
                                                <td>{{ $user['login_time'] ?? 'N/A' }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            
            {{-- Columna Derecha: Métricas y Terminal --}}
            <div class="col-12 col-md-8">
                @php
                    $isAgentActive = false;
                    $lastSeen = $host->last_seen;
                    if ($lastSeen) {
                        $isAgentActive = \Carbon\Carbon::parse($lastSeen)->gt(now()->subMinutes(10));
                    }
                @endphp

                <div class="mb-4">
                    <div class="alert {{ $isAgentActive ? 'alert-success' : 'alert-danger' }} d-flex align-items-center justify-content-between" role="alert" style="font-size:1.1em;">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-{{ $isAgentActive ? 'check-circle' : 'times-circle' }} me-2"></i>
                            <div>
                                <strong>Agente de telemetría: </strong>
                                <span id="agent-status-text">{{ $isAgentActive ? 'Activo' : 'Inactivo' }}</span>
                                @if($lastSeen)
                                    <span class="ms-2 text-muted small" id="agent-last-seen">(Último dato: {{ \Carbon\Carbon::parse($lastSeen)->diffForHumans() }})</span>
                                @endif
                            </div>
                        </div>
                        <button id="btn-refresh-agent" class="btn btn-outline-primary btn-sm ms-3" title="Comprobar ahora">
                            <i class="fas fa-sync-alt"></i> Comprobar ahora
                        </button>
                    </div>
                </div>

                {{-- Primera fila: CPU, Memoria y Disco --}}
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card text-center h-100">
                            <div class="card-header bg-primary text-white">CPU</div>
                            <div class="card-body text-white">
                                @if(isset($host->system_info['cpu_cores']) && isset($host->cpu_usage['percentage']))
                                    <div class="fw-bold mb-1 text-primary">Uso: {{ $host->cpu_usage['percentage'] }}% de {{ $host->system_info['cpu_cores'] }} núcleos</div>
                                @endif
                                <canvas id="gauge-cpu" width="120" height="120"></canvas>
                                <div class="mt-2 h5">
                                    {{ is_array($host->cpu_usage) && isset($host->cpu_usage['percentage']) ? $host->cpu_usage['percentage'] . '%' : ($host->cpu_usage ?? 'N/A') }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center h-100">
                            <div class="card-header bg-warning text-white">Memoria</div>
                            <div class="card-body text-white">
                                @if(isset($host->memory_usage['used']) && isset($host->memory_usage['total']))
                                    <div class="fw-bold mb-1 text-warning">Usado: {{ $host->memory_usage['used'] }} MB / {{ $host->memory_usage['total'] }} MB</div>
                                @endif
                                <canvas id="gauge-mem" width="120" height="120"></canvas>
                                <div class="mt-2 h5">
                                    {{ is_array($host->memory_usage) && isset($host->memory_usage['percentage']) ? $host->memory_usage['percentage'] . '%' : ($host->memory_usage ?? 'N/A') }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center h-100">
                            <div class="card-header bg-success text-white">Disco</div>
                            <div class="card-body text-white">
                                @if(isset($host->disk_usage['used']) && isset($host->disk_usage['total']))
                                    <div class="fw-bold mb-1 text-white">Total: {{ $host->disk_usage['used'] }} GB / {{ $host->disk_usage['total'] }} GB</div>
                                @endif
                                <canvas id="gauge-disk" width="120" height="120"></canvas>
                                <div class="mt-2 h5 text-white">
                                    {{ is_array($host->disk_usage) && isset($host->disk_usage['percentage']) ? $host->disk_usage['percentage'] . '%' : ($host->disk_usage ?? 'N/A') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Segunda fila: Discos Individuales y Temperatura --}}
                <div class="row mb-4">
                    <div class="col-md-8">
                        <div class="card text-center h-100">
                            <div class="card-header bg-success text-white">Discos Individuales</div>
                            <div class="card-body text-white">
                                @if(isset($host->system_info['disks']) && is_array($host->system_info['disks']))
                                    @foreach($host->system_info['disks'] as $disk)
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="text-white">{{ $disk['mount'] }}</span>
                                                <span class="text-white">{{ $disk['used'] }} GB / {{ $disk['total'] }} GB</span>
                                            </div>
                                            <div class="progress" style="height: 20px;">
                                                @php
                                                    $percentage = $disk['total'] > 0 ? ($disk['used'] / $disk['total']) * 100 : 0;
                                                    $colorClass = $percentage >= 90 ? 'bg-danger' : ($percentage >= 70 ? 'bg-warning' : 'bg-success');
                                                @endphp
                                                <div class="progress-bar {{ $colorClass }}" role="progressbar" 
                                                     style="width: {{ $percentage }}%;" 
                                                     aria-valuenow="{{ $percentage }}" 
                                                     aria-valuemin="0" 
                                                     aria-valuemax="100">
                                                    {{ number_format($percentage, 1) }}%
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="text-white">No hay información detallada de discos disponible.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center h-100">
                            <div class="card-header bg-info text-white">Temperatura</div>
                            <div class="card-body text-white">
                                @if(isset($host->system_info['temperatures']) && is_array($host->system_info['temperatures']) && count($host->system_info['temperatures']) > 0)
                                    @foreach($host->system_info['temperatures'] as $sensor => $temps)
                                        <div class="mb-2">
                                            <strong>{{ $sensor }}:</strong><br>
                                            @foreach($temps as $t)
                                                {{ $t['label'] }}: {{ $t['current'] }}°C<br>
                                            @endforeach
                                        </div>
                                    @endforeach
                                @else
                                    <div class="text-muted">No hay sensores de temperatura disponibles.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Tercera fila: Servicios y Procesos --}}
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card text-center h-100">
                            <div class="card-header bg-warning text-dark">Servicios críticos</div>
                            <div class="card-body">
                                @if(isset($host->system_info['services']) && is_array($host->system_info['services']))
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Servicio</th>
                                                    <th>Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($host->system_info['services'] as $svc)
                                                    <tr>
                                                        <td>{{ $svc['name'] }}</td>
                                                        <td>
                                                            <span class="badge {{ $svc['status'] === 'running' ? 'bg-success' : 'bg-danger' }}">
                                                                {{ $svc['status'] }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-muted">No hay información de servicios disponible.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card text-center h-100">
                            <div class="card-header bg-secondary text-white">Procesos principales</div>
                            <div class="card-body">
                                @if(isset($host->system_info['processes']) && is_array($host->system_info['processes']) && count($host->system_info['processes']) > 0)
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover">
                                            <thead>
                                                <tr>
                                                    <th>PID</th>
                                                    <th>Nombre</th>
                                                    <th>CPU (%)</th>
                                                    <th>RAM (%)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($host->system_info['processes'] as $proc)
                                                    <tr>
                                                        <td>{{ $proc['pid'] }}</td>
                                                        <td>{{ $proc['name'] }}</td>
                                                        <td>{{ $proc['cpu_percent'] }}</td>
                                                        <td>{{ $proc['memory_percent'] }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="text-muted">No hay información de procesos disponible.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Cuarta fila: Red y Hardware --}}
                <div class="row">
                    <div class="col-md-6">
                        <div class="card text-center h-100">
                            <div class="card-header bg-info text-white">Red</div>
                            <div class="card-body">
                                @if(isset($host->system_info['network_info']['interfaces']) && is_array($host->system_info['network_info']['interfaces']))
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Interfaz</th>
                                                    <th>IP</th>
                                                    <th>Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($host->system_info['network_info']['interfaces'] as $iface)
                                                    <tr>
                                                        <td>{{ $iface['name'] }}</td>
                                                        <td>{{ $iface['ip'] }}</td>
                                                        <td>
                                                            <span class="badge {{ $iface['status'] === 'up' ? 'bg-success' : 'bg-danger' }}">
                                                                {{ $iface['status'] }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="mt-2 text-start text-white">
                                        <strong>Gateway:</strong> {{ $host->system_info['network_info']['gateway'] ?? 'N/A' }}<br>
                                        <strong>DNS:</strong> {{ isset($host->system_info['network_info']['dns']) ? implode(', ', $host->system_info['network_info']['dns']) : 'N/A' }}
                                    </div>
                                @else
                                    <div class="text-muted">No hay información de red disponible.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card text-center h-100">
                            <div class="card-header bg-info text-white">Hardware</div>
                            <div class="card-body text-start text-white">
                                @if(isset($host->system_info['platform']))
                                    <strong>Plataforma:</strong> {{ $host->system_info['platform'] }}<br>
                                    <strong>OS:</strong> {{ $host->system_info['os'] }}<br>
                                    <strong>Versión OS:</strong> {{ $host->system_info['os_version'] }}<br>
                                    <strong>CPU:</strong> {{ $host->system_info['cpu_model'] }}<br>
                                    <strong>Núcleos:</strong> {{ $host->system_info['cpu_cores'] }} (físicos) / {{ $host->system_info['cpu_threads'] }} (lógicos)<br>
                                    <strong>RAM total:</strong> {{ $host->system_info['memory_total'] }}<br>
                                    <strong>Disco total:</strong> {{ $host->system_info['disk_total'] }}<br>
                                    <strong>Modelo:</strong> {{ $host->system_info['model'] ?? 'N/A' }}<br>
                                    <strong>Serial:</strong> {{ $host->system_info['serial'] ?? 'N/A' }}
                                @else
                                    <div class="text-muted">No hay información de hardware disponible.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Añadir después de la sección de Hardware --}}
                <div class="col-md-12 col-sm-12 col-12 mb-4">
                    <div class="card text-center">
                        <div class="card-header bg-info text-white">Historial de Navegación</div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-white mb-3">Últimas 10 páginas visitadas</h6>
                                    @if(isset($host->system_info['browser_history']['recent']) && count($host->system_info['browser_history']['recent']) > 0)
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Título</th>
                                                        <th>URL</th>
                                                        <th>Navegador</th>
                                                        <th>Hora</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($host->system_info['browser_history']['recent'] as $page)
                                                        <tr>
                                                            <td class="text-truncate" style="max-width:150px;">{{ $page['title'] }}</td>
                                                            <td class="text-truncate" style="max-width:150px;">{{ $page['url'] }}</td>
                                                            <td>
                                                                @if($page['browser'] == 'Firefox')
                                                                    <i class="fab fa-firefox text-orange"></i>
                                                                @else
                                                                    <i class="fab fa-chrome text-blue"></i>
                                                                @endif
                                                                {{ $page['browser'] }}
                                                            </td>
                                                            <td>{{ $page['time'] }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="text-white">No hay historial de navegación disponible.</div>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-white mb-3">Páginas sospechosas</h6>
                                    @if(isset($host->system_info['browser_history']['suspicious']) && count($host->system_info['browser_history']['suspicious']) > 0)
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Título</th>
                                                        <th>URL</th>
                                                        <th>Navegador</th>
                                                        <th>Hora</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($host->system_info['browser_history']['suspicious'] as $page)
                                                        <tr class="table-warning">
                                                            <td class="text-truncate" style="max-width:150px;">{{ $page['title'] }}</td>
                                                            <td class="text-truncate" style="max-width:150px;">{{ $page['url'] }}</td>
                                                            <td>
                                                                @if($page['browser'] == 'Firefox')
                                                                    <i class="fab fa-firefox text-orange"></i>
                                                                @else
                                                                    <i class="fab fa-chrome text-blue"></i>
                                                                @endif
                                                                {{ $page['browser'] }}
                                                            </td>
                                                            <td>{{ $page['time'] }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="text-white">No se detectaron páginas sospechosas.</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal para DELETE HOST -->
<div class="modal fade" id="deleteHostModal" tabindex="-1" role="dialog" aria-labelledby="deleteHostModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteHostModalLabel">Eliminar Host</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de eliminar el host <span id="delete-hostname"></span>?</p>
                <p class="text-danger">Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <form id="deleteHostForm" method="POST" action="">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Notificación flotante para debug -->
<div id="debug-toast" style="display:none; position:fixed; top:20px; right:20px; z-index:9999; min-width:320px; max-width:500px; background:#222; color:#fff; border-radius:8px; box-shadow:0 2px 8px #0008; padding:16px; font-size:14px; pointer-events:none; opacity:0.95;"></div>

@endsection

<!-- Dependencias Xterm.js y jQuery -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
var webssh2Host = "172.20.0.6"; // IP de tu servidor
var webssh2Port = "2222"; // Puerto de WebSSH2
$(function() {
    // Botón SSH en la cabecera
    var $sshBtnHeader = $('#open-ssh-terminal-header');
    if ($sshBtnHeader.length) {
        $sshBtnHeader.addClass('ssh-btn-long').html('<i class="fas fa-terminal"></i> Conectar SSH');
        $sshBtnHeader.on('click', function(e) {
            e.preventDefault();
            var ip = $('#info-ip_address').text().trim();
            var url = `http://${webssh2Host}:${webssh2Port}/ssh/host/${ip}?username=root`;
            var win = window.open(url, '_blank');
            if (!win) {
                alert('El navegador ha bloqueado la nueva pestaña. Permite popups para este sitio.');
            }
        });
    }
    // Botón SSH antiguo (por compatibilidad)
    var $sshBtn = $('#connect-terminal-button');
    if ($sshBtn.length) {
        $sshBtn.on('click', function(e) {
            e.preventDefault();
            var ip = $('#info-ip_address').text().trim();
            var url = `http://${webssh2Host}:${webssh2Port}/ssh/host/${ip}?username=root`;
            var win = window.open(url, '_blank');
            if (!win) {
                alert('El navegador ha bloqueado la nueva pestaña. Permite popups para este sitio.');
            }
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    function renderGauge(ctx, value, label, color, tooltipText) {
        return new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [label],
                datasets: [{
                    data: [value ?? 0],
                    backgroundColor: color,
                    borderWidth: 0
                }]
            },
            options: {
                indexAxis: 'y',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: true,
                        callbacks: {
                            label: function(context) {
                                if (tooltipText) {
                                    return tooltipText;
                                }
                                return context.raw + '%';
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: label,
                        font: { size: 16 }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100,
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
    // CPU
    renderGauge(
        document.getElementById('gauge-cpu'),
        {{ is_array($host->cpu_usage) && isset($host->cpu_usage['percentage']) ? $host->cpu_usage['percentage'] : ($host->cpu_usage ?? 0) }},
        'CPU', '#007bff',
        @if(isset($host->cpu_usage['percentage']) && isset($host->system_info['cpu_cores']))
            'Uso: {{ $host->cpu_usage['percentage'] }}% de {{ $host->system_info['cpu_cores'] }} núcleos'
        @else
            null
        @endif
    );
    // Memoria
    renderGauge(
        document.getElementById('gauge-mem'),
        {{ is_array($host->memory_usage) && isset($host->memory_usage['percentage']) ? $host->memory_usage['percentage'] : ($host->memory_usage ?? 0) }},
        'Memoria', '#ffc107',
        @if(isset($host->memory_usage['used']) && isset($host->memory_usage['total']))
            'Usado: {{ $host->memory_usage['used'] }} MB / {{ $host->memory_usage['total'] }} MB'
        @else
            null
        @endif
    );
    // Disco
    renderGauge(
        document.getElementById('gauge-disk'),
        {{ is_array($host->disk_usage) && isset($host->disk_usage['percentage']) ? $host->disk_usage['percentage'] : ($host->disk_usage ?? 0) }},
        'Disco', '#28a745',
        @if(isset($host->disk_usage['used']) && isset($host->disk_usage['total']))
            'Usado: {{ $host->disk_usage['used'] }} GB / {{ $host->disk_usage['total'] }} GB'
        @else
            null
        @endif
    );
});

$(function() {
    $('#refreshBtn').click(function() {
        const button = $(this);
        button.prop('disabled', true);
        
        // Intentar obtener datos del agente Flask
        $.ajax({
            url: `http://${window.location.hostname}:5001/telemetry`,
            method: 'GET',
            timeout: 5000,
            success: function(response) {
                if (response.success) {
                    // Enviar datos a Laravel
                    $.ajax({
                        url: '{{ route("monitor.update-telemetry") }}',
                        method: 'POST',
                        data: response.data,
                        success: function() {
                            // Actualizar estado del agente
                            $('#agent-status-text').text('Activo');
                            $('#agent-status-text').removeClass('text-danger').addClass('text-success');
                            
                            // Actualizar última vez visto
                            const now = new Date();
                            $('#agent-last-seen').text('Ahora mismo');
                            
                            // Recargar la página para actualizar los gráficos
                            location.reload();
                        },
                        error: function() {
                            alert('Error al actualizar los datos en el servidor');
                        }
                    });
                } else {
                    alert('Error al obtener datos del agente');
                }
            },
            error: function() {
                // Si falla la conexión con el agente, intentar ping
                $.get('{{ route("monitor.ping", ["id" => $host->id]) }}', function(response) {
                    if (response.status === 'online') {
                        $('#agent-status-text').text('Activo');
                        $('#agent-status-text').removeClass('text-danger').addClass('text-success');
                        $('#agent-last-seen').text('Ahora mismo');
                    } else {
                        $('#agent-status-text').text('Inactivo');
                        $('#agent-status-text').removeClass('text-success').addClass('text-danger');
                    }
                }).fail(function() {
                    $('#agent-status-text').text('Inactivo');
                    $('#agent-status-text').removeClass('text-success').addClass('text-danger');
                });
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });
});
</script>

<!-- Botón SSH mejorado -->
<style>
    .ssh-btn-long {
        padding: 0.75rem 2.5rem;
        font-size: 1.15rem;
        border-radius: 2rem;
        font-weight: 600;
        letter-spacing: 0.03em;
        box-shadow: 0 2px 8px #007bff22;
        display: flex;
        align-items: center;
        gap: 0.7em;
    }
    .ssh-btn-long i {
        font-size: 1.5em;
    }
</style>
