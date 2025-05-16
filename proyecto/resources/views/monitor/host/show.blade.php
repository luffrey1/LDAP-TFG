<!-- Sección para mostrar usuarios conectados si están disponibles -->
@if(isset($host->system_info['users']))
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-users me-2"></i> Usuarios conectados
        </h5>
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
                            <th>Tiempo de conexión</th>
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

<!-- Sección para mostrar procesos en ejecución si están disponibles -->
@if(isset($host->system_info['processes']))
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-tasks me-2"></i> Procesos en ejecución
        </h5>
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
                            <th>Inicio</th>
                            <th>Comando</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($host->system_info['processes']['processes'] as $process)
                            <tr>
                                <td>{{ $process['pid'] ?? 'N/A' }}</td>
                                <td>{{ $process['user'] ?? 'N/A' }}</td>
                                <td>{{ $process['cpu'] ?? '0' }}%</td>
                                <td>{{ $process['memory'] ?? '0' }}%</td>
                                <td>{{ $process['started'] ?? 'N/A' }}</td>
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

<!-- Sección para mostrar información de red si está disponible -->
@if(isset($host->system_info['network_info']))
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-network-wired me-2"></i> Interfaces de red
        </h5>
    </div>
    <div class="card-body">
        @if(count($host->system_info['network_info']['interfaces'] ?? []) > 0)
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>Interfaz</th>
                            <th>IP</th>
                            <th>MAC</th>
                            <th>Estado</th>
                            <th>Recibido</th>
                            <th>Enviado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($host->system_info['network_info']['interfaces'] as $name => $interface)
                            <tr>
                                <td>{{ $name }}</td>
                                <td>{{ $interface['ip'] ?? 'N/A' }}</td>
                                <td>{{ $interface['mac'] ?? 'N/A' }}</td>
                                <td>
                                    @if(($interface['status'] ?? '') == 'up')
                                        <span class="badge bg-success">Activo</span>
                                    @elseif(($interface['status'] ?? '') == 'down')
                                        <span class="badge bg-danger">Inactivo</span>
                                    @else
                                        <span class="badge bg-secondary">{{ $interface['status'] ?? 'Desconocido' }}</span>
                                    @endif
                                </td>
                                <td>{{ $interface['rx_mb'] ?? '0' }} MB</td>
                                <td>{{ $interface['tx_mb'] ?? '0' }} MB</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if(isset($host->system_info['network_info']['total_connections']))
                <div class="mt-2 text-muted small">
                    <strong>Conexiones activas:</strong> {{ $host->system_info['network_info']['total_connections'] }}
                </div>
            @endif
        @else
            <p class="text-muted">No hay información de interfaces de red disponible.</p>
        @endif
    </div>
</div>
@endif

<!-- Información del agente si está disponible -->
@if(isset($host->agent_version))
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="fas fa-robot me-2"></i> Información del agente
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Versión del agente:</strong> {{ $host->agent_version }}</p>
            </div>
            <div class="col-md-6">
                <p><strong>Último reporte:</strong> {{ $host->last_seen ? $host->last_seen->format('d/m/Y H:i:s') : 'Nunca' }}</p>
            </div>
        </div>
        <div class="mt-3">
            <a href="{{ route('monitor.ping', $host->id) }}" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-sync"></i> Actualizar telemetría
            </a>
            @if(!empty($host->mac_address))
            <a href="{{ route('monitor.wol', $host->id) }}" class="btn btn-outline-success btn-sm ms-2">
                <i class="fas fa-power-off"></i> Enviar Wake-on-LAN
            </a>
            @endif
        </div>
    </div>
</div>
@endif 