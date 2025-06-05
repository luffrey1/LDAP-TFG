@extends('layouts.dashboard')

@section('title', 'Detalles del Grupo ' . $group->name)

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-{{ $group->type == 'classroom' ? 'chalkboard-teacher' : 'building' }} me-2"></i> 
                        Grupo: {{ $group->name }}
                    </h4>
                    <div>
                        <a href="{{ route('monitor.groups.index') }}" class="btn btn-sm btn-light me-1">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                        <a href="{{ route('monitor.groups.edit', $group->id) }}" class="btn btn-sm btn-warning me-1">
                            <i class="fas fa-edit me-1"></i> Editar
                        </a>
                        <a href="{{ route('monitor.groups.wol', $group->id) }}" 
                           class="btn btn-sm btn-dark" 
                           onclick="return confirm('¿Estás seguro que deseas enviar Wake-on-LAN a todos los equipos de este grupo?');">
                            <i class="fas fa-power-off me-1"></i> WOL
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                        </div>
                    @endif

                    @if(isset($group))
                    <form action="{{ route('monitor.group.clean', $group->id) }}" method="POST" onsubmit="return confirm('¿Seguro que quieres borrar todos los equipos de esta clase?');">
                        @csrf
                        <button type="submit" class="btn btn-danger mb-3">
                            <i class="fas fa-trash"></i> Limpiar clase (borrar todos los equipos)
                        </button>
                    </form>
                    @endif

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0 text-black"><i class="fas fa-info-circle me-2 text-black"></i> Información del Grupo</h5>
                                </div>
                                <div class="card-body">
                                    <dl class="row mb-0 text-white">
                                        <dt class="col-sm-4">Nombre:</dt>
                                        <dd class="col-sm-8">{{ $group->name }}</dd>
                                        
                                        <dt class="col-sm-4">Descripción:</dt>
                                        <dd class="col-sm-8">{{ $group->description ?? 'Sin descripción' }}</dd>
                                        
                                        <dt class="col-sm-4">Tipo:</dt>
                                        <dd class="col-sm-8">
                                            <span class="badge bg-info">
                                                <i class="fas fa-{{ $group->type == 'classroom' ? 'chalkboard-teacher' : ($group->type == 'lab' ? 'flask' : ($group->type == 'office' ? 'briefcase' : 'building')) }} me-1"></i>
                                                {{ $group->type == 'classroom' ? 'Aula' : ($group->type == 'lab' ? 'Laboratorio' : ($group->type == 'office' ? 'Oficina' : ($group->type == 'department' ? 'Departamento' : 'Otro'))) }}
                                            </span>
                                        </dd>
                                        
                                        <dt class="col-sm-4">Ubicación:</dt>
                                        <dd class="col-sm-8">{{ $group->location ?? 'No especificada' }}</dd>
                                        
                                        <dt class="col-sm-4">Creado:</dt>
                                        <dd class="col-sm-8">{{ $group->created_at->format('d/m/Y H:i') }}</dd>
                                        
                                        <dt class="col-sm-4">Última actualización:</dt>
                                        <dd class="col-sm-8">{{ $group->updated_at->format('d/m/Y H:i') }}</dd>
                                    </dl>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0 text-black"><i class="fas fa-chart-pie me-2"></i> Estadísticas</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="card bg-primary text-white">
                                                <div class="card-body py-3 text-center text-white">
                                                    <h3 class="mb-0">{{ $group->total_hosts_count }}</h3>
                                                    <p class="mb-0">Total de Equipos</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="card bg-success text-white">
                                                <div class="card-body py-3 text-center text-white">
                                                    <h3 class="mb-0">{{ $group->online_hosts_count }}</h3>
                                                    <p class="mb-0">Equipos En Línea</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="progress mt-3" style="height: 25px;">
                                        @php
                                            $online_percentage = $group->total_hosts_count > 0 
                                                ? round(($group->online_hosts_count / $group->total_hosts_count) * 100) 
                                                : 0;
                                        @endphp
                                        <div class="progress-bar bg-success" 
                                             role="progressbar" 
                                             style="width: {{ $online_percentage }}%;" 
                                             aria-valuenow="{{ $online_percentage }}" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100">
                                            {{ $online_percentage }}% En Línea
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-desktop me-2"></i> Equipos del Grupo
                    </h4>
                    <div>
                        <button id="refresh-status" class="btn btn-sm btn-light me-1">
                            <i class="fas fa-sync-alt me-1"></i> Actualizar Estado
                        </button>
                        <a href="{{ route('monitor.create') }}?group_id={{ $group->id }}" class="btn btn-sm btn-success">
                            <i class="fas fa-plus me-1"></i> Añadir Equipo
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($hosts->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Estado</th>
                                        <th>Hostname</th>
                                        <th>IP</th>
                                        <th>MAC</th>
                                        <th>Último visto</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($hosts as $host)
                                        <tr id="host-row-{{ $host->id }}">
                                            <td>
                                                <span class="badge bg-{{ $host->status_color }} host-status" data-host-id="{{ $host->id }}">
                                                    <i class="fas fa-{{ $host->status == 'online' ? 'check-circle' : ($host->status == 'offline' ? 'times-circle' : 'exclamation-circle') }} me-1"></i>
                                                    {{ $host->status_text }}
                                                </span>
                                            </td>
                                            <td>{{ $host->hostname }}</td>
                                            <td>{{ $host->ip_address }}</td>
                                            <td>{{ $host->mac_address ?? 'No disponible' }}</td>
                                            <td>{{ $host->last_seen ? $host->last_seen->format('d/m/Y H:i:s') : 'Nunca' }}</td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('monitor.show', $host->id) }}" class="btn btn-sm btn-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-info btn-ping" 
                                                            data-host-id="{{ $host->id }}"
                                                            data-hostname="{{ $host->hostname }}"
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#updateStatusModal"
                                                            title="Ping">
                                                        <i class="fas fa-exchange-alt"></i>
                                                    </button>
                                                    @if($host->mac_address)
                                                        <a href="{{ route('monitor.wol', $host->id) }}" 
                                                           class="btn btn-sm btn-dark" 
                                                           onclick="return confirm('¿Enviar señal Wake-on-LAN a {{ $host->hostname }}?');" 
                                                           title="Wake-on-LAN">
                                                            <i class="fas fa-power-off"></i>
                                                        </a>
                                                    @endif
                                                    <a href="{{ route('monitor.edit', $host->id) }}" class="btn btn-sm btn-warning" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form action="{{ route('monitor.destroy', $host->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Estás seguro que deseas eliminar este host?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> Este grupo no tiene equipos asignados. 
                            <a href="{{ route('monitor.create') }}?group_id={{ $group->id }}" class="alert-link">Haga clic aquí para añadir uno</a>.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de actualización de estado -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateStatusModalLabel">Actualizar Estado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Seleccione el tipo de escaneo:</label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="scanType" id="scanByHostname" value="hostname" checked>
                        <label class="form-check-label" for="scanByHostname">
                            Escanear por Hostname (para equipos de aula)
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="scanType" id="scanByIP" value="ip">
                        <label class="form-check-label" for="scanByIP">
                            Escanear por IP (para equipos de infraestructura)
                        </label>
                    </div>
                </div>
                <div id="scanProgress" style="display: none;">
                    <div class="d-flex align-items-center mb-3">
                        <div class="spinner-border spinner-border-sm me-2" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <span id="scanStatus">Detectando...</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="startScanBtn">
                    <i class="fas fa-sync-alt me-1"></i> Iniciar Escaneo
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
@push('scripts')
<script>
    $(document).ready(function() {
        // Configuración global de AJAX para incluir token CSRF
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        let currentHostId = null;
        let currentHostname = null;
        let scanInProgress = false;

        // Función para resetear el estado del escaneo
        function resetScanState() {
            $('#scanProgress').hide();
            $('#scanStatus').text('Detectando...');
            $('.progress-bar').css('width', '0%');
            $('#startScanBtn').prop('disabled', false);
            scanInProgress = false;
        }

        // Función para actualizar el estado
        function updateStatus(hostId = null) {
            if (scanInProgress) return;
            
            const scanType = $('input[name="scanType"]:checked').val();
            const url = hostId ? `/monitor/ping/${hostId}` : "{{ route('monitor.ping-all') }}";
            const data = hostId ? { scan_type: scanType } : { group: "{{ $group->id }}", scan_type: scanType };
            
            // Mostrar progreso
            $('#scanProgress').show();
            $('#startScanBtn').prop('disabled', true);
            scanInProgress = true;
            $('.progress-bar').css('width', '0%');
            $('#scanStatus').html('<i class="fas fa-spinner fa-spin me-2"></i>Detectando...');

            // Realizar la petición AJAX
            $.ajax({
                url: url,
                method: 'POST',
                data: data,
                success: function(response) {
                    $('.progress-bar').css('width', '100%');
                    $('#scanStatus').html('<i class="fas fa-check-circle me-2 text-success"></i>Escaneo completado');
                    
                    if (hostId) {
                        // Actualizar estado individual
                        const statusBadge = $(`#host-row-${hostId} .host-status`);
                        statusBadge.removeClass('bg-danger bg-warning').addClass('bg-success');
                        statusBadge.html(`
                            <i class="fas fa-check-circle me-1"></i>
                            Online
                        `);
                        
                        // Cerrar el modal después de 1 segundo
                        setTimeout(() => {
                            $('#updateStatusModal').modal('hide');
                        }, 1000);
                    } else {
                        // Recargar la página para actualizar todos los estados
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    }
                },
                error: function(xhr) {
                    let errorMessage = 'Error al actualizar el estado';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    }
                    
                    $('#scanStatus').html(`<i class="fas fa-exclamation-circle me-2 text-danger"></i>${errorMessage}`);
                    
                    if (hostId) {
                        // Actualizar estado individual
                        const statusBadge = $(`#host-row-${hostId} .host-status`);
                        statusBadge.removeClass('bg-success bg-warning').addClass('bg-danger');
                        statusBadge.html(`
                            <i class="fas fa-times-circle me-1"></i>
                            Offline
                        `);
                    }
                },
                complete: function() {
                    scanInProgress = false;
                    $('#startScanBtn').prop('disabled', false);
                }
            });
        }

        // Manejar el clic en el botón de ping individual
        $('.btn-ping').on('click', function() {
            currentHostId = $(this).data('host-id');
            currentHostname = $(this).data('hostname');
            $('#updateStatusModal .modal-title').text(`Actualizar estado de ${currentHostname}`);
            resetScanState();
        });

        // Manejar el clic en el botón de actualizar estado del grupo
        $('#refresh-status').on('click', function() {
            currentHostId = null;
            currentHostname = null;
            $('#updateStatusModal .modal-title').text('Actualizar estado del grupo');
            resetScanState();
        });

        // Manejar el clic en el botón de inicio de escaneo
        $('#startScanBtn').on('click', function() {
            if (!scanInProgress) {
                updateStatus(currentHostId);
            }
        });
        
        // Resetear el modal cuando se cierra
        $('#updateStatusModal').on('hidden.bs.modal', function() {
            resetScanState();
            currentHostId = null;
            currentHostname = null;
        });

        // Cerrar alertas automáticamente después de 5 segundos
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);
    });
</script>
@endpush
@endsection 