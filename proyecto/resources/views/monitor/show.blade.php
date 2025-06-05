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
        </div>
        <div class="section-header-breadcrumb d-flex align-items-center">
            <div class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></div>
            <div class="breadcrumb-item"><a href="{{ route('monitor.index') }}">Monitoreo</a></div>
            <div class="breadcrumb-item">{{ $host->hostname }}</div>
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
                            <button id="refreshBtn" class="btn btn-primary btn-block">
                                <i class="fas fa-sync-alt"></i> <span class="btn-text">Comprobar ahora</span>
                            </button>
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
                                <div style="height: 20px;">
                                    <canvas id="gauge-cpu"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center h-100">
                            <div class="card-header bg-warning text-white">Memoria</div>
                            <div class="card-body text-white">
                                @if(isset($host->memory_usage['used']) && isset($host->memory_usage['total']))
                                    <div class="fw-bold mb-1 text-warning">Usado: {{ number_format($host->memory_usage['used'], 2) }} MB / {{ number_format($host->memory_usage['total'], 2) }} MB</div>
                                @endif
                                <div style="height: 20px;">
                                    <canvas id="gauge-mem"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center h-100">
                            <div class="card-header bg-success text-white">Disco</div>
                            <div class="card-body text-white">
                                @if(isset($host->disk_usage['used']) && isset($host->disk_usage['total']))
                                    <div class="fw-bold mb-1 text-white">Total: {{ number_format($host->disk_usage['used'], 2) }} GB / {{ number_format($host->disk_usage['total'], 2) }} GB</div>
                                @endif
                                <div style="height: 20px;">
                                    <canvas id="gauge-disk"></canvas>
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
            var hostname = '{{ $host->hostname }}';
            if (!hostname) {
                alert('No se pudo obtener el hostname del host');
                return;
            }
            console.log('Conectando a hostname:', hostname);
            // URL base para la conexión SSH
            var baseUrl = `http://${webssh2Host}:${webssh2Port}`;
            var url = `${baseUrl}/ssh/host/${hostname}`;
            console.log('URL de conexión:', url);
            
            // Crear un iframe oculto para precargar los recursos
            var iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = baseUrl;
            document.body.appendChild(iframe);
            
            // Precargar Socket.IO
            var socketScript = document.createElement('script');
            socketScript.src = `${baseUrl}/ssh/socket.io/socket.io.js`;
            document.head.appendChild(socketScript);
            
            // Abrir la ventana después de un breve retraso
            setTimeout(function() {
                var win = window.open(url, '_blank', 'width=800,height=600,resizable=yes,scrollbars=yes');
                if (!win) {
                    alert('El navegador ha bloqueado la nueva ventana. Permite popups para este sitio.');
                }
                // Eliminar el iframe y el script después de un tiempo
                setTimeout(function() {
                    document.body.removeChild(iframe);
                    document.head.removeChild(socketScript);
                }, 5000);
            }, 1000);
        });
    }
    // Botón SSH antiguo (por compatibilidad)
    var $sshBtn = $('#connect-terminal-button');
    if ($sshBtn.length) {
        $sshBtn.on('click', function(e) {
            e.preventDefault();
            var hostname = '{{ $host->hostname }}';
            if (!hostname) {
                alert('No se pudo obtener el hostname del host');
                return;
            }
            console.log('Conectando a hostname:', hostname);
            // URL base para la conexión SSH
            var baseUrl = `http://${webssh2Host}:${webssh2Port}`;
            var url = `${baseUrl}/ssh/host/${hostname}`;
            console.log('URL de conexión:', url);
            
            // Crear un iframe oculto para precargar los recursos
            var iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = baseUrl;
            document.body.appendChild(iframe);
            
            // Precargar Socket.IO
            var socketScript = document.createElement('script');
            socketScript.src = `${baseUrl}/ssh/socket.io/socket.io.js`;
            document.head.appendChild(socketScript);
            
            // Abrir la ventana después de un breve retraso
            setTimeout(function() {
                var win = window.open(url, '_blank', 'width=800,height=600,resizable=yes,scrollbars=yes');
                if (!win) {
                    alert('El navegador ha bloqueado la nueva ventana. Permite popups para este sitio.');
                }
                // Eliminar el iframe y el script después de un tiempo
                setTimeout(function() {
                    document.body.removeChild(iframe);
                    document.head.removeChild(socketScript);
                }, 5000);
            }, 1000);
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
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100,
                        display: false
                    },
                    y: {
                        display: false
                    }
                },
                maintainAspectRatio: false,
                responsive: true,
                animation: false,
                layout: {
                    padding: {
                        top: 0,
                        right: 0,
                        bottom: 0,
                        left: 0
                    }
                }
            }
        });
    }
    // CPU
    renderGauge(
        document.getElementById('gauge-cpu'),
        @if(is_array($host->cpu_usage) && isset($host->cpu_usage['percentage']))
            {{ $host->cpu_usage['percentage'] }}
        @else
            {{ is_numeric($host->cpu_usage) ? $host->cpu_usage : 0 }}
        @endif,
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
        @if(is_array($host->memory_usage) && isset($host->memory_usage['percentage']))
            {{ $host->memory_usage['percentage'] }}
        @else
            {{ is_numeric($host->memory_usage) ? $host->memory_usage : 0 }}
        @endif,
        'Memoria', '#ffc107',
        @if(isset($host->memory_usage['used']) && isset($host->memory_usage['total']))
            'Usado: {{ number_format($host->memory_usage['used'], 2) }} MB / {{ number_format($host->memory_usage['total'], 2) }} MB'
        @else
            null
        @endif
    );
    // Disco
    renderGauge(
        document.getElementById('gauge-disk'),
        @if(is_array($host->disk_usage) && isset($host->disk_usage['percentage']))
            {{ $host->disk_usage['percentage'] }}
        @else
            {{ is_numeric($host->disk_usage) ? $host->disk_usage : 0 }}
        @endif,
        'Disco', '#28a745',
        @if(isset($host->disk_usage['used']) && isset($host->disk_usage['total']))
            'Usado: {{ number_format($host->disk_usage['used'], 2) }} GB / {{ number_format($host->disk_usage['total'], 2) }} GB'
        @else
            null
        @endif
    );
});

