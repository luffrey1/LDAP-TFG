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

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> Información del Grupo</h5>
                                </div>
                                <div class="card-body">
                                    <dl class="row mb-0">
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
                                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i> Estadísticas</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <div class="card bg-primary text-white">
                                                <div class="card-body py-3 text-center">
                                                    <h3 class="mb-0">{{ $group->total_hosts_count }}</h3>
                                                    <p class="mb-0">Total de Equipos</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <div class="card bg-success text-white">
                                                <div class="card-body py-3 text-center">
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
                                                    <button class="btn btn-sm btn-info ping-host" data-host-id="{{ $host->id }}" title="Ping">
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
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Función para hacer ping a un host
        $('.ping-host').click(function() {
            var hostId = $(this).data('host-id');
            var button = $(this);
            button.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
            $.ajax({
                url: '/monitor/ping/' + hostId,
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    // Recargar la página para mostrar el estado actualizado
                    location.reload();
                },
                error: function() {
                    button.html('<i class="fas fa-exchange-alt"></i>').prop('disabled', false);
                    alert('Error al realizar ping');
                }
            });
        });
        
        // Actualizar estado de todos los hosts
        $('#refresh-status').click(function() {
            var button = $(this);
            button.html('<i class="fas fa-spinner fa-spin me-1"></i> Actualizando...').prop('disabled', true);
            
            $('.ping-host').each(function() {
                $(this).click();
            });
            
            setTimeout(function() {
                button.html('<i class="fas fa-sync-alt me-1"></i> Actualizar Estado').prop('disabled', false);
            }, 2000);
        });
        
        // Cerrar alertas automáticamente después de 5 segundos
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);
    });
</script>
@endsection 