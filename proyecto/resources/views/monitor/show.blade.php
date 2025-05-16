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
            height: 450px;
            background-color: #300a24 !important; /* Color de fondo Ubuntu */
            border-radius: 8px;
            padding: 0;
            display: none;
            overflow: hidden;
            position: relative;
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
            height: calc(100% - 54px); /* Altura total - (titlebar + statusbar) */
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
                
                {{-- Card Terminal SSH --}}
                <div class="card" id="ssh-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4><i class="fas fa-terminal mr-2"></i>Terminal SSH</h4>
                        <div class="card-header-action">
                            <button id="connect-terminal-button" class="btn btn-primary btn-sm">
                                <i class="fas fa-terminal"></i> Conectar
                            </button>
                            <button id="terminal-fullscreen-btn" class="btn btn-icon btn-info btn-sm" title="Pantalla completa">
                                <i class="fas fa-expand"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div id="terminal-message-container"></div>
                        <div id="terminal-container" class="position-relative">
                            <!-- Barra de título estilo Ubuntu -->
                            <div class="terminal-titlebar">
                                <div class="terminal-titlebar-buttons">
                                    <div class="terminal-button terminal-button-close"></div>
                                    <div class="terminal-button terminal-button-minimize"></div>
                                    <div class="terminal-button terminal-button-maximize"></div>
                                </div>
                                <div class="terminal-title">
                                    root@{{ $host->hostname }}: ~
                                </div>
                            </div>
                            
                            <div id="terminal-basic" class="terminal"></div>
                            
                            <!-- Barra de estado -->
                            <div class="terminal-statusbar">
                                <div class="connection-indicator connection-inactive" id="connection-status"></div>
                                <span id="connection-text">Desconectado</span>
                                <div class="ml-auto" id="terminal-info">80×24</div>
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
@endsection

@section('js')
{{-- Terminal JS dependencies --}}
<script src="https://cdn.jsdelivr.net/npm/xterm@5.3.0/lib/xterm.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xterm-addon-fit@0.8.0/lib/xterm-addon-fit.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xterm-addon-webgl@0.16.0/lib/xterm-addon-webgl.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.15.3/dist/echo.iife.js"></script>
<script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0/dist/web/pusher.min.js"></script>

