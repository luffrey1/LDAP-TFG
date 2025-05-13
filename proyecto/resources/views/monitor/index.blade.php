@extends('layouts.dashboard')

@section('title', 'Monitoreo de Equipos')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="fas fa-desktop me-2"></i> Monitoreo de Equipos</h4>
                        <div>
                            <a href="{{ route('monitor.groups.index') }}" class="btn btn-light btn-sm me-2">
                                <i class="fas fa-layer-group me-1"></i> Grupos de Aulas
                            </a>
                            <a href="{{ route('monitor.scan') }}" class="btn btn-warning btn-sm">
                                <i class="fas fa-search me-1"></i> Escanear Red
                            </a>
                        </div>
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

                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card bg-primary text-white h-100">
                                <div class="card-body text-center">
                                    <h1 class="display-4">{{ count($hosts) }}</h1>
                                    <p class="lead">Total Equipos</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card bg-success text-white h-100">
                                <div class="card-body text-center">
                                    <h1 class="display-4">{{ $hosts->where('status', 'online')->count() }}</h1>
                                    <p class="lead">En Línea</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card bg-danger text-white h-100">
                                <div class="card-body text-center">
                                    <h1 class="display-4">{{ $hosts->where('status', 'offline')->count() }}</h1>
                                    <p class="lead">Desconectados</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card bg-info text-white h-100">
                                <div class="card-body text-center">
                                    <h1 class="display-4">{{ $groups->count() }}</h1>
                                    <p class="lead">Grupos de Aulas</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0"><i class="fas fa-list me-2"></i> Listado de Equipos</h5>
                                        <div>
                                            <a href="{{ route('monitor.create') }}" class="btn btn-success btn-sm me-2">
                                                <i class="fas fa-plus me-1"></i> Añadir Equipo
                                            </a>
                                            <a href="{{ route('monitor.ping-all') }}" class="btn btn-primary btn-sm">
                                                <i class="fas fa-sync-alt me-1"></i> Actualizar Estado
                                            </a>
                                            <a href="{{ route('monitor.refresh-network') }}" class="btn btn-info btn-sm">
                                                <i class="fas fa-network-wired me-1"></i> Actualizar Routers
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    @if(count($hosts) > 0)
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Estado</th>
                                                        <th>Hostname</th>
                                                        <th>IP</th>
                                                        <th>Grupo/Aula</th>
                                                        <th>Último visto</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($hosts as $host)
                                                        <tr>
                                                            <td>
                                                                <span class="badge bg-{{ $host->status_color }}">
                                                                    <i class="fas fa-{{ $host->status == 'online' ? 'check-circle' : ($host->status == 'offline' ? 'times-circle' : 'exclamation-circle') }} me-1"></i>
                                                                    {{ $host->status_text }}
                                                                </span>
                                                            </td>
                                                            <td>{{ $host->hostname }}</td>
                                                            <td>{{ $host->ip_address }}</td>
                                                            <td>
                                                                @if($host->group)
                                                                    <a href="{{ route('monitor.groups.show', $host->group_id) }}" class="badge bg-info text-decoration-none">
                                                                        <i class="fas fa-{{ $host->group->type == 'classroom' ? 'chalkboard-teacher' : 'building' }} me-1"></i>
                                                                        {{ $host->group->name }}
                                                                    </a>
                                                                @else
                                                                    <span class="badge bg-secondary">Sin grupo</span>
                                                                @endif
                                                            </td>
                                                            <td>{{ $host->last_seen ? $host->last_seen->format('d/m/Y H:i:s') : 'Nunca' }}</td>
                                                            <td>
                                                                <div class="btn-group" role="group">
                                                                    <a href="{{ route('monitor.show', $host->id) }}" class="btn btn-sm btn-primary" title="Ver detalles">
                                                                        <i class="fas fa-eye"></i>
                                                                    </a>
                                                                    <a href="{{ route('monitor.ping', $host->id) }}" class="btn btn-sm btn-info" title="Ping">
                                                                        <i class="fas fa-exchange-alt"></i>
                                                                    </a>
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
                                                                    <button type="button" class="btn btn-sm btn-danger" 
                                                                            onclick="eliminarHost({{ $host->id }}, '{{ $host->hostname }}')"
                                                                            title="Eliminar">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </div>
                                                                <form id="form-eliminar-{{ $host->id }}" action="{{ route('monitor.destroy', $host->id) }}" method="POST" style="display: none;">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i> No hay equipos registrados para monitorear.
                                            <a href="{{ route('monitor.create') }}" class="alert-link">Haga clic aquí para añadir uno</a> o
                                            <a href="{{ route('monitor.scan') }}" class="alert-link">ejecute un escaneo de red</a>.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    @if(count($groups) > 0)
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-light">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0"><i class="fas fa-layer-group me-2"></i> Grupos de Equipos</h5>
                                        <a href="{{ route('monitor.groups.index') }}" class="btn btn-primary btn-sm">
                                            <i class="fas fa-external-link-alt me-1"></i> Ver Todos
                                        </a>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        @foreach($groups->take(3) as $group)
                                            <div class="col-md-4 mb-3">
                                                <div class="card h-100 border-{{ $group->is_active ? 'success' : 'secondary' }}">
                                                    <div class="card-header bg-{{ $group->is_active ? 'success' : 'secondary' }} text-white">
                                                        <h5 class="mb-0">
                                                            <i class="fas fa-{{ $group->type == 'classroom' ? 'chalkboard-teacher' : 'building' }} me-2"></i>
                                                            {{ $group->name }}
                                                            <span class="badge bg-light text-dark float-end">
                                                                {{ $group->total_hosts_count }} equipos
                                                            </span>
                                                        </h5>
                                                    </div>
                                                    <div class="card-body">
                                                        <p>{{ \Illuminate\Support\Str::limit($group->description, 100) ?: 'Sin descripción' }}</p>
                                                        <div class="progress" style="height: 20px;">
                                                            @php
                                                                $online_percentage = $group->total_hosts_count > 0 
                                                                    ? round(($group->online_hosts_count / $group->total_hosts_count) * 100) 
                                                                    : 0;
                                                            @endphp
                                                            <div class="progress-bar bg-success" 
                                                                style="width: {{ $online_percentage }}%;" 
                                                                aria-valuenow="{{ $online_percentage }}" 
                                                                aria-valuemin="0" 
                                                                aria-valuemax="100">
                                                                {{ $online_percentage }}% En Línea
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="card-footer bg-light">
                                                        <a href="{{ route('monitor.groups.show', $group->id) }}" class="btn btn-primary btn-sm">
                                                            <i class="fas fa-eye me-1"></i> Ver
                                                        </a>
                                                        <a href="{{ route('monitor.groups.wol', $group->id) }}" 
                                                           class="btn btn-dark btn-sm float-end" 
                                                           onclick="return confirm('¿Estás seguro que deseas enviar Wake-on-LAN a todos los equipos de este grupo?');">
                                                            <i class="fas fa-power-off me-1"></i> WOL
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
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
        // Cerrar alertas automáticamente después de 5 segundos
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);
    });
    
    function eliminarHost(id, nombre) {
        if (confirm('¿Está seguro que desea eliminar el equipo "' + nombre + '"? Esta acción no se puede deshacer.')) {
            document.getElementById('form-eliminar-' + id).submit();
        }
    }
</script>
@endsection 