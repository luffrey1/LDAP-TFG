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
        <h1>{{ $host->hostname }}</h1>
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
                    <div class="card-body text-center">
                        <div id="status-badge-container" class="mb-3">
                            <span class="badge badge-{{ $host->status_color }} badge-pill">
                                {{ $host->status_text }}
                            </span>
                        </div>
                        <div>
                            @if($host->last_seen)
                                <p class="mb-0"><strong>Último contacto:</strong></p>
                                <p id="last-seen-text">{{ $host->last_seen->format('d/m/Y H:i:s') }} ({{ $host->last_seen->diffForHumans() }})</p>
                            @else
                                <p id="last-seen-text" class="text-muted">Sin contacto previo</p>
                            @endif
                        </div>
                        <div class="mt-4">
                            <h6 class="text-center">Información Básica</h6>
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
                    <div class="card-body text-center">
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

                <div class="row">
                    <div class="col-md-4 col-sm-6 col-12 mb-4">
                        <div class="card text-center">
                            <div class="card-header bg-primary text-white">CPU</div>
                            <div class="card-body">
                                @if(isset($host->system_info['cpu_cores']) && isset($host->cpu_usage['percentage']))
                                    <div class="fw-bold mb-1 text-primary">Uso: {{ $host->cpu_usage['percentage'] }}% de {{ $host->system_info['cpu_cores'] }} núcleos</div>
                                @endif
                                <canvas id="gauge-cpu" width="120" height="120"></canvas>
                                <div class="mt-2 h5">
                                    {{ is_array($host->cpu_usage) && isset($host->cpu_usage['percentage']) ? $host->cpu_usage['percentage'] . '%' : ($host->cpu_usage ?? 'N/A') }}
                                </div>
                                @if(isset($host->system_info['cpu_model']))
                                    <div class="small text-muted">Modelo: {{ $host->system_info['cpu_model'] }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-6 col-12 mb-4">
                        <div class="card text-center">
                            <div class="card-header bg-warning text-white">Memoria</div>
                            <div class="card-body">
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
                    <div class="col-md-4 col-sm-6 col-12 mb-4">
                        <div class="card text-center">
                            <div class="card-header bg-success text-white">Disco</div>
                            <div class="card-body">
                                @if(isset($host->disk_usage['used']) && isset($host->disk_usage['total']))
                                    <div class="fw-bold mb-1 text-success">Usado: {{ $host->disk_usage['used'] }} GB / {{ $host->disk_usage['total'] }} GB</div>
                                @endif
                                <canvas id="gauge-disk" width="120" height="120"></canvas>
                                <div class="mt-2 h5">
                                    {{ is_array($host->disk_usage) && isset($host->disk_usage['percentage']) ? $host->disk_usage['percentage'] . '%' : ($host->disk_usage ?? 'N/A') }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 col-sm-12 col-12 mb-4">
                        <div class="card text-center">
                            <div class="card-header bg-info text-white">Uptime</div>
                            <div class="card-body">
                                <div class="display-6">{{ $host->uptime ?? 'N/A' }}</div>
                                @if($host->last_boot)
                                    <div class="text-muted small">Último arranque: {{ \Carbon\Carbon::parse($host->last_boot)->format('d/m/Y H:i:s') }}</div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 col-sm-12 col-12 mb-4">
                        <div class="card text-center">
                            <div class="card-header bg-secondary text-white">Procesos principales</div>
                            <div class="card-body">
                                @if(isset($host->system_info['processes']) && is_array($host->system_info['processes']) && count($host->system_info['processes']) > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>PID</th>
                                                <th>Nombre</th>
                                                <th>Usuario</th>
                                                <th>CPU (%)</th>
                                                <th>RAM (%)</th>
                                                <th>Estado</th>
                                                <th>Uptime</th>
                                                <th>Comando</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($host->system_info['processes'] as $proc)
                                            <tr>
                                                <td>{{ $proc['pid'] }}</td>
                                                <td>{{ $proc['name'] }}</td>
                                                <td>{{ $proc['user'] }}</td>
                                                <td>{{ $proc['cpu_percent'] }}</td>
                                                <td>{{ $proc['memory_percent'] }}</td>
                                                <td>{{ $proc['status'] }}</td>
                                                <td>{{ $proc['uptime'] }}</td>
                                                <td class="text-truncate" style="max-width:200px;">{{ $proc['cmdline'] }}</td>
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
                    <div class="col-md-12 col-sm-12 col-12 mb-4">
                        <div class="card text-center">
                            <div class="card-header bg-info text-white">Red</div>
                            <div class="card-body">
                                @if(isset($host->system_info['network_info']['interfaces']) && is_array($host->system_info['network_info']['interfaces']))
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead>
                                            <tr>
                                                <th>Interfaz</th>
                                                <th>IP</th>
                                                <th>MAC</th>
                                                <th>Estado</th>
                                                <th>RX (bytes)</th>
                                                <th>TX (bytes)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($host->system_info['network_info']['interfaces'] as $iface)
                                            <tr>
                                                <td>{{ $iface['name'] }}</td>
                                                <td>{{ $iface['ip'] }}</td>
                                                <td>{{ $iface['mac'] }}</td>
                                                <td>{{ $iface['status'] }}</td>
                                                <td>{{ $iface['rx_bytes'] }}</td>
                                                <td>{{ $iface['tx_bytes'] }}</td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-2 text-start">
                                    <strong>Gateway:</strong> {{ $host->system_info['network_info']['gateway'] ?? 'N/A' }}<br>
                                    <strong>DNS:</strong> {{ isset($host->system_info['network_info']['dns']) ? implode(', ', $host->system_info['network_info']['dns']) : 'N/A' }}
                                </div>
                                @else
                                <div class="text-muted">No hay información de red disponible.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 col-sm-12 col-12 mb-4">
                        <div class="card text-center">
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
                                                <td>{{ $svc['status'] }}</td>
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
                    <div class="col-md-12 col-sm-12 col-12 mb-4">
                        <div class="card text-center">
                            <div class="card-header bg-success text-white">Temperatura y Batería</div>
                            <div class="card-body">
                                @if(isset($host->system_info['temperatures']) && is_array($host->system_info['temperatures']) && count($host->system_info['temperatures']) > 0)
                                    <div class="mb-2 text-start">
                                        @foreach($host->system_info['temperatures'] as $sensor => $temps)
                                            <strong>{{ $sensor }}:</strong>
                                            @foreach($temps as $t)
                                                {{ $t['label'] }}: {{ $t['current'] }}°C
                                            @endforeach
                                            <br>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-muted">No hay sensores de temperatura disponibles.</div>
                                @endif
                                @if(isset($host->system_info['battery']) && is_array($host->system_info['battery']))
                                    <div class="mt-2 text-start">
                                        <strong>Batería:</strong> {{ $host->system_info['battery']['percent'] ?? 'N/A' }}% 
                                        ({{ $host->system_info['battery']['plugged'] ? 'Conectado' : 'No conectado' }})
                                    </div>
                                @else
                                    <div class="text-muted">No hay información de batería disponible.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 col-sm-12 col-12 mb-4">
                        <div class="card text-center">
                            <div class="card-header bg-info text-white">Hardware</div>
                            <div class="card-body text-start">
                                @if(isset($host->system_info['platform']))
                                    <strong>Plataforma:</strong> {{ $host->system_info['platform'] }}<br>
                                    <strong>OS:</strong> {{ $host->system_info['os'] }}<br>
                                    <strong>Versión OS:</strong> {{ $host->system_info['os_version'] }}<br>
                                    <strong>CPU:</strong> {{ $host->system_info['cpu_model'] }}<br>
                                    <strong>Núcleos físicos:</strong> {{ $host->system_info['cpu_cores'] }}<br>
                                    <strong>Núcleos lógicos:</strong> {{ $host->system_info['cpu_threads'] }}<br>
                                    <strong>RAM total:</strong> {{ $host->system_info['memory_total'] }}<br>
                                    <strong>Disco total:</strong> {{ $host->system_info['disk_total'] }}<br>
                                    <strong>Modelo equipo:</strong> {{ $host->system_info['model'] ?? 'N/A' }}<br>
                                    <strong>Serial:</strong> {{ $host->system_info['serial'] ?? 'N/A' }}<br>
                                @else
                                    <div class="text-muted">No hay información de hardware disponible.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- TERMINAL SSH REAL (solo en la cabecera ahora) --}}
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
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [value ?? 0, 100 - (value ?? 0)],
                    backgroundColor: [color, '#e9ecef'],
                    borderWidth: 0
                }],
                labels: [label, 'Libre']
            },
            options: {
                cutout: '75%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        enabled: true,
                        callbacks: {
                            label: function(context) {
                                if (context.dataIndex === 0 && tooltipText) {
                                    return tooltipText;
                                }
                                return null;
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: label,
                        font: { size: 16 }
                    },
                    datalabels: { display: false }
                },
                circumference: 180,
                rotation: 270
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
    $('#btn-refresh-agent').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Comprobando...');
        $.ajax({
            url: "{{ route('monitor.ping', ['id' => $host->id]) }}",
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                // Actualizar el recuadro de estado del agente
                if (response.status === 'online') {
                    $('#agent-status-text').text('Activo');
                    btn.closest('.alert').removeClass('alert-danger').addClass('alert-success');
                } else {
                    $('#agent-status-text').text('Inactivo');
                    btn.closest('.alert').removeClass('alert-success').addClass('alert-danger');
                }
                if (response.last_seen) {
                    $('#agent-last-seen').text('(Último dato: ' + response.last_seen + ')');
                }
                btn.prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Comprobar ahora');
                // Opcional: recargar la página para actualizar los gráficos
                location.reload();
            },
            error: function() {
                btn.prop('disabled', false).html('<i class="fas fa-sync-alt"></i> Comprobar ahora');
                toastr.error('Error al comprobar el estado del agente');
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
