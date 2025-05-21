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
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></div>
            <div class="breadcrumb-item"><a href="{{ route('monitor.index') }}">Monitoreo</a></div>
            <div class="breadcrumb-item">{{ $host->hostname }}</div>
        </div>
    </div>

    <div class="section-body">
        <div class="row">
            {{-- Columna Izquierda: Información del host --}}
            <div class="col-12 col-md-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4>Información del Host</h4>
                        <div class="card-header-action">
                            <button class="btn btn-icon btn-primary btn-sm ping-button" data-id="{{ $host->id }}">
                                <i class="fas fa-network-wired"></i> Ping
                            </button>
                        </div>
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
                
                <div class="card">
                    <div class="card-header"><h4>Acciones</h4></div>
                    <div class="card-body">
                        <div class="buttons">
                            <a href="{{ route('monitor.edit', $host->id) }}" class="btn btn-warning btn-icon icon-left btn-block mb-2">
                                <i class="fas fa-edit"></i> Editar Host
                            </a>
                            <button class="btn btn-danger btn-icon icon-left btn-block delete-host-button" data-id="{{ $host->id }}" data-hostname="{{ $host->hostname }}">
                                <i class="fas fa-trash"></i> Eliminar Host
                            </button>
                            @if(!empty($host->mac_address))
                            <a href="{{ route('monitor.wol', $host->id) }}" class="btn btn-success btn-icon icon-left btn-block mt-2">
                                <i class="fas fa-power-off"></i> Encender (WOL)
                            </a>
                            @endif
                            <!-- Botón para abrir la terminal SSH real en webssh2 -->
                            <button type="button" class="btn btn-primary btn-icon icon-left btn-block mt-2" id="open-ssh-terminal">
                                <i class="fas fa-terminal"></i> Terminal SSH
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Usuarios conectados si están disponibles -->
                @if(is_array($host->users) && count($host->users) > 0)
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
                                        <th>Tiempo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($host->users as $user)
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
                                <canvas id="gauge-cpu" width="120" height="120"></canvas>
                                <div class="mt-2 h5">
                                    {{ is_array($host->cpu_usage) && isset($host->cpu_usage['percentage']) ? $host->cpu_usage['percentage'] . '%' : ($host->cpu_usage ?? 'N/A') }}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 col-sm-6 col-12 mb-4">
                        <div class="card text-center">
                            <div class="card-header bg-warning text-white">Memoria</div>
                            <div class="card-body">
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
                </div>
                
                {{-- Información del sistema si está disponible --}}
                @if(is_array($host->system_info) && count($host->system_info) > 0)
                <div class="card mb-4">
                    <div class="card-header">
                        <h4><i class="fas fa-desktop mr-2"></i> Información del sistema</h4>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            @foreach($host->system_info as $key => $value)
                                <li class="list-group-item">
                                    <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong>
                                    @if(is_array($value))
                                        <pre class="mb-0">{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                    @else
                                        {{ $value }}
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif
                
                {{-- TERMINAL SSH REAL --}}
                <div class="card mt-4">
                    <div class="card-header d-flex align-items-center" style="padding: 0; background: none; border: none;">
                        <div class="terminal-titlebar" id="ubuntuTitleBar" style="width: 100%;">
                            <div class="terminal-titlebar-buttons">
                                <div class="terminal-button terminal-button-close"></div>
                                <div class="terminal-button terminal-button-minimize"></div>
                                <div class="terminal-button terminal-button-maximize"></div>
                            </div>
                            <span class="terminal-title">ubuntu@{{ $host->hostname }}: ~</span>
                        </div>
                        <span id="connectionStatus" class="connection-indicator connection-inactive ms-3"></span>
                        <span id="connectionText" class="ms-2">Desconectado</span>
                        <button id="connect-terminal-button" class="btn btn-primary ms-auto"><i class="fas fa-terminal"></i> Conectar</button>
                    </div>
                    <!-- Tabs -->
                    <ul class="nav nav-tabs" id="terminalTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="terminal-tab" data-toggle="tab" data-target="#terminal-pane" type="button" role="tab" aria-controls="terminal-pane" aria-selected="true">Terminal</button>
                        </li>
                    </ul>
                    <div class="tab-content" id="terminalTabsContent">
                        <div class="tab-pane fade show active" id="terminal-pane" role="tabpanel" aria-labelledby="terminal-tab">
                            <div class="card-body p-0" style="background: #1e1e1e;">
                                <div id="terminal-container"></div>
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
    function renderGauge(ctx, value, label, color) {
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
                    tooltip: { enabled: false },
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
    renderGauge(document.getElementById('gauge-cpu'), {{ $host->cpu_usage ?? '0' }}, 'CPU', '#007bff');
    // Memoria
    renderGauge(document.getElementById('gauge-mem'), {{ $host->memory_usage ?? '0' }}, 'Memoria', '#ffc107');
    // Disco
    renderGauge(document.getElementById('gauge-disk'), {{ $host->disk_usage ?? '0' }}, 'Disco', '#28a745');
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
