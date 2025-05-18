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
    </style>
@endsection

@section('content')
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
                    <div class="card-header d-flex align-items-center">
                        <span class="terminal-title" style="font-family: monospace; font-size: 1.1em;">Terminal SSH</span>
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
                                <div id="terminal-container" style="width: 100%; height: 400px;"></div>
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
<script src="https://cdn.jsdelivr.net/npm/xterm@5.3.0/lib/xterm.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xterm-addon-fit@0.8.0/lib/xterm-addon-fit.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xterm-addon-search@0.13.0/lib/xterm-addon-search.min.js"></script>
<script>
// Toda la lógica de la terminal y eventos DOM dentro de DOMContentLoaded
window.addEventListener('DOMContentLoaded', function() {
    // Obtener elementos DOM de forma segura
    const connectionStatus = document.getElementById('connectionStatus');
    const connectionText = document.getElementById('connectionText');
    const connectBtn = document.getElementById('connect-terminal-button');
    const terminalContainer = document.getElementById('terminal-container');
    if (!connectionStatus || !connectionText || !connectBtn || !terminalContainer) {
        console.error('No se encontraron los elementos necesarios para la terminal.');
        return;
    }

    // --- Terminal SSH Real con WebSocket y xterm.js ---
    window.terminal = null;
    window.fitAddon = null;
    window.searchAddon = null;
    let isConnected = false;
    let sessionId = null;
    let currentPrompt = '';
    let currentDirectory = '~';
    let commandBuffer = '';
    let commandHistory = [];
    let historyIndex = -1;
    let usingWebsockets = true;
    let retryConnection = false;

    function initTerminal() {
        window.terminal = new Terminal({
            theme: {
                background: '#1e1e1e',
                foreground: '#d4d4d4',
                cursor: '#00ffea',
                selection: '#264f78',
            },
            fontFamily: 'Fira Mono, monospace',
            fontSize: 15,
            cursorBlink: true,
            scrollback: 2000,
            tabStopWidth: 4,
            disableStdin: false,
        });
        window.fitAddon = new FitAddon.FitAddon();
        window.searchAddon = new SearchAddon.SearchAddon();
        terminal = window.terminal;
        fitAddon = window.fitAddon;
        searchAddon = window.searchAddon;
        terminal.loadAddon(fitAddon);
        terminal.loadAddon(searchAddon);
        terminal.open(terminalContainer);
        fitAddon.fit();
        terminal.focus();
        terminal.writeln('\x1B[1;36m┌─────────────────────────────────────────────┐\x1B[0m');
        terminal.writeln('\x1B[1;36m│\x1B[0m \x1B[1;32mBienvenido al terminal SSH\x1B[0m           \x1B[1;36m│\x1B[0m');
        terminal.writeln('\x1B[1;36m└─────────────────────────────────────────────┘\x1B[0m\r\n');
        terminal.write('Presiona Conectar para iniciar sesión...\r\n');
        terminal.onData(handleTerminalInput);
        window.addEventListener('resize', () => fitAddon.fit());
    }

    function updateConnectionStatus(isActive, text) {
        connectionStatus.className = `connection-indicator ${isActive ? 'connection-active' : 'connection-inactive'}`;
        connectionText.textContent = text;
    }

    function updateTerminalTitle(username = 'root') {
        const titleEl = document.querySelector('.terminal-title');
        if (titleEl) {
            titleEl.textContent = `${username}@{{ $host->hostname }}: ${formatPath(currentDirectory)}`;
        }
    }

    function formatPath(path) {
        if (!path) return '~';
        return path.replace('/home/' + getCurrentUserFromPrompt(), '~');
    }

    function getCurrentUserFromPrompt() {
        const match = currentPrompt.match(/^([\w-]+)@/);
        return match ? match[1] : 'root';
    }

    function showPrompt() {
        terminal.write(`\r\n${currentPrompt}`);
    }

    function handleTerminalInput(data) {
        if (!isConnected) return;
        // Enter
        if (data === '\r') {
            terminal.write('\r\n');
            if (commandBuffer.trim() !== '') {
                sendCommand(commandBuffer);
                commandHistory.unshift(commandBuffer);
                if (commandHistory.length > 50) commandHistory.pop();
                historyIndex = -1;
            }
            commandBuffer = '';
            // El prompt se mostrará al recibir la respuesta
        } else if (data === '\u007F') { // Backspace
            if (commandBuffer.length > 0) {
                terminal.write('\b \b');
                commandBuffer = commandBuffer.slice(0, -1);
            }
        } else if (data === '\u001b[A') { // Flecha arriba
            if (commandHistory.length > 0 && historyIndex < commandHistory.length - 1) {
                historyIndex++;
                clearCurrentLine();
                commandBuffer = commandHistory[historyIndex];
                terminal.write(commandBuffer);
            }
        } else if (data === '\u001b[B') { // Flecha abajo
            if (historyIndex > 0) {
                historyIndex--;
                clearCurrentLine();
                commandBuffer = commandHistory[historyIndex];
                terminal.write(commandBuffer);
            } else if (historyIndex === 0) {
                historyIndex = -1;
                clearCurrentLine();
                commandBuffer = '';
            }
        } else if (data === '\u0003') { // Ctrl+C
            terminal.write('^C');
            commandBuffer = '';
            showPrompt();
        } else if (data >= ' ' && data <= '~') { // Caracteres imprimibles
            terminal.write(data);
            commandBuffer += data;
        }
    }

    function clearCurrentLine() {
        // Borra la línea actual del prompt
        let len = commandBuffer.length;
        while (len-- > 0) terminal.write('\b \b');
    }

    // --- Debug helpers ---
    const debugCommands = [];
    const debugEvents = [];
    let lastDebugOutput = '';
    function showDebugToast(title, content) {
        const toast = document.getElementById('debug-toast');
        if (!toast) return;
        toast.innerHTML = `<b>${title}</b><br><pre style='white-space:pre-wrap; color:#0f0; margin:0;'>${content}</pre>`;
        toast.style.display = 'block';
        clearTimeout(window._debugToastTimeout);
        window._debugToastTimeout = setTimeout(() => { toast.style.display = 'none'; }, 5000);
    }

    function sendCommand(cmd) {
        if (!sessionId || !isConnected) return;
        showDebugToast('Comando enviado', cmd);
        if (usingWebsockets && window.Echo) {
            // Enviar comando por WebSocket (Reverb)
            fetch('/api/websocket/command', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ sessionId, command: cmd })
            }).then(res => res.json()).then(resp => {
                if (!resp.success) {
                    terminal.writeln(`\x1B[1;31m✗ Error WebSocket: ${resp.message}\x1B[0m`);
                    showPrompt();
                }
            }).catch(() => {
                terminal.writeln('\x1B[1;31m✗ Error enviando comando por WebSocket\x1B[0m');
                showPrompt();
            });
        } else {
            // Fallback AJAX
            fetch('/api/terminal/send', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ sessionId, command: cmd })
            }).then(res => res.json()).then(resp => {
                if (resp.output) terminal.write(resp.output);
                showPrompt();
            });
        }
    }

    function connectToSsh(username = 'root') {
        terminal.clear();
        updateConnectionStatus(false, 'Iniciando WebSocket...');
        terminal.writeln('\x1B[1;34m● Iniciando servidor WebSocket Reverb...\x1B[0m');
        if (window.echoConnectionFailed) {
            terminal.writeln('\x1B[1;33m⚠ WebSockets no disponibles. Continuando en modo de compatibilidad.\x1B[0m');
            usingWebsockets = false;
            connectToSSHServer(username);
            return;
        }
        fetch('/api/websocket/status').then(r => r.json()).then(response => {
            if (response.running) {
                terminal.writeln('\x1B[1;32m✓ Servidor WebSocket activo\x1B[0m');
                usingWebsockets = true;
                connectToSSHServer(username);
            } else {
                startReverbServer(username);
            }
        }).catch(() => startReverbServer(username));
    }

    function startReverbServer(username) {
        fetch('/api/websocket/start', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        }).then(r => r.json()).then(response => {
            terminal.writeln(`\x1B[1;32m✓ ${response.message || 'Servidor WebSocket iniciado'}\x1B[0m`);
            usingWebsockets = true;
            setTimeout(() => connectToSSHServer(username), 1000);
        }).catch(() => {
            terminal.writeln('\x1B[1;33m⚠ No se pudo iniciar Reverb. Continuando en modo de compatibilidad.\x1B[0m');
            usingWebsockets = false;
            window.echoConnectionFailed = true;
            connectToSSHServer(username);
        });
    }

    function connectToSSHServer(username) {
        updateConnectionStatus(false, 'Conectando...');
        terminal.writeln(`\x1B[1;34m● Conectando al servidor SSH como \x1B[1;36m${username}\x1B[0m usando autenticación por clave...`);
        fetch('/api/terminal/connect', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            body: JSON.stringify({ host_id: '{{ $host->id }}', username })
        }).then(res => res.json()).then(response => {
            if (response.success) {
                isConnected = true;
                sessionId = response.sessionId;
                updateConnectionStatus(true, `Conectado: ${username}@{{ $host->hostname }}`);
                terminal.writeln('\x1B[1;32m✓ Conexión establecida. Terminal listo.\x1B[0m');
                currentPrompt = `${username}@{{ $host->hostname }}:~$ `;
                updateTerminalTitle(username);
                if (usingWebsockets) setupWebSocketConnection(sessionId);
                showPrompt();
            } else {
                updateConnectionStatus(false, 'Error de conexión');
                terminal.writeln(`\x1B[1;31m✗ Error: ${response.message}\x1B[0m`);
            }
        }).catch(() => {
            updateConnectionStatus(false, 'Falló la conexión');
            terminal.writeln('\x1B[1;33m⚠ Presiona Enter para intentar nuevamente\x1B[0m');
            retryConnection = true;
        });
    }

    function setupWebSocketConnection(sessionId) {
    if (typeof window.Echo === 'undefined' || window.echoConnectionFailed === true) {
        usingWebsockets = false;
        terminal.writeln('\x1B[1;33m⚠ WebSockets no disponibles. Funcionando en modo de compatibilidad.\x1B[0m');
        return;
    }
    try {
        const channelName = `terminal.${sessionId}`;

        // Logs iniciales
        console.log(`[CLIENT JS DEBUG] Intentando configurar WebSocket para el canal: ${channelName}`);
        console.log(`[CLIENT JS DEBUG] Objeto Echo disponible:`, window.Echo);
        console.log(`[CLIENT JS DEBUG] Escuchando el evento: 'App.Events.TerminalOutputReceived'`);

        // --- UTILIZA SOLO ESTE BLOQUE PARA LA SUSCRIPCIÓN Y LISTENERS ---
        if (window.Echo) {
            // Dejar de escuchar en el canal antiguo si ya existía una suscripción previa
            // Esto es importante si llamas a setupWebSocketConnection múltiples veces con diferentes sessionIds
            if (window.currentActiveTerminalChannel) {
                console.log(`[CLIENT JS DEBUG] Dejando el canal anterior: ${window.currentActiveTerminalChannel.name}`);
                window.currentActiveTerminalChannel.stopListening('App.Events.TerminalOutputReceived'); // Detiene el listener específico
                window.Echo.leave(window.currentActiveTerminalChannel.name); // Abandona el canal
            }

            console.log(`[CLIENT JS DEBUG] Suscribiéndose ahora a: ${channelName}`);
            let subscribedChannel = window.Echo.channel(channelName);
            window.currentActiveTerminalChannel = subscribedChannel; // Guardar referencia al canal activo

            subscribedChannel.subscribed(() => {
                console.log(`[CLIENT JS DEBUG] ¡Suscrito exitosamente a: ${channelName}! Listo para escuchar.`);
                updateConnectionStatus(true, `Conectado (WS): ${getCurrentUserFromPrompt()}@{{ $host->hostname }}`);
                usingWebsockets = true;
            });

            subscribedChannel.error((error) => {
                console.error(`[CLIENT JS DEBUG] Error de suscripción a ${channelName}:`, error);
                terminal.writeln(`\x1B[1;31m✗ Error de suscripción WebSocket a ${channelName}\x1B[0m`);
                updateConnectionStatus(false, 'Error WS');
                // Considerar fallback a AJAX si la suscripción falla
                // usingWebsockets = false;
                // window.echoConnectionFailed = true;
            });

            // Suscríbete a ambos nombres de evento por compatibilidad
            subscribedChannel.listen('App.Events.TerminalOutputReceived', (eventData) => {
                console.log('[CLIENT JS DEBUG] >>> Evento App.Events.TerminalOutputReceived RECIBIDO (listener principal):', eventData);
                processWebSocketResponse(eventData);
            });
            subscribedChannel.listen('TerminalOutputReceived', (eventData) => {
                console.log('[CLIENT JS DEBUG] >>> Evento TerminalOutputReceived RECIBIDO (listener alternativo):', eventData);
                processWebSocketResponse(eventData);
            });

            // Listener manual usando el canal Pusher subyacente
            const pusherChannel = window.Echo.connector.pusher.channel(channelName);
            if (pusherChannel) {
                pusherChannel.bind('TerminalOutputReceived', function(data) {
                    console.log('[CLIENT JS DEBUG] Evento TerminalOutputReceived recibido por Pusher:', data);
                    let eventData = data;
                    if (typeof data === 'string') {
                        try { eventData = JSON.parse(data); } catch (e) {}
                    }
                    processWebSocketResponse(eventData);
                });
            }

            // Listeners de diagnóstico de bajo nivel (puedes mantenerlos o quitarlos una vez funcione)
            if (window.Echo.connector && window.Echo.connector.pusher) {
                const connection = window.Echo.connector.pusher.connection;
                // Evita añadir listeners duplicados si esta función se llama múltiples veces
                if (!connection.bindings || !connection.bindings['state_change']) { // Simple check
                    connection.bind('state_change', function(states) {
                        console.log("[CLIENT JS DEBUG] Estado de conexión Reverb cambió:", states);
                    });
                    connection.bind('connected', function() {
                        console.log('[CLIENT JS DEBUG] Conectado a Reverb a nivel de pusher (bajo nivel).');
                    });
                    connection.bind('error', function(err) {
                        console.error('[CLIENT JS DEBUG] Error de conexión Pusher/Reverb (bajo nivel):', err);
                    });
                }
            }
        } else {
             console.error("[CLIENT JS DEBUG] window.Echo no está definido al intentar suscribirse.");
             // Fallback a modo no-websockets
             usingWebsockets = false;
             window.echoConnectionFailed = true;
             terminal.writeln('\x1B[1;33m⚠ window.Echo no definido. Funcionando en modo de compatibilidad.\x1B[0m');
        }
        // Ya no necesitas la línea que actualizaba connectionStatus aquí,
        // se hace dentro del callback .subscribed() o .error()

    } catch (error) {
        terminal.writeln('\x1B[1;31m✗ Error al configurar WebSocket: ' + error.message + '\x1B[0m');
        terminal.writeln('\x1B[1;33m⚠ Continuando en modo de compatibilidad sin WebSockets\x1B[0m');
        usingWebsockets = false;
            window.echoConnectionFailed = true;
        }
    }


    // --- Asegura que siempre existe formatOutput ---
    function formatOutput(output) {
        output = String(output ?? '');
        // Normaliza saltos de línea para xterm.js
        return output.replace(/\r?\n/g, '\r\n');
    }

    function processWebSocketResponse(event) {
        if (window.lastCommandTimeout) {
            clearTimeout(window.lastCommandTimeout);
            window.lastCommandTimeout = null;
        }
        showDebugToast('Evento WebSocket recibido', JSON.stringify(event, null, 2));
        console.log('WebSocket: Evento recibido', event);
        try {
            // Si es comando de limpiar terminal
            if (event.clear) {
                terminal.clear();
                return;
            }
            // Escribir la salida en el terminal SIEMPRE, aunque sea vacío
            let formattedOutput = '';
            if ('output' in event) {
                formattedOutput = formatOutput(event.output);
                terminal.write(formattedOutput);
                console.log('WebSocket: Salida escrita en terminal:', formattedOutput);
            } else {
                console.warn('WebSocket: Evento recibido SIN output:', event);
                terminal.write('\r\n\x1B[1;33m[Advertencia] Evento recibido sin output\x1B[0m\r\n');
            }
            // Actualizar el directorio actual si se proporciona
            if (event.currentDirectory) {
                const currentUser = getCurrentUserFromPrompt ? getCurrentUserFromPrompt() : 'user';
                currentDirectory = event.currentDirectory;
                currentPrompt = `${currentUser}@{{ $host->hostname }}:${formatPath ? formatPath(currentDirectory) : currentDirectory}$ `;
                updateTerminalTitle && updateTerminalTitle(currentUser);
            }
            // Mostrar el prompt SIEMPRE en nueva línea
            terminal.write('\r\n' + (typeof currentPrompt !== 'undefined' ? currentPrompt : '$ '));
            // Ajustar scroll automáticamente
            if (typeof scrollToBottom === 'function') scrollToBottom();
            else if (terminal && terminal.scrollToBottom) terminal.scrollToBottom();
            fitAddon && fitAddon.fit();
        } catch (err) {
            console.error('WebSocket: Error procesando evento:', err, event);
            terminal.write('\r\n\x1B[1;31m[Error] No se pudo mostrar la salida del evento WebSocket\x1B[0m\r\n');
        }
    }

    // Botón conectar
    connectBtn.addEventListener('click', function() {
        if (!isConnected) {
            connectBtn.disabled = true;
            connectBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Conectando...';
            connectToSsh('root');
            setTimeout(() => {
                connectBtn.disabled = false;
                connectBtn.innerHTML = '<i class="fas fa-power-off"></i> Desconectar';
            }, 3000);
        } else {
            // Desconectar
            isConnected = false;
            sessionId = null;
            updateConnectionStatus(false, 'Desconectado');
            terminal.writeln('\x1B[1;33mDesconectado.\x1B[0m');
            connectBtn.innerHTML = '<i class="fas fa-terminal"></i> Conectar';
        }
    });

    // Inicializar terminal
    initTerminal();
});
</script>
