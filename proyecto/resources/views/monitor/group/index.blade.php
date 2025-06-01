@extends('layouts.dashboard')

@section('title', 'Grupos de Monitoreo')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-layer-group me-2"></i> Grupos de Monitoreo
                    </h4>
                </div>
                <div class="card-body">
                    <div class="mb-4 d-flex justify-content-between align-items-center">
                        <div>
                            <a href="{{ route('monitor.groups.create') }}" class="btn btn-success">
                                <i class="fas fa-plus me-1"></i> Nuevo Grupo
                            </a>
                            <a href="{{ route('monitor.index') }}" class="btn btn-secondary ms-2">
                                <i class="fas fa-desktop me-1"></i> Ver Equipos
                            </a>
                        </div>
                        <div class="text-end">
                            <small class="text-white">Total: {{ count($groups) }} grupos</small>
                        </div>
                    </div>

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

                    @if(count($groups) > 0)
                        <div class="row">
                            @foreach($groups as $group)
                                <div class="col-md-4 mb-4">
                                    <div class="card h-100 border-{{ $group->is_active ? 'success' : 'secondary' }}">
                                        <div class="card-header bg-{{ $group->is_active ? 'success' : 'secondary' }} text-white d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0 text-white"><i class="fas fa-{{ $group->type == 'classroom' ? 'chalkboard-teacher' : 'building' }} me-2"></i> {{ $group->name }}</h5>
                                            <span class="badge bg-light text-dark">
                                                <i class="fas fa-desktop me-1"></i> {{ $group->total_hosts_count }}
                                            </span>
                                        </div>
                                        <div class="card-body">
                                            <p class="card-text text-white">{{ $group->description ?? 'Sin descripción' }}</p>
                                            
                                            <div class="d-flex justify-content-between align-items-center text-white">
                                                <div>
                                                    <span class="badge bg-success me-1">
                                                        <i class="fas fa-check-circle me-1"></i> {{ $group->online_hosts_count }} en línea
                                                    </span>
                                                </div>
                                                <small class="text-white">{{ $group->location ?? 'Ubicación no especificada' }}</small>
                                            </div>
                                        </div>
                                        <div class="card-footer bg-light d-flex justify-content-between">
                                            <div>
                                                <a href="{{ route('monitor.groups.show', $group->id) }}" class="btn btn-sm btn-primary me-1">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('monitor.groups.edit', $group->id) }}" class="btn btn-sm btn-warning me-1">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('monitor.groups.destroy', $group->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Estás seguro que deseas eliminar este grupo?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                            <a href="{{ route('monitor.groups.wol', $group->id) }}" 
                                               class="btn btn-sm btn-dark" 
                                               onclick="return confirm('¿Estás seguro que deseas enviar Wake-on-LAN a todos los equipos de este grupo?');">
                                                <i class="fas fa-power-off me-1"></i> WOL
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> No hay grupos de monitoreo configurados.
                            <a href="{{ route('monitor.groups.create') }}" class="alert-link">¡Crea el primero ahora!</a>
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
</script>
@endsection 