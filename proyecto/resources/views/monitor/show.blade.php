@extends('layouts.dashboard')

@section('title', 'Detalles del Host')

@section('content')
<section class="section">
    <div class="section-header">
        <h1>Detalles del Host: {{ $host->hostname }}</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard.index') }}">Dashboard</a></div>
            <div class="breadcrumb-item"><a href="{{ route('monitor.index') }}">Monitoreo</a></div>
            <div class="breadcrumb-item">{{ $host->hostname }}</div>
        </div>
    </div>

    <div class="section-body">
        <div class="row">
            <div class="col-12">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible show fade">
                        <div class="alert-body">
                            <button class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                            {{ session('success') }}
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible show fade">
                        <div class="alert-body">
                            <button class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                            {{ session('error') }}
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h4>Estado del Host</h4>
                        <div class="card-header-action">
                            <button class="btn btn-primary ping-host" data-id="{{ $host->id }}">
                                <i class="fas fa-sync"></i> Ping
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="mb-3 text-center">
                            <div id="status-badge" class="mb-3">
                                <span class="badge badge-{{ $host->status_color }} badge-pill" style="font-size: 1.2rem; padding: 10px 20px;">
                                    {{ $host->status_text }}
                                </span>
                            </div>
                            
                            <div>
                                @if($host->last_seen)
                                    <p class="mb-0"><strong>Último contacto:</strong></p>
                                    <p id="last-seen">{{ $host->last_seen->format('d/m/Y H:i:s') }} ({{ $host->last_seen->diffForHumans() }})</p>
                                @else
                                    <p id="last-seen" class="text-muted">Sin contacto previo</p>
                                @endif
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <h6 class="text-center">Información Básica</h6>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <tr>
                                        <th style="width: 40%;">Hostname</th>
                                        <td>{{ $host->hostname }}</td>
                                    </tr>
                                    <tr>
                                        <th>Dirección IP</th>
                                        <td>{{ $host->ip_address }}</td>
                                    </tr>
                                    <tr>
                                        <th>MAC Address</th>
                                        <td>{{ $host->mac_address ?? 'No disponible' }}</td>
                                    </tr>
                                    <tr>
                                        <th>Descripción</th>
                                        <td>{{ $host->description ?? 'Sin descripción' }}</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h4>Acciones</h4>
                    </div>
                    <div class="card-body">
                        <div class="buttons">
                            <a href="{{ route('monitor.edit', $host->id) }}" class="btn btn-warning btn-icon icon-left btn-block mb-2">
                                <i class="fas fa-edit"></i> Editar Host
                            </a>
                            <a href="javascript:void(0)" class="btn btn-info btn-icon icon-left btn-block mb-2 ping-host" data-id="{{ $host->id }}">
                                <i class="fas fa-sync"></i> Actualizar Estado
                            </a>
                            <a href="javascript:void(0)" class="btn btn-danger btn-icon icon-left btn-block delete-host" data-id="{{ $host->id }}" data-hostname="{{ $host->hostname }}">
                                <i class="fas fa-trash"></i> Eliminar Host
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-12 col-md-8">
                <div class="row">
                    <!-- Uso de CPU -->
                    <div class="col-md-6 col-sm-6 col-12">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-primary">
                                <i class="fas fa-microchip"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h4>CPU</h4>
                                </div>
                                <div class="card-body" id="cpu-usage">
                                    @if($host->cpu_usage !== null)
                                        {{ $host->cpu_usage }}%
                                    @else
                                        N/A
                                    @endif
                                </div>
                                @if($host->cpu_usage !== null)
                                <div class="progress mb-2" style="height: 6px;">
                                    <div class="progress-bar bg-{{ $host->cpu_color }}" role="progressbar" style="width: {{ $host->cpu_usage }}%" aria-valuenow="{{ $host->cpu_usage }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <!-- Uso de Memoria -->
                    <div class="col-md-6 col-sm-6 col-12">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-warning">
                                <i class="fas fa-memory"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h4>Memoria</h4>
                                </div>
                                <div class="card-body" id="memory-usage">
                                    @if($host->memory_usage !== null)
                                        {{ $host->memory_usage }}%
                                    @else
                                        N/A
                                    @endif
                                </div>
                                @if($host->memory_usage !== null)
                                <div class="progress mb-2" style="height: 6px;">
                                    <div class="progress-bar bg-{{ $host->memory_color }}" role="progressbar" style="width: {{ $host->memory_usage }}%" aria-valuenow="{{ $host->memory_usage }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <!-- Uso de Disco -->
                    <div class="col-md-6 col-sm-6 col-12">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-success">
                                <i class="fas fa-hdd"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h4>Disco</h4>
                                </div>
                                <div class="card-body" id="disk-usage">
                                    @if($host->disk_usage !== null)
                                        {{ $host->disk_usage }}%
                                    @else
                                        N/A
                                    @endif
                                </div>
                                @if($host->disk_usage !== null)
                                <div class="progress mb-2" style="height: 6px;">
                                    <div class="progress-bar bg-{{ $host->disk_color }}" role="progressbar" style="width: {{ $host->disk_usage }}%" aria-valuenow="{{ $host->disk_usage }}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tiempo de actividad -->
                    <div class="col-md-6 col-sm-6 col-12">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-info">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h4>Uptime</h4>
                                </div>
                                <div class="card-body" id="uptime">
                                    @if($host->uptime)
                                        {{ $host->uptime }}
                                    @else
                                        N/A
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Información del Sistema -->
                <div class="card">
                    <div class="card-header">
                        <h4>Información del Sistema</h4>
                    </div>
                    <div class="card-body">
                        @if($host->system_info)
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <tbody>
                                        @foreach($host->system_info as $key => $value)
                                            @if(is_string($value) || is_numeric($value))
                                                <tr>
                                                    <th style="width: 30%;">{{ ucfirst(str_replace('_', ' ', $key)) }}</th>
                                                    <td>{{ $value }}</td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="alert alert-info">
                                No hay información detallada del sistema. Instala un agente de monitoreo en este host para obtener más datos.
                            </div>
                        @endif
                    </div>
                </div>
                
                <!-- Terminal / Consola de comandos -->
                <div class="card">
                    <div class="card-header">
                        <h4>Consola de Comandos</h4>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" id="command-input" placeholder="Ingresa un comando...">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="button" id="send-command">Ejecutar</button>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Resultado:</label>
                            <textarea class="form-control" id="command-output" rows="8" readonly style="font-family: monospace; background-color: #000; color: #fff;"></textarea>
                        </div>
                        <div class="text-muted">
                            <small>Nota: La ejecución de comandos remotos requiere un agente instalado en el host.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modal de Confirmación para Eliminar -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmar Eliminación</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que deseas eliminar <span id="delete-hostname"></span>?</p>
                <p class="text-danger">Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <form id="deleteForm" method="POST">
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
<script>
$(document).ready(function() {
    // Ping al host
    $('.ping-host').on('click', function() {
        var hostId = $(this).data('id');
        var statusBadge = $('#status-badge span');
        
        $.ajax({
            url: '/monitor/ping/' + hostId,
            type: 'GET',
            dataType: 'json',
            beforeSend: function() {
                statusBadge.removeClass().addClass('badge badge-warning badge-pill').html('Verificando...');
            },
            success: function(response) {
                if (response.status === 'online') {
                    statusBadge.removeClass().addClass('badge badge-success badge-pill').html('En línea');
                } else if (response.status === 'offline') {
                    statusBadge.removeClass().addClass('badge badge-danger badge-pill').html('Desconectado');
                } else {
                    statusBadge.removeClass().addClass('badge badge-warning badge-pill').html('Error');
                }
                
                // Actualizar la hora del último contacto
                var now = new Date();
                var dateStr = now.getDate() + '/' + (now.getMonth() + 1) + '/' + now.getFullYear() + ' ' + 
                              now.getHours() + ':' + now.getMinutes() + ':' + now.getSeconds();
                $('#last-seen').text(dateStr + ' (Justo ahora)');
                
                iziToast.info({
                    title: 'Ping',
                    message: response.message,
                    position: 'topRight'
                });
            },
            error: function(xhr, status, error) {
                statusBadge.removeClass().addClass('badge badge-danger badge-pill').html('Error');
                
                iziToast.error({
                    title: 'Error',
                    message: 'No se pudo verificar el estado del host',
                    position: 'topRight'
                });
            }
        });
    });
    
    // Enviar comando
    $('#send-command').on('click', function() {
        var command = $('#command-input').val();
        var output = $('#command-output');
        
        if (!command) {
            return;
        }
        
        $.ajax({
            url: '/monitor/{{ $host->id }}/command',
            type: 'POST',
            data: {
                command: command,
                _token: '{{ csrf_token() }}'
            },
            dataType: 'json',
            beforeSend: function() {
                output.val('Ejecutando comando: ' + command + '...\n');
            },
            success: function(response) {
                if (response.status === 'success') {
                    output.val(output.val() + '\n' + response.output + '\n\n');
                } else {
                    output.val(output.val() + '\nError: ' + response.message + '\n\n');
                }
                output.scrollTop(output[0].scrollHeight);
            },
            error: function(xhr, status, error) {
                output.val(output.val() + '\nError en la solicitud: ' + error + '\n\n');
                output.scrollTop(output[0].scrollHeight);
            }
        });
        
        // Limpiar el input
        $('#command-input').val('');
    });
    
    // Enter para enviar comando
    $('#command-input').on('keypress', function(e) {
        if (e.which === 13) {
            $('#send-command').click();
        }
    });
    
    // Configuración del modal de eliminación
    $('.delete-host').on('click', function() {
        var hostId = $(this).data('id');
        var hostname = $(this).data('hostname');
        
        $('#delete-hostname').text(hostname);
        $('#deleteForm').attr('action', '/monitor/' + hostId);
        
        $('#deleteModal').modal('show');
    });
});
</script>
@endsection 