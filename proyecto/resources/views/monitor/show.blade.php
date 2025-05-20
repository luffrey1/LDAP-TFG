{{-- resources/views/monitor/show.blade.php - Terminal SSH moderna con WebSockets --}}
@extends('layouts.dashboard')

@section('title', 'Detalles del Host: ' . $host->hostname)

@section('css')
    {{-- Terminal CSS --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/xterm@5.3.0/css/xterm.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/xterm-addon-fit@0.8.0/lib/xterm-addon-fit.css" />
    <style>
        /* Estilos para terminal */
        #terminal-container {
            width: 100%;
            height: 400px;
            min-height: 300px;
            background-color: #300a24 !important; /* Color de fondo Ubuntu */
            border-radius: 8px;
            padding: 0;
            overflow: hidden;
            position: relative;
            margin: 0 auto;
            box-sizing: border-box;
        }
        
        /* Barra de título estilo Ubuntu */
        .terminal-titlebar {
            height: 30px;
            background-color: #3c3b37;
            display: flex;
            align-items: center;
            padding: 0 10px;
            border-radius: 8px 8px 0 0;
            user-select: none;
        }
        
        .terminal-titlebar-buttons {
            display: flex;
        }
        
        .terminal-button {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .terminal-button-close {
            background-color: #e95420;
        }
        
        .terminal-button-minimize {
            background-color: #f6c856;
            }
            
        .terminal-button-maximize {
            background-color: #5cb85c;
        }
        
        .terminal-title {
            flex-grow: 1;
            text-align: center;
            color: #fff;
            font-size: 14px;
            font-family: 'Ubuntu', sans-serif;
        }
        
        /* Barra de estado */
        .terminal-statusbar {
            height: 24px;
            background-color: #3c3b37;
            border-radius: 0 0 8px 8px;
            display: flex;
            align-items: center;
            padding: 0 10px;
            color: #ddd;
            font-size: 12px;
            font-family: monospace;
        }
        
        .connection-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            margin-right: 8px;
        }
        
        .connection-active {
            background-color: #5cb85c;
        }
        
        .connection-inactive {
            background-color: #d9534f;
        }
        
        /* Terminal básico */
        #terminal-basic {
            height: calc(100% - 54px);
            min-height: 300px;
            width: 100%;
            padding: 8px;
        }
        
        /* Spinner de carga */
        .terminal-loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }
        
        .terminal-spinner {
            border: 4px solid rgba(255, 255, 255, 0.1);
            border-left-color: #e95420;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Comandos rápidos */
        .quick-command {
            cursor: pointer;
            padding: 5px 10px;
            margin: 5px 0;
            background-color: #f8f9fa;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        
        .quick-command:hover {
            background-color: #e9ecef;
        }

        /* Scrollbar personalizado */
        .terminal .xterm-viewport::-webkit-scrollbar {
            width: 8px;
        }
        
        .terminal .xterm-viewport::-webkit-scrollbar-track {
            background: #300a24;
        }
        
        .terminal .xterm-viewport::-webkit-scrollbar-thumb {
            background: #666;
            border-radius: 4px;
        }
        
        .terminal .xterm-viewport::-webkit-scrollbar-thumb:hover {
            background: #888;
        }

        #ssh-card {
            position: relative;
            display: flex;
            flex-direction: column;
        }
        .card-body.p-0 {
            padding: 0 !important;
            display: flex;
            flex-direction: column;
            align-items: stretch;
        }

        #terminal-container canvas, #terminal-basic canvas, .xterm-canvas {
            width: 100% !important;
            height: 100% !important;
            display: block;
            max-width: 100vw;
            max-height: 100vh;
            box-sizing: border-box;
        }

        /* Estilos Ubuntu-like solo cuando la terminal está conectada */
        #terminal-container.ubuntu-terminal-active {
            background: linear-gradient(135deg, #300a24 0%, #4f2350 100%) !important;
            border-radius: 10px;
            box-shadow: 0 4px 24px #0004;
            border: 2px solid #5e2750;
        }
        .terminal-titlebar.ubuntu-terminal-active {
            background: linear-gradient(90deg, #3c3b37 0%, #5e2750 100%);
            border-radius: 10px 10px 0 0;
            font-family: 'Ubuntu', 'Fira Mono', monospace;
            font-size: 15px;
            letter-spacing: 0.5px;
        }
        .terminal-titlebar-buttons.ubuntu-terminal-active .terminal-button {
            width: 13px;
            height: 13px;
            margin-right: 0;
        }
        .terminal-titlebar-buttons.ubuntu-terminal-active {
            gap: 7px;
        }
        .terminal-title.ubuntu-terminal-active {
            font-family: 'Ubuntu', 'Fira Mono', monospace;
            font-size: 15px;
        }
        .xterm.ubuntu-terminal-active {
            font-family: 'Ubuntu Mono', 'Fira Mono', monospace !important;
            font-size: 15px !important;
            color: #e0e0e0 !important;
            background: transparent !important;
            padding: 12px 18px 12px 18px !important;
            border-radius: 0 0 10px 10px;
        }
    </style>
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
                @if(isset($host->system_info['users']))
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-users mr-2"></i> Usuarios conectados</h4>
                    </div>
                    <div class="card-body">
                        @if(count($host->system_info['users']['users'] ?? []) > 0)
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
                                        @foreach($host->system_info['users']['users'] as $user)
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
                            @if(isset($host->system_info['users']['last_login']))
                                <div class="mt-2 text-muted small">
                                    <strong>Último login:</strong> {{ $host->system_info['users']['last_login'] }}
                                </div>
                            @endif
                        @else
                            <p class="text-muted">No hay usuarios conectados actualmente.</p>
                        @endif
                    </div>
                </div>
                @endif
            </div>
            
            {{-- Columna Derecha: Métricas y Terminal --}}
            <div class="col-12 col-md-8">
                <div class="row">
                    {{-- CPU --}}
                    <div class="col-md-6 col-sm-6 col-12">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-primary"><i class="fas fa-microchip"></i></div>
                            <div class="card-wrap">
                                <div class="card-header"><h4>CPU</h4></div>
                                <div class="card-body" id="metric-cpu-usage">
                                    {{ $host->cpu_usage !== null ? $host->cpu_usage . '%' : 'N/A' }}
                                </div>
                                @if($host->cpu_usage !== null)
                                <div class="progress mb-2" style="height: 6px;">
                                    <div id="metric-cpu-progress" class="progress-bar bg-{{ $host->cpu_color }}" role="progressbar" style="width: {{ $host->cpu_usage }}%" aria-valuenow="{{ $host->cpu_usage }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                @else
                                <div class="progress mb-2" style="height: 6px;">
                                     <div id="metric-cpu-progress" class="progress-bar bg-light" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    {{-- Memoria --}}
                    <div class="col-md-6 col-sm-6 col-12">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-warning"><i class="fas fa-memory"></i></div>
                            <div class="card-wrap">
                                <div class="card-header"><h4>Memoria</h4></div>
                                <div class="card-body" id="metric-memory-usage">
                                    {{ $host->memory_usage !== null ? $host->memory_usage . '%' : 'N/A' }}
                                </div>
                                @if($host->memory_usage !== null)
                                <div class="progress mb-2" style="height: 6px;">
                                    <div id="metric-memory-progress" class="progress-bar bg-{{ $host->memory_color }}" role="progressbar" style="width: {{ $host->memory_usage }}%" aria-valuenow="{{ $host->memory_usage }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                @else
                                <div class="progress mb-2" style="height: 6px;">
                                     <div id="metric-memory-progress" class="progress-bar bg-light" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    {{-- Disco --}}
                    <div class="col-md-6 col-sm-6 col-12">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-success"><i class="fas fa-hdd"></i></div>
                            <div class="card-wrap">
                                <div class="card-header"><h4>Disco</h4></div>
                                <div class="card-body" id="metric-disk-usage">
                                    {{ $host->disk_usage !== null ? $host->disk_usage . '%' : 'N/A' }}
                                </div>
                                @if($host->disk_usage !== null)
                                <div class="progress mb-2" style="height: 6px;">
                                    <div id="metric-disk-progress" class="progress-bar bg-{{ $host->disk_color }}" role="progressbar" style="width: {{ $host->disk_usage }}%" aria-valuenow="{{ $host->disk_usage }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                @else
                                 <div class="progress mb-2" style="height: 6px;">
                                     <div id="metric-disk-progress" class="progress-bar bg-light" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    {{-- Uptime --}}
                    <div class="col-md-6 col-sm-6 col-12">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-info"><i class="fas fa-clock"></i></div>
                            <div class="card-wrap">
                                <div class="card-header"><h4>Uptime</h4></div>
                                <div class="card-body" id="metric-uptime">
                                    {{ $host->uptime ?? 'N/A' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Procesos en ejecución si están disponibles --}}
                @if(isset($host->system_info['processes']))
                <div class="card mb-4">
                    <div class="card-header">
                        <h4><i class="fas fa-tasks mr-2"></i> Principales procesos</h4>
                    </div>
                    <div class="card-body">
                        @if(count($host->system_info['processes']['processes'] ?? []) > 0)
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead>
                                        <tr>
                                            <th>PID</th>
                                            <th>Usuario</th>
                                            <th>CPU %</th>
                                            <th>Mem %</th>
                                            <th>Comando</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach(array_slice($host->system_info['processes']['processes'], 0, 5) as $process)
                                            <tr>
                                                <td>{{ $process['pid'] ?? 'N/A' }}</td>
                                                <td>{{ $process['user'] ?? 'N/A' }}</td>
                                                <td>{{ $process['cpu'] ?? '0' }}%</td>
                                                <td>{{ $process['memory'] ?? '0' }}%</td>
                                                <td><code>{{ $process['command'] ?? 'N/A' }}</code></td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @if(isset($host->system_info['processes']['total_count']))
                                <div class="mt-2 text-muted small">
                                    <strong>Total de procesos:</strong> {{ $host->system_info['processes']['total_count'] }}
                                </div>
                            @endif
                        @else
                            <p class="text-muted">No hay información de procesos disponible.</p>
                        @endif
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
<script>
$(function() {
    var $sshBtn = $('#connect-terminal-button');
    if ($sshBtn.length) {
        $sshBtn.on('click', function(e) {
            e.preventDefault();
            var ip = $('#info-ip_address').text().trim();
            var url = `http://localhost:2222/ssh/host/${ip}?username=root`;
            var win = window.open(url, '_blank');
            if (!win) {
                alert('El navegador ha bloqueado la nueva pestaña. Permite popups para este sitio.');
            }
        });
    }
});
</script>
