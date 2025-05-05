@extends('layouts.dashboard')

@section('title', 'Monitor de Equipos')

@section('content')
<div class="container-fluid">
    <!-- Encabezado de página -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Monitor de Equipos</h1>
        <div>
            <button id="btn-ping-all" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm mr-2">
                <i class="fas fa-sync fa-sm text-white-50"></i> Actualizar Estado
            </button>
            <a href="{{ route('monitor.scan') }}" class="d-none d-sm-inline-block btn btn-sm btn-info shadow-sm mr-2">
                <i class="fas fa-search fa-sm text-white-50"></i> Escanear Red
            </a>
            <a href="{{ route('monitor.create') }}" class="d-none d-sm-inline-block btn btn-sm btn-success shadow-sm">
                <i class="fas fa-plus fa-sm text-white-50"></i> Añadir Equipo
            </a>
        </div>
    </div>

    <!-- Tarjetas de resumen -->
    <div class="row">
        <!-- Total de equipos -->
        <div class="col-xl-3 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="100">
            <div class="card border-left-primary shadow h-100 py-2 stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Equipos</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800 counter" id="total-hosts">{{ count($hosts) }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-desktop fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Equipos en línea -->
        <div class="col-xl-3 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="200">
            <div class="card border-left-success shadow h-100 py-2 stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Equipos Online</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800 counter" id="online-hosts">
                                {{ $hosts->where('status', 'online')->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Equipos desconectados -->
        <div class="col-xl-3 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="300">
            <div class="card border-left-danger shadow h-100 py-2 stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Equipos Offline</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800 counter" id="offline-hosts">
                                {{ $hosts->where('status', 'offline')->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Equipos desconocidos -->
        <div class="col-xl-3 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="400">
            <div class="card border-left-warning shadow h-100 py-2 stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Estado Desconocido</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800 counter" id="unknown-hosts">
                                {{ $hosts->where('status', 'unknown')->count() }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-question-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Equipos -->
    <div class="card shadow mb-4" data-aos="fade-up">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Equipos Monitoreados</h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown">
                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                    <div class="dropdown-header">Acciones:</div>
                    <a class="dropdown-item" href="{{ route('monitor.create') }}">Añadir Equipo</a>
                    <button class="dropdown-item" id="dropdown-ping-all">Actualizar Estado</button>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="#">Exportar Lista</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="hostsTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Dirección IP</th>
                            <th>Estado</th>
                            <th>Último Visto</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($hosts as $host)
                        <tr data-host-id="{{ $host->id }}" class="host-row">
                            <td>{{ $host->hostname }}</td>
                            <td>{{ $host->ip_address }}</td>
                            <td>
                                <span class="status-badge badge 
                                    @if($host->status == 'online') badge-success
                                    @elseif($host->status == 'offline') badge-danger
                                    @else badge-warning
                                    @endif">
                                    {{ $host->status }}
                                </span>
                            </td>
                            <td>
                                @if($host->last_seen)
                                    {{ $host->last_seen->diffForHumans() }}
                                @else
                                    Nunca
                                @endif
                            </td>
                            <td>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-primary btn-sm btn-ping" data-host-id="{{ $host->id }}" title="Ping">
                                        <i class="fas fa-sync"></i>
                                    </button>
                                    <a href="{{ route('monitor.show', $host->id) }}" class="btn btn-info btn-sm" title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button type="button" class="btn btn-warning btn-sm btn-command" data-host-id="{{ $host->id }}" data-host-name="{{ $host->hostname }}" title="Ejecutar comando">
                                        <i class="fas fa-terminal"></i>
                                    </button>
                                    <a href="{{ route('monitor.edit', $host->id) }}" class="btn btn-secondary btn-sm" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-sm btn-delete" data-host-id="{{ $host->id }}" data-host-name="{{ $host->hostname }}" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">No hay equipos registrados. <a href="{{ route('monitor.create') }}">Añadir uno</a>.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para ejecutar comandos -->
<div class="modal fade" id="commandModal" tabindex="-1" role="dialog" aria-labelledby="commandModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="commandModalLabel">Ejecutar Comando</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
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

<!-- Modal para eliminar hosts -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmar eliminación</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                ¿Está seguro de que desea eliminar el equipo <span id="delete-host-name" class="font-weight-bold"></span>?
                <br>
                Esta acción no se puede deshacer.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="delete-form" method="POST">
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
    // DataTables
    $('#hostsTable').DataTable({
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
        },
        order: [[2, 'desc']], // Ordenar por estado
        columnDefs: [
            { orderable: false, targets: 4 } // No ordenar la columna de acciones
        ]
    });
    
    // Función para hacer ping a un host individual
    $('.btn-ping').on('click', function() {
        const hostId = $(this).data('host-id');
        const row = $(this).closest('tr');
        const statusBadge = row.find('.status-badge');
        
        // Cambiar ícono a animación de carga
        $(this).html('<i class="fas fa-spinner fa-spin"></i>');
        
        $.ajax({
            url: "{{ route('monitor.ping', ['id' => '__id__']) }}".replace('__id__', hostId),
            type: 'GET',
            dataType: 'json',
            beforeSend: function() {
                statusBadge.removeClass('badge-success badge-danger badge-warning');
                statusBadge.addClass('badge-warning');
                statusBadge.text('Verificando...');
            },
            success: function(response) {
                // Actualizar badge de estado
                statusBadge.removeClass('badge-warning');
                
                if (response.status === 'online') {
                    statusBadge.addClass('badge-success');
                    updateCounter('online');
                } else if (response.status === 'offline') {
                    statusBadge.addClass('badge-danger');
                    updateCounter('offline');
                } else {
                    statusBadge.addClass('badge-warning');
                    updateCounter('unknown');
                }
                
                statusBadge.text(response.status);
                
                // Restaurar ícono original
                row.find('.btn-ping').html('<i class="fas fa-sync"></i>');
            },
            error: function(xhr) {
                console.error('Error al hacer ping:', xhr.responseText);
                statusBadge.removeClass('badge-success badge-danger badge-warning');
                statusBadge.addClass('badge-danger');
                statusBadge.text('Error');
                
                // Restaurar ícono original
                row.find('.btn-ping').html('<i class="fas fa-sync"></i>');
                
                // Mostrar notificación de error
                toastr.error('Error al hacer ping al host');
            }
        });
    });
    
    // Función para hacer ping a todos los hosts
    $('#btn-ping-all, #dropdown-ping-all').on('click', function() {
        // Cambiar ícono a animación de carga
        const button = $('#btn-ping-all');
        button.html('<i class="fas fa-spinner fa-spin fa-sm text-white-50"></i> Actualizando...');
        button.prop('disabled', true);
        
        $.ajax({
            url: "{{ route('monitor.ping-all') }}",
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                // Recargar la página para mostrar los estados actualizados
                location.reload();
            },
            error: function(xhr) {
                console.error('Error al hacer ping a todos los hosts:', xhr.responseText);
                
                // Restaurar ícono original y habilitar botón
                button.html('<i class="fas fa-sync fa-sm text-white-50"></i> Actualizar Estado');
                button.prop('disabled', false);
                
                // Mostrar notificación de error
                toastr.error('Error al actualizar el estado de los hosts');
            }
        });
    });
    
    // Modal para ejecutar comandos
    let currentHostId = null;
    
    $('.btn-command').on('click', function() {
        currentHostId = $(this).data('host-id');
        const hostname = $(this).data('host-name');
        
        $('#commandModalLabel').text('Ejecutar Comando en ' + hostname);
        $('#command-result-container').addClass('d-none');
        $('#command-input').val('');
        $('#commandModal').modal('show');
    });
    
    $('#execute-command').on('click', function() {
        const command = $('#command-input').val().trim();
        
        if (!command) {
            toastr.warning('Por favor ingrese un comando');
            return;
        }
        
        // Mostrar spinner mientras se ejecuta el comando
        $(this).html('<i class="fas fa-spinner fa-spin"></i> Ejecutando...');
        $(this).prop('disabled', true);
        
        $.ajax({
            url: "{{ route('monitor.command', ['id' => '__id__']) }}".replace('__id__', currentHostId),
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
                
                // Mostrar error en resultado
                $('#command-result').text('Error al ejecutar comando: ' + xhr.responseText);
                $('#command-result-container').removeClass('d-none');
                
                // Restaurar botón
                $('#execute-command').html('Ejecutar');
                $('#execute-command').prop('disabled', false);
                
                // Mostrar notificación de error
                toastr.error('Error al ejecutar el comando');
            }
        });
    });
    
    // Modal para eliminar hosts
    $('.btn-delete').on('click', function() {
        const hostId = $(this).data('host-id');
        const hostname = $(this).data('host-name');
        
        $('#delete-host-name').text(hostname);
        $('#delete-form').attr('action', "{{ route('monitor.destroy', ['id' => '__id__']) }}".replace('__id__', hostId));
        $('#deleteModal').modal('show');
    });
    
    // Función para actualizar contadores
    function updateCounter(status) {
        const totalHosts = parseInt($('#total-hosts').text());
        const onlineHosts = parseInt($('#online-hosts').text());
        const offlineHosts = parseInt($('#offline-hosts').text());
        const unknownHosts = parseInt($('#unknown-hosts').text());
        
        // Resetear todos los contadores
        $('#online-hosts').text(onlineHosts - 1);
        $('#offline-hosts').text(offlineHosts - 1);
        $('#unknown-hosts').text(unknownHosts - 1);
        
        // Incrementar el contador correspondiente
        if (status === 'online') {
            $('#online-hosts').text(onlineHosts + 1);
        } else if (status === 'offline') {
            $('#offline-hosts').text(offlineHosts + 1);
        } else {
            $('#unknown-hosts').text(unknownHosts + 1);
        }
    }
});
</script>
@endsection 