<script>
    // Variables globales para la terminal
    let terminal;
    let fitAddon;
    let webglAddon;
    let isConnected = false;
    let sessionId = null;
    let currentDirectory = '~';
    let currentPrompt = 'root@{{ $host->hostname }}:~$ ';
    let usingWebsockets = false;
    
    // Elementos DOM
    const terminalContainer = document.getElementById('terminal-container');
    const connectionStatus = document.getElementById('connection-status');
    const connectionText = document.getElementById('connection-text');
    const messageContainer = $('#terminal-message-container');
    
    // Verificar si el WebSocket puede funcionar desde el inicio
    window.addEventListener('DOMContentLoaded', function() {
        // Comprobar si WebSocket está disponible en el navegador
        if ('WebSocket' in window) {
            console.log('WebSocket está disponible en este navegador');
            setupLaravelEcho();
        } else {
            console.error('WebSocket no está disponible en este navegador');
            showMessage('Tu navegador no soporta WebSockets, necesarios para la terminal SSH.', 'danger');
        }
    });
    
    // Configurar Laravel Echo para manejar WebSockets
    function setupLaravelEcho() {
        try {
            // Obtener el host y protocolo actual para WebSockets
            const currentHost = window.location.hostname;
            const currentProtocol = window.location.protocol === 'https:' ? 'wss' : 'ws';
            const isSecure = window.location.protocol === 'https:';
            
            // Configurar Laravel Echo para usar Pusher (mediante Reverb)
            window.Echo = new Echo({
                broadcaster: 'pusher',
                key: '{{ env("REVERB_APP_KEY", "proyectoDArevkey") }}',
                wsHost: currentHost,
                wsPort: {{ env("REVERB_PORT", "8080") }},
                wssPort: {{ env("REVERB_PORT", "8080") }},
                forceTLS: isSecure,
                encrypted: isSecure,
                disableStats: true,
                enabledTransports: ['ws', 'wss'],
                cluster: 'mt1',
                authEndpoint: '/broadcasting/auth',
                enableLogging: true
            });
            
            // Verificar conexión de Echo
            if (window.Echo.connector && window.Echo.connector.pusher) {
                // Escuchar eventos de conexión y desconexión
                window.Echo.connector.pusher.connection.bind('connected', function() {
                    console.log('WebSocket conectado correctamente');
                    usingWebsockets = true;
                });
                
                window.Echo.connector.pusher.connection.bind('disconnected', function() {
                    console.warn('WebSocket desconectado');
                    if (isConnected) {
                        showMessage('Conexión WebSocket perdida. La terminal puede no funcionar correctamente.', 'warning');
                    }
                });
                
                window.Echo.connector.pusher.connection.bind('error', function(error) {
                    console.error('Error de conexión WebSocket:', error);
                    window.echoConnectionFailed = true;
                    showMessage('Error al conectar con el servidor WebSocket: ' + JSON.stringify(error), 'danger');
                });
            }
        } catch (error) {
            console.error('Error al configurar Laravel Echo:', error);
            showMessage('Error al configurar WebSockets: ' + error.message, 'danger');
        }
    }
    
    // Inicializa la terminal XTerm.js
    function initTerminal(username) {
        // Si la terminal ya existe, destruirla primero
        if (terminal) {
            terminal.dispose();
        }
        
        // Opciones de la terminal
        const options = {
            fontFamily: 'Menlo, Monaco, "Courier New", monospace',
            fontSize: 14,
            lineHeight: 1.2,
            cursorBlink: true,
            cursorStyle: 'bar',
            theme: {
                background: '#300A24',
                foreground: '#FFFFFF',
                cursor: '#FFFFFF',
                cursorAccent: '#300A24',
                selection: 'rgba(255, 255, 255, 0.3)',
                black: '#2E3436',
                red: '#CC0000',
                green: '#4E9A06',
                yellow: '#C4A000',
                blue: '#3465A4',
                magenta: '#75507B',
                cyan: '#06989A',
                white: '#D3D7CF',
                brightBlack: '#555753',
                brightRed: '#EF2929',
                brightGreen: '#8AE234',
                brightYellow: '#FCE94F',
                brightBlue: '#729FCF',
                brightMagenta: '#AD7FA8',
                brightCyan: '#34E2E2',
                brightWhite: '#EEEEEC'
            }
        };
        
        // Crear la instancia de terminal
        terminal = new Terminal(options);
        
        // Cargar addons
        fitAddon = new FitAddon.FitAddon();
        terminal.loadAddon(fitAddon);
        
        try {
            webglAddon = new WebglAddon.WebglAddon();
            terminal.loadAddon(webglAddon);
        } catch (e) {
            console.warn('WebGL no disponible, usando renderizador estándar', e);
        }
        
        // Mostrar terminal
        terminal.open(document.getElementById('terminal-basic'));
        fitAddon.fit();
        terminalContainer.style.display = 'block';
        
        // Mensaje inicial
        terminal.write('\r\n\x1B[1;34m● Conectando a {{ $host->hostname }}...\x1B[0m\r\n');
        
        // Ajustar tamaño en cambios de ventana
        window.addEventListener('resize', function() {
            if (fitAddon) {
                fitAddon.fit();
                const dims = terminal.cols + '×' + terminal.rows;
                document.getElementById('terminal-info').textContent = dims;
            }
        });
        
        // Mostrar dimensiones de la terminal
        const dims = terminal.cols + '×' + terminal.rows;
        document.getElementById('terminal-info').textContent = dims;
        
        // Manejar entrada del usuario
        terminal.onData(function(data) {
            // Solo enviar datos si estamos conectados
            if (isConnected) {
                // Si es retroceso o suprimir
                if (data === '\x7f') {
                    // Solo enviar si hay caracteres después del prompt
                    terminal.write('\b \b');
                } 
                // Si es Enter
                else if (data === '\r') {
                    // Extraer el comando después del prompt
                    const lastLine = getLastLine();
                    const promptLength = currentPrompt.length;
                    const command = lastLine.substring(promptLength);
                    
                    // Ejecutar el comando
                    executeCommand(command.trim());
                } 
                // Otros caracteres: escribir al terminal
                else {
                    terminal.write(data);
                }
            }
        });
        
        // Conectar a SSH
        connectToSSH(username);
    }
    
    // Conectar a SSH
    function connectToSSH(username) {
        // Si ya está conectado, desconectar primero
        if (isConnected) {
            disconnectTerminal();
            return;
        }
        
        // Mostrar indicador de carga
        updateConnectionStatus(false, 'Conectando...');
        terminal.write('\r\n\x1B[1;34m● Estableciendo conexión SSH...\x1B[0m\r\n');
        
        // Iniciar el servidor Reverb si es necesario
        checkReverbServer().then(function(serverRunning) {
            if (!serverRunning) {
                terminal.write('\r\n\x1B[1;33m⚠ Iniciando servidor WebSocket...\x1B[0m\r\n');
                return startReverbServer();
            }
            return Promise.resolve(true);
        }).then(function(success) {
            if (!success) {
                terminal.write('\r\n\x1B[1;31m✗ No se pudo iniciar el servidor WebSocket\x1B[0m\r\n');
                showReverbInstructions();
                return Promise.reject('Error al iniciar WebSocket');
            }
            
            // Conectar al SSH
            return $.ajax({
                url: '/api/terminal/connect',
                type: 'POST',
                data: {
                    host_id: {{ $host->id }},
                    username: username,
                    _token: '{{ csrf_token() }}'
                }
            });
        }).then(function(response) {
            if (response.success) {
                // Guardar ID de sesión
                sessionId = response.sessionId;
                isConnected = true;
                
                // Actualizar estado
                updateConnectionStatus(true, 'Conectado: SSH');
                terminal.write('\r\n\x1B[1;32m✓ Conexión SSH establecida\x1B[0m\r\n');
                
                // Actualizar directorio y prompt
                currentDirectory = '~';
                currentPrompt = `${username}@{{ $host->hostname }}:~$ `;
                terminal.write('\r\n' + currentPrompt);
                
                // Actualizar título
                updateTerminalTitle(username);
                
                // Configurar WebSocket
                setupWebSocketConnection(sessionId);
                
                // Actualizar estado del botón
                $('#connect-terminal-button').prop('disabled', false).html('<i class="fas fa-times"></i> Desconectar');
            } else {
                // Mostrar error
                terminal.write('\r\n\x1B[1;31m✗ Error al conectar: ' + response.message + '\x1B[0m\r\n');
                updateConnectionStatus(false, 'Error de conexión');
                $('#connect-terminal-button').prop('disabled', false).html('<i class="fas fa-terminal"></i> Conectar');
            }
        }).catch(function(error) {
            console.error('Error al conectar SSH:', error);
            let errorMessage = 'Error de conexión';
            
            if (error.responseJSON && error.responseJSON.message) {
                errorMessage = error.responseJSON.message;
            } else if (typeof error === 'string') {
                errorMessage = error;
            }
            
            terminal.write('\r\n\x1B[1;31m✗ Error: ' + errorMessage + '\x1B[0m\r\n');
            updateConnectionStatus(false, 'Desconectado');
            $('#connect-terminal-button').prop('disabled', false).html('<i class="fas fa-terminal"></i> Conectar');
        });
    }
    
    // Verificar si el servidor Reverb está funcionando
    function checkReverbServer() {
        return new Promise(function(resolve, reject) {
            $.ajax({
                url: '/api/websocket/status',
                type: 'GET',
                success: function(response) {
                    resolve(response.running);
                },
                error: function() {
                    resolve(false);
                }
            });
        });
    }
    
    // Iniciar el servidor Reverb
    function startReverbServer() {
        return new Promise(function(resolve, reject) {
            $.ajax({
                url: '/api/websocket/start',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        terminal.write('\r\n\x1B[1;32m✓ Servidor WebSocket iniciado\x1B[0m\r\n');
                        
                        // Esperar un momento para dar tiempo al servidor a iniciar
                        setTimeout(function() {
                            // Reintentar configurar Echo
                            setupLaravelEcho();
                            resolve(true);
                        }, 2000);
                    } else {
                        terminal.write('\r\n\x1B[1;31m✗ Error al iniciar servidor WebSocket: ' + response.message + '\x1B[0m\r\n');
                        resolve(false);
                    }
                },
                error: function(xhr, status, error) {
                    terminal.write('\r\n\x1B[1;31m✗ Error al iniciar servidor WebSocket\x1B[0m\r\n');
                    resolve(false);
                }
            });
        });
    }
    
    // Configurar WebSocket para terminal
    function setupWebSocketConnection(sessionId) {
        console.log('Configurando WebSocket para la terminal:', sessionId);
        
        if (!window.Echo) {
            terminal.write('\r\n\x1B[1;31m✗ Error: Laravel Echo no está disponible\x1B[0m\r\n');
            showReverbInstructions();
            return false;
        }
        
        try {
            // Suscribirse al canal privado del terminal
            window.Echo.private(`terminal.${sessionId}`)
                .listen('TerminalOutputReceived', function(e) {
                    processTerminalOutput(e);
                });
                
            console.log('Suscrito al canal privado terminal.' + sessionId);
            usingWebsockets = true;
            updateConnectionStatus(true, 'Conectado: WebSockets');
            return true;
        } catch (error) {
            console.error('Error al suscribirse al canal privado:', error);
            
            // Intentar con canal público como alternativa
            try {
                window.Echo.channel(`terminal.${sessionId}`)
                    .listen('TerminalOutputReceived', function(e) {
                        processTerminalOutput(e);
                    });
                    
                console.log('Suscrito al canal público terminal.' + sessionId);
                usingWebsockets = true;
                updateConnectionStatus(true, 'Conectado: WebSockets (público)');
                return true;
            } catch (innerError) {
                console.error('Error al suscribirse al canal público:', innerError);
                terminal.write('\r\n\x1B[1;31m✗ Error: No se pudo establecer comunicación WebSocket\x1B[0m\r\n');
                showReverbInstructions();
                return false;
            }
        }
    }
    
    // Procesar salida del terminal recibida por WebSocket
    function processTerminalOutput(event) {
        // Cancelar timeout pendiente
        if (window.lastCommandTimeout) {
            clearTimeout(window.lastCommandTimeout);
            window.lastCommandTimeout = null;
        }
        
        // Procesar salida
        if (event.output) {
            let formattedOutput = formatOutput(event.output);
            terminal.write(formattedOutput);
            
            // Actualizar directorio si se proporciona
            if (event.currentDirectory) {
                const currentUser = getCurrentUserFromPrompt();
                currentDirectory = event.currentDirectory;
                currentPrompt = `${currentUser}@{{ $host->hostname }}:${formatPath(currentDirectory)}$ `;
                updateTerminalTitle(currentUser);
            }
            
            // Mostrar nuevo prompt
            terminal.write('\r\n' + currentPrompt);
            
            // Scroll al fondo
            scrollToBottom();
        }
    }
    
    // Formatear salida del terminal
    function formatOutput(output) {
        // Asegurarse de que sea string
        output = String(output);
        return output;
    }
    
    // Ejecutar comando
    function executeCommand(command) {
        if (!command) return;
        
        // Si no hay conexión, mostrar error
        if (!isConnected || !sessionId) {
            terminal.write('\r\n\x1B[1;31m✗ Error: No hay una conexión SSH activa\x1B[0m\r\n');
            return;
        }
        
        // Mostrar comando en la terminal
        terminal.write('\r\n');
        
        // Intentar enviar por WebSocket si está disponible
        if (usingWebsockets && window.Echo) {
            try {
                // Enviar usando whisper en canal privado
                window.Echo.private('terminal.' + sessionId)
                    .whisper('command', {
                        command: command
                    });
                    
                console.log('Comando enviado via WebSocket:', command);
                
                // Establecer timeout para la respuesta
                window.lastCommandTimeout = setTimeout(function() {
                    terminal.write('\r\n\x1B[1;31m✗ Error: Tiempo de espera agotado\x1B[0m\r\n');
                    terminal.write('\r\n' + currentPrompt);
                }, 10000);
                
                return; // Si se envió por WebSocket, salir
            } catch (error) {
                console.error('Error al enviar comando por WebSocket:', error);
                // Continuar con AJAX como fallback
            }
        }
        
        // Fallback a AJAX si WebSocket no está disponible
        $.ajax({
            url: '/api/terminal/send',
            type: 'POST',
            data: {
                sessionId: sessionId,
                command: command,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                console.log('Comando enviado via AJAX:', response);
                
                if (response.success) {
                    // Procesar la respuesta directamente
                    let formattedOutput = formatOutput(response.output || '');
                    terminal.write(formattedOutput);
                    
                    // Actualizar directorio si se proporciona
                    if (response.currentDirectory) {
                        const currentUser = getCurrentUserFromPrompt();
                        currentDirectory = response.currentDirectory;
                        currentPrompt = `${currentUser}@{{ $host->hostname }}:${formatPath(currentDirectory)}$ `;
                        updateTerminalTitle(currentUser);
                    }
                    
                    // Mostrar nuevo prompt
                    terminal.write('\r\n' + currentPrompt);
                    
                    // Scroll al fondo
                    scrollToBottom();
                } else {
                    terminal.write('\r\n\x1B[1;31m✗ Error: ' + response.message + '\x1B[0m\r\n');
                    terminal.write('\r\n' + currentPrompt);
                }
            },
            error: function(xhr, status, error) {
                terminal.write('\r\n\x1B[1;31m✗ Error: No se pudo enviar el comando\x1B[0m\r\n');
                terminal.write('\r\n\x1B[1;33m⚠ Error en la comunicación con el servidor\x1B[0m\r\n');
                terminal.write('\r\n' + currentPrompt);
            }
        });
    }
    
    // Desconectar terminal
    function disconnectTerminal() {
        if (!isConnected || !sessionId) return;
        
        terminal.write('\r\n\x1B[1;34m● Cerrando sesión SSH...\x1B[0m\r\n');
        
        $.ajax({
            url: '/api/terminal/disconnect',
            type: 'POST',
            data: {
                sessionId: sessionId,
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    terminal.write('\r\n\x1B[1;32m✓ Sesión terminada correctamente\x1B[0m\r\n');
                } else {
                    terminal.write('\r\n\x1B[1;31m✗ Error al cerrar sesión: ' + response.message + '\x1B[0m\r\n');
                }
                
                finishDisconnect();
            },
            error: function(xhr) {
                let message = 'Error al cerrar sesión';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
                
                terminal.write('\r\n\x1B[1;31m✗ Error: ' + message + '\x1B[0m\r\n');
                finishDisconnect();
            }
        });
        
        function finishDisconnect() {
            isConnected = false;
            sessionId = null;
            updateConnectionStatus(false, 'Desconectado');
            $('#connect-terminal-button').prop('disabled', false).html('<i class="fas fa-terminal"></i> Conectar');
            
            // Desconectar de WebSocket si está disponible
            if (window.Echo) {
                try {
                    window.Echo.leave(`terminal.${sessionId}`);
                } catch (error) {
                    console.error('Error al desconectar WebSocket:', error);
                }
            }
        }
    }
    
    // Funciones auxiliares
    function getLastLine() {
        const lines = terminal.buffer.active.getLine(terminal.buffer.active.cursorY);
        let lastLine = '';
        
        if (lines) {
            lastLine = lines.translateToString();
        }
        
        return lastLine;
    }
    
    function formatPath(path) {
        if (path === '~' || path === '/root') return '~';
        return path.replace('/root', '~');
    }
    
    function getCurrentUserFromPrompt() {
        const promptMatch = currentPrompt.match(/^([^@]+)@/);
        return promptMatch ? promptMatch[1] : 'root';
    }
    
    function updateConnectionStatus(isActive, text) {
        connectionStatus.className = `connection-indicator ${isActive ? 'connection-active' : 'connection-inactive'}`;
        connectionText.textContent = text;
    }
    
    function updateTerminalTitle(username) {
        $('.terminal-title').text(`${username}@{{ $host->hostname }}: ${formatPath(currentDirectory)}`);
    }
    
    function scrollToBottom() {
        terminal.scrollToBottom();
    }
    
    function showMessage(message, type = 'info') {
        messageContainer.html(`
            <div class="alert alert-${type} alert-dismissible show fade">
                <div class="alert-body">
                    <button class="close" data-dismiss="alert"><span>&times;</span></button>
                    ${message}
                </div>
            </div>
        `);
    }
    
    function showReverbInstructions() {
        terminal.write('\r\n\x1B[1;33m⚠ El servidor WebSocket (Reverb) no está funcionando.\x1B[0m\r\n');
        terminal.write('\r\n\x1B[1;37mPara iniciar el servidor, ejecuta este comando en la consola del servidor:\x1B[0m\r\n');
        terminal.write('\r\n\x1B[1;32m$ php artisan reverb:start\x1B[0m\r\n');
        terminal.write('\r\n\x1B[1;37mMantén la ventana abierta mientras usas la terminal.\x1B[0m\r\n');
    }
    
    // Event listeners
    $(document).ready(function() {
        // Botón conectar/desconectar
        $('#connect-terminal-button').on('click', function() {
            if (isConnected) {
                disconnectTerminal();
            } else {
                initTerminal('root');
            }
        });
        
        // Modal eliminar host
        $('.delete-host-button').on('click', function() {
            const hostId = $(this).data('id');
            const hostname = $(this).data('hostname');
            
            $('#delete-hostname').text(hostname);
            $('#deleteHostForm').attr('action', '/monitor/' + hostId);
            $('#deleteHostModal').modal('show');
        });
        
        // Botón ping
        $('.ping-button').on('click', function() {
            const hostId = $(this).data('id');
            
            $.ajax({
                url: '/monitor/ping/' + hostId,
                type: 'GET',
                beforeSend: function() {
                    showMessage('Haciendo ping a {{ $host->hostname }}...', 'info');
                },
                success: function(response) {
                    if (response.success) {
                        showMessage('Host activo: ' + response.message, 'success');
                        
                        // Actualizar estado si cambió
                        if (response.status_color) {
                            $('#status-badge-container span').removeClass().addClass('badge badge-' + response.status_color + ' badge-pill').text(response.status_text);
                        }
                        
                        // Actualizar último contacto
                        if (response.last_seen) {
                            $('#last-seen-text').text(response.last_seen);
                        }
                        
                        // Actualizar métricas si cambiaron
                        if (response.metrics) {
                            if (response.metrics.cpu_usage !== undefined) {
                                $('#metric-cpu-usage').text(response.metrics.cpu_usage + '%');
                                $('#metric-cpu-progress').css('width', response.metrics.cpu_usage + '%')
                                    .attr('aria-valuenow', response.metrics.cpu_usage)
                                    .removeClass().addClass('progress-bar bg-' + response.metrics.cpu_color);
                            }
                            
                            if (response.metrics.memory_usage !== undefined) {
                                $('#metric-memory-usage').text(response.metrics.memory_usage + '%');
                                $('#metric-memory-progress').css('width', response.metrics.memory_usage + '%')
                                    .attr('aria-valuenow', response.metrics.memory_usage)
                                    .removeClass().addClass('progress-bar bg-' + response.metrics.memory_color);
                            }
                            
                            if (response.metrics.disk_usage !== undefined) {
                                $('#metric-disk-usage').text(response.metrics.disk_usage + '%');
                                $('#metric-disk-progress').css('width', response.metrics.disk_usage + '%')
                                    .attr('aria-valuenow', response.metrics.disk_usage)
                                    .removeClass().addClass('progress-bar bg-' + response.metrics.disk_color);
                            }
                            
                            if (response.metrics.uptime !== undefined) {
                                $('#metric-uptime').text(response.metrics.uptime);
                            }
                        }
                    } else {
                        showMessage('Error: ' + response.message, 'danger');
                    }
                },
                error: function() {
                    showMessage('Error al hacer ping al host', 'danger');
                }
            });
        });
        
        // Pantalla completa
        $('#terminal-fullscreen-btn').on('click', function() {
            if (!document.fullscreenElement) {
                terminalContainer.requestFullscreen().catch(err => {
                    showMessage('Error al entrar en modo pantalla completa: ' + err.message, 'warning');
                });
            } else {
                document.exitFullscreen();
            }
        });
        
        // Reajustar terminal cuando cambia el tamaño
        document.addEventListener('fullscreenchange', function() {
            if (fitAddon) {
                setTimeout(function() {
                    fitAddon.fit();
                    const dims = terminal.cols + '×' + terminal.rows;
                    document.getElementById('terminal-info').textContent = dims;
                }, 100);
            }
        });
    });
</script>
@endsection