$(document).ready(function() {
    function updateStatus(status, message) {
        const statusBadge = $('#status-badge-container .badge');
        const agentStatus = $('#agent-status-text');
        const lastSeen = $('#agent-last-seen');
        const alert = $('.alert');
        
        if (status === 'success') {
            statusBadge.removeClass('badge-danger badge-warning').addClass('badge-success');
            statusBadge.text('Online');
            agentStatus.text('Activo').removeClass('text-danger').addClass('text-success');
            lastSeen.text('(Último dato: Ahora mismo)');
            alert.removeClass('alert-danger').addClass('alert-success');
        } else if (status === 'error') {
            statusBadge.removeClass('badge-success badge-warning').addClass('badge-danger');
            statusBadge.text('Offline');
            agentStatus.text('Inactivo').removeClass('text-success').addClass('text-danger');
            alert.removeClass('alert-success').addClass('alert-danger');
        }
        
        // Mostrar mensaje de estado
        const toast = $('#debug-toast');
        toast.html(message).fadeIn();
        setTimeout(() => toast.fadeOut(), 3000);
    }
    
    function refreshData() {
        const btn = $('#refreshBtn');
        const btnText = btn.find('.btn-text');
        const icon = btn.find('i');
        
        // Deshabilitar botón y mostrar loading
        btn.prop('disabled', true);
        btnText.text('Comprobando...');
        icon.addClass('fa-spin');
        
        // Obtener la IP del host
        const hostIp = '{{ $host->ip_address }}';
        console.log('Intentando conectar con el agente en:', hostIp);
        
        // Usar el proxy de Laravel para obtener datos del agente
        $.ajax({
            url: '/monitor/api/telemetry-proxy/' + hostIp,
            method: 'GET',
            timeout: 5000,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            success: function(response) {
                console.log('Respuesta del agente:', response);
                if (response.success) {
                    // Enviar datos a Laravel
                    $.ajax({
                        url: '{{ route("monitor.update-telemetry") }}',
                        method: 'POST',
                        data: JSON.stringify(response.data),
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        success: function() {
                            console.log('Datos actualizados en Laravel');
                            updateStatus('success', 'Datos actualizados correctamente');
                            setTimeout(() => location.reload(), 1000);
                        },
                        error: function(xhr, status, error) {
                            console.error('Error Laravel:', xhr.responseText);
                            updateStatus('error', 'Error al actualizar datos en el servidor: ' + error);
                        }
                    });
                } else {
                    console.error('Error en respuesta del agente:', response);
                    updateStatus('error', response.error || 'Error en la respuesta del agente');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error al conectar con el agente:', error);
                
                // Si falla, intentar ping
                $.ajax({
                    url: '{{ route("monitor.ping", ["id" => $host->id]) }}',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    success: function(pingResponse) {
                        console.log('Respuesta ping:', pingResponse);
                        if (pingResponse.status === 'online') {
                            updateStatus('success', 'Host activo');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            updateStatus('error', 'Host inactivo');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error en ping:', error);
                        updateStatus('error', 'No se pudo contactar con el host');
                    }
                });
            },
            complete: function() {
                // Restaurar botón
                btn.prop('disabled', false);
                btnText.text('Comprobar ahora');
                icon.removeClass('fa-spin');
            }
        });
    }
    
    // Asignar evento al botón
    $('#refreshBtn').click(function(e) {
        e.preventDefault();
        console.log('Botón de comprobación clickeado');
        refreshData();
    });
    
    // Actualizar automáticamente cada 5 minutos
    setInterval(refreshData, 300000);
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

    #debug-toast {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        max-width: 500px;
        background: rgba(0, 0, 0, 0.8);
        color: #fff;
        border-radius: 8px;
        padding: 15px;
        font-size: 14px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        display: none;
    }

    .badge-success { background-color: #28a745; }
    .badge-danger { background-color: #dc3545; }
    .badge-warning { background-color: #ffc107; }
</style>
