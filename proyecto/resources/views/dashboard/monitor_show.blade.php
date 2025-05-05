@extends('layouts.dashboard')

@section('title', 'Detalles de Equipo')

@section('content')
<div class="container-fluid">
    <!-- Encabezado de página -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Detalles del Equipo: {{ $host->hostname }}</h1>
        <div>
            <button id="btn-ping" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm mr-2" data-host-id="{{ $host->id }}">
                <i class="fas fa-sync fa-sm text-white-50"></i> Actualizar Estado
            </button>
            <a href="{{ route('monitor.index') }}" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Volver al Monitor
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Información básica del equipo -->
        <div class="col-lg-4 mb-4">
            <div class="card shadow h-100" data-aos="fade-up">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Información básica</h6>
                    <div class="dropdown">
                        <button type="button" class="btn btn-link p-0" onclick="document.getElementById('menu-actions').classList.toggle('show');">
                            <i class="fas fa-ellipsis-v text-gray-400"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" id="menu-actions">
                            <div class="dropdown-header">Acciones:</div>
                            <a class="dropdown-item" href="{{ route('monitor.edit', $host->id) }}">
                                <i class="fas fa-edit fa-sm fa-fw text-gray-400"></i> Editar Equipo
                            </a>
                            <button class="dropdown-item text-danger" type="button" onclick="document.getElementById('deleteModal').style.display='block'; document.getElementById('deleteModal').classList.add('show');">
                                <i class="fas fa-trash fa-sm fa-fw text-danger"></i> Eliminar Equipo
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="status-indicator mb-3">
                            <span class="status-badge badge badge-pill 
                                @if($host->status == 'online') badge-success
                                @elseif($host->status == 'offline') badge-danger
                                @else badge-warning
                                @endif p-3">
                                <i class="fas 
                                    @if($host->status == 'online') fa-check
                                    @elseif($host->status == 'offline') fa-times
                                    @else fa-question
                                    @endif fa-2x"></i>
                            </span>
                        </div>
                        <h4 class="font-weight-bold">{{ $host->hostname }}</h4>
                        <p class="mb-0">{{ $host->ip_address }}</p>
                        <p class="text-muted">
                            @if($host->status == 'online')
                                En línea
                            @elseif($host->status == 'offline')
                                Fuera de línea
                            @else
                                Estado desconocido
                            @endif
                        </p>
                    </div>
                    
                    <hr>
                    
                    <div class="info-item mb-2">
                        <span class="font-weight-bold">Dirección MAC:</span>
                        <span class="float-right">{{ $host->mac_address ?? 'No disponible' }}</span>
                    </div>
                    
                    <div class="info-item mb-2">
                        <span class="font-weight-bold">Grupo:</span>
                        <span class="float-right">
                            @switch($host->group_id)
                                @case(1)
                                    Laboratorio
                                    @break
                                @case(2)
                                    Administración
                                    @break
                                @case(3)
                                    Aulas
                                    @break
                                @case(4)
                                    Servidores
                                    @break
                                @default
                                    Sin grupo
                            @endswitch
                        </span>
                    </div>
                    
                    <div class="info-item mb-2">
                        <span class="font-weight-bold">Última conexión:</span>
                        <span class="float-right">{{ $host->last_seen ? $host->last_seen->format('d/m/Y H:i:s') : 'Nunca' }}</span>
                    </div>
                    
                    <div class="info-item mb-2">
                        <span class="font-weight-bold">Último arranque:</span>
                        <span class="float-right">{{ $host->last_boot ? $host->last_boot->format('d/m/Y H:i:s') : 'Desconocido' }}</span>
                    </div>
                    
                    <div class="info-item mb-2">
                        <span class="font-weight-bold">Añadido el:</span>
                        <span class="float-right">{{ $host->created_at->format('d/m/Y') }}</span>
                    </div>
                    
                    <hr>
                    
                    <div class="info-item">
                        <span class="font-weight-bold">Descripción:</span>
                        <p class="mt-2">{{ $host->description ?? 'Sin descripción' }}</p>
                    </div>
                    
                    <div class="d-flex mt-3">
                        <button type="button" class="btn btn-warning btn-sm me-2" id="btn-command" data-host-id="{{ $host->id }}" data-host-name="{{ $host->hostname }}">
                            <i class="fas fa-terminal"></i> Ejecutar Comando
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Información del sistema -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow h-100" data-aos="fade-up" data-aos-delay="100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Telemetría del Sistema</h6>
                </div>
                <div class="card-body">
                    @if($host->system_info)
                        <div class="row">
                            <!-- Información del SO -->
                            <div class="col-md-6 mb-4">
                                <div class="card border-left-primary shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Sistema Operativo</div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $host->system_info['os_name'] ?? 'Desconocido' }}</div>
                                                <div class="text-xs text-gray-600">{{ $host->system_info['os_version'] ?? '' }}</div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-laptop fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Memoria RAM -->
                            <div class="col-md-6 mb-4">
                                <div class="card border-left-success shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Memoria RAM</div>
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col-auto">
                                                        <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                                                            {{ $host->memory_usage['used_percent'] ?? '0' }}%
                                                        </div>
                                                    </div>
                                                    <div class="col">
                                                        <div class="progress progress-sm mr-2">
                                                            <div class="progress-bar bg-success" role="progressbar" 
                                                                style="width: {{ $host->memory_usage['used_percent'] ?? '0' }}%" 
                                                                aria-valuenow="{{ $host->memory_usage['used_percent'] ?? '0' }}" 
                                                                aria-valuemin="0" aria-valuemax="100">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="text-xs text-gray-600 mt-1">
                                                    {{ $host->memory_usage['used'] ?? '0' }} / {{ $host->memory_usage['total'] ?? '0' }} MB
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-memory fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <!-- CPU -->
                            <div class="col-md-6 mb-4">
                                <div class="card border-left-info shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Uso de CPU</div>
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col-auto">
                                                        <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                                                            {{ $host->cpu_usage['percent'] ?? '0' }}%
                                                        </div>
                                                    </div>
                                                    <div class="col">
                                                        <div class="progress progress-sm mr-2">
                                                            <div class="progress-bar bg-info" role="progressbar" 
                                                                style="width: {{ $host->cpu_usage['percent'] ?? '0' }}%" 
                                                                aria-valuenow="{{ $host->cpu_usage['percent'] ?? '0' }}" 
                                                                aria-valuemin="0" aria-valuemax="100">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="text-xs text-gray-600 mt-1">
                                                    {{ $host->system_info['cpu_model'] ?? 'CPU' }}
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-microchip fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Disco Duro -->
                            <div class="col-md-6 mb-4">
                                <div class="card border-left-warning shadow h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Espacio en Disco</div>
                                                <div class="row no-gutters align-items-center">
                                                    <div class="col-auto">
                                                        <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800">
                                                            {{ $host->disk_usage['used_percent'] ?? '0' }}%
                                                        </div>
                                                    </div>
                                                    <div class="col">
                                                        <div class="progress progress-sm mr-2">
                                                            <div class="progress-bar bg-warning" role="progressbar" 
                                                                style="width: {{ $host->disk_usage['used_percent'] ?? '0' }}%" 
                                                                aria-valuenow="{{ $host->disk_usage['used_percent'] ?? '0' }}" 
                                                                aria-valuemin="0" aria-valuemax="100">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="text-xs text-gray-600 mt-1">
                                                    {{ $host->disk_usage['used'] ?? '0' }} / {{ $host->disk_usage['total'] ?? '0' }} GB
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-hdd fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Información detallada del sistema -->
                        @if(isset($host->system_info['details']))
                            <div class="card shadow mb-4" data-aos="fade-up" data-aos-delay="200">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Información detallada</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered" width="100%" cellspacing="0">
                                            <tbody>
                                                @foreach($host->system_info['details'] as $key => $value)
                                                <tr>
                                                    <th width="30%">{{ ucfirst(str_replace('_', ' ', $key)) }}</th>
                                                    <td>{{ $value }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-info-circle fa-3x text-gray-300 mb-3"></i>
                            <p class="mb-0">No hay información de telemetría disponible para este equipo.</p>
                            <p class="text-muted">Para recopilar información detallada, considere instalar un agente de monitoreo en el equipo.</p>
                            
                            <div class="mt-4">
                                <div class="btn-group">
                                    <a href="{{ asset('agent/MonitorAgent.ps1') }}" download class="btn btn-primary">
                                        <i class="fab fa-windows mr-1"></i> Agente Windows
                                    </a>
                                    <a href="{{ asset('agent/MonitorAgent.sh') }}" download class="btn btn-secondary">
                                        <i class="fab fa-linux mr-1"></i> Agente Linux
                                    </a>
                                </div>
                                <div class="mt-2 small text-muted">
                                    <strong>Uso (Windows):</strong> powershell -ExecutionPolicy Bypass -File MonitorAgent.ps1 -ServerUrl "{{ url('/') }}"
                                </div>
                                <div class="small text-muted">
                                    <strong>Uso (Linux):</strong> bash MonitorAgent.sh -s "{{ url('/') }}"
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para ejecutar comandos -->
<div class="modal fade" id="commandModal" tabindex="-1" role="dialog" aria-labelledby="commandModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="commandModalLabel">Ejecutar Comando en {{ $host->hostname }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="command-input">Comando:</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="text" class="form-control" id="command-input" placeholder="Ingrese comando...">
                    </div>
                    <small class="form-text text-muted">Ejemplo: uptime, df -h, free -m</small>
                </div>
                <div class="mt-3 d-none" id="command-result-container">
                    <h6>Resultado:</h6>
                    <div class="bg-dark text-light p-3 rounded" style="max-height: 300px; overflow-y: auto;">
                        <pre id="command-result"></pre>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" id="execute-command">Ejecutar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para eliminar host -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmar eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ¿Está seguro de que desea eliminar el equipo <span id="delete-host-name" class="font-weight-bold">{{ $host->hostname }}</span>?
                <br>
                Esta acción no se puede deshacer.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="delete-form" method="POST" action="{{ route('monitor.destroy', $host->id) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Cerrar dropdown cuando se hace clic fuera de él
    document.addEventListener('click', function(event) {
        var menu = document.getElementById('menu-actions');
        var button = document.querySelector('.dropdown button');
        
        if (menu && menu.classList.contains('show') && 
            !menu.contains(event.target) && 
            !button.contains(event.target)) {
            menu.classList.remove('show');
        }
    });
    
    // Modal para ejecutar comandos
    $('#btn-command').on('click', function() {
        $('#command-result-container').addClass('d-none');
        $('#command-input').val('');
        $('#commandModal').modal('show');
    });
    
    // Modal para eliminar host
    $('#btn-delete, #btn-delete-sm').on('click', function() {
        $('#deleteModal').modal('show');
    });
    
    // Botón de ping
    $('#btn-ping').on('click', function() {
        const hostId = $(this).data('host-id');
        const statusBadge = $('.status-badge');
        
        // Cambiar ícono a animación de carga
        $(this).html('<i class="fas fa-spinner fa-spin fa-sm text-white-50"></i> Actualizando...');
        $(this).prop('disabled', true);
        
        $.ajax({
            url: '/monitor/ping/' + hostId,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                // Actualizar badge de estado
                statusBadge.removeClass('badge-success badge-danger badge-warning');
                statusBadge.find('i').removeClass('fa-check fa-times fa-question');
                
                if (response.status === 'online') {
                    statusBadge.addClass('badge-success');
                    statusBadge.find('i').addClass('fa-check');
                    $('.text-muted').text('En línea');
                } else if (response.status === 'offline') {
                    statusBadge.addClass('badge-danger');
                    statusBadge.find('i').addClass('fa-times');
                    $('.text-muted').text('Fuera de línea');
                } else {
                    statusBadge.addClass('badge-warning');
                    statusBadge.find('i').addClass('fa-question');
                    $('.text-muted').text('Estado desconocido');
                }
                
                // Restaurar botón
                $('#btn-ping').html('<i class="fas fa-sync fa-sm text-white-50"></i> Actualizar Estado');
                $('#btn-ping').prop('disabled', false);
                
                // Recargar la página para actualizar la información
                location.reload();
            },
            error: function(xhr) {
                console.error('Error al hacer ping:', xhr.responseText);
                
                // Restaurar botón
                $('#btn-ping').html('<i class="fas fa-sync fa-sm text-white-50"></i> Actualizar Estado');
                $('#btn-ping').prop('disabled', false);
                
                // Mostrar notificación de error
                showNotification('Error al hacer ping al host', 'error');
            }
        });
    });
    
    $('#execute-command').on('click', function() {
        const command = $('#command-input').val().trim();
        const hostId = $('#btn-command').data('host-id');
        
        if (!command) {
            showNotification('Por favor ingrese un comando', 'warning');
            return;
        }
        
        // Mostrar spinner
        $(this).html('<i class="fas fa-spinner fa-spin"></i> Ejecutando...');
        $(this).prop('disabled', true);
        
        $.ajax({
            url: '/monitor/command/' + hostId,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                command: command
            },
            success: function(response) {
                // Mostrar resultado
                $('#command-result').text(response.output);
                $('#command-result-container').removeClass('d-none');
                
                // Restaurar botón
                $('#execute-command').html('Ejecutar');
                $('#execute-command').prop('disabled', false);
            },
            error: function(xhr) {
                console.error('Error al ejecutar comando:', xhr.responseText);
                
                // Mostrar error
                $('#command-result').text('Error al ejecutar comando: ' + xhr.responseText);
                $('#command-result-container').removeClass('d-none');
                
                // Restaurar botón
                $('#execute-command').html('Ejecutar');
                $('#execute-command').prop('disabled', false);
                
                // Mostrar notificación de error
                showNotification('Error al ejecutar el comando', 'error');
            }
        });
    });
});
</script>
@endsection 