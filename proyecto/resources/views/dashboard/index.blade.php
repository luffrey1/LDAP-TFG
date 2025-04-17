@extends('layouts.dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="text-2xl font-bold text-gray-800 mb-4">Dashboard</h1>
            <p class="text-gray-600 mb-6">Bienvenido, {{ session('auth_user')['nombre'] }}. Última conexión: {{ now()->format('d/m/Y H:i') }}</p>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="row mb-6">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2 bg-white rounded">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Usuarios</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['usuarios'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2 bg-white rounded">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Documentos</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['documentos'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2 bg-white rounded">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Mensajes Nuevos</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['mensajes_nuevos'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-envelope fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2 bg-white rounded">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Eventos Próximos</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $stats['eventos_proximos'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Documentos Recientes -->
        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="card shadow mb-4 bg-white rounded">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-white">
                    <h6 class="m-0 font-weight-bold text-primary">Documentos Recientes</h6>
                    <a href="{{ route('dashboard.gestion-documental') }}" class="btn btn-sm btn-primary shadow-sm">
                        <i class="fas fa-arrow-right fa-sm text-white-50"></i> Ver todos
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Subido por</th>
                                    <th>Fecha</th>
                                    <th>Tamaño</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentDocuments as $doc)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @if($doc['extension'] == 'pdf')
                                            <i class="fas fa-file-pdf text-danger mr-2"></i>
                                            @elseif(in_array($doc['extension'], ['docx', 'doc']))
                                            <i class="fas fa-file-word text-primary mr-2"></i>
                                            @elseif(in_array($doc['extension'], ['xlsx', 'xls']))
                                            <i class="fas fa-file-excel text-success mr-2"></i>
                                            @else
                                            <i class="fas fa-file text-gray-500 mr-2"></i>
                                            @endif
                                            {{ $doc['nombre'] }}
                                        </div>
                                    </td>
                                    <td>{{ $doc['subido_por_nombre'] }}</td>
                                    <td>{{ date('d/m/Y', strtotime($doc['fecha_subida'])) }}</td>
                                    <td>{{ $doc['tamaño'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mensajes Recientes -->
        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="card shadow mb-4 bg-white rounded">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-white">
                    <h6 class="m-0 font-weight-bold text-primary">Mensajes Recientes</h6>
                    <a href="{{ route('dashboard.mensajes') }}" class="btn btn-sm btn-primary shadow-sm">
                        <i class="fas fa-arrow-right fa-sm text-white-50"></i> Ver todos
                    </a>
                </div>
                <div class="card-body">
                    @if(isset($recentMessages) && count($recentMessages) > 0)
                    <div class="list-group">
                        @foreach($recentMessages as $mensaje)
                        <a href="{{ route('dashboard.mensajes.ver', $mensaje['id']) }}" class="list-group-item list-group-item-action {{ $mensaje['leido'] ? '' : 'font-weight-bold' }}">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1">{{ $mensaje['asunto'] }}</h5>
                                <small>{{ date('d/m/Y', strtotime($mensaje['fecha'])) }}</small>
                            </div>
                            <p class="mb-1 text-truncate">{{ $mensaje['contenido'] }}</p>
                            <small>De: {{ $mensaje['remitente_nombre'] }}</small>
                            @if(!$mensaje['leido'])
                            <span class="badge badge-primary float-right">Nuevo</span>
                            @endif
                        </a>
                        @endforeach
                    </div>
                    @else
                    <p class="text-center py-3">No hay mensajes recientes.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Eventos Próximos -->
        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="card shadow mb-4 bg-white rounded">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-white">
                    <h6 class="m-0 font-weight-bold text-primary">Eventos Próximos</h6>
                    <a href="{{ route('dashboard.calendario') }}" class="btn btn-sm btn-primary shadow-sm">
                        <i class="fas fa-arrow-right fa-sm text-white-50"></i> Ver calendario
                    </a>
                </div>
                <div class="card-body">
                    @if(isset($upcomingEvents) && count($upcomingEvents) > 0)
                    <div class="list-group">
                        @foreach($upcomingEvents as $evento)
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1">{{ $evento['titulo'] }}</h5>
                            </div>
                            <p class="mb-1">
                                <span class="badge" style="background-color: {{ $evento['color'] }}; color: white;">
                                    <i class="fas fa-calendar-day mr-1"></i>
                                    @if(isset($evento['todo_el_dia']) && $evento['todo_el_dia'])
                                        {{ date('d/m/Y', strtotime($evento['fecha_inicio'])) }} (Todo el día)
                                    @else
                                        {{ date('d/m/Y H:i', strtotime($evento['fecha_inicio'])) }} - 
                                        @if(date('d/m/Y', strtotime($evento['fecha_inicio'])) == date('d/m/Y', strtotime($evento['fecha_fin'])))
                                            {{ date('H:i', strtotime($evento['fecha_fin'])) }}
                                        @else
                                            {{ date('d/m/Y H:i', strtotime($evento['fecha_fin'])) }}
                                        @endif
                                    @endif
                                </span>
                            </p>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-center py-3">No hay eventos próximos.</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Actividad Reciente -->
        <div class="col-xl-6 col-lg-6 mb-4">
            <div class="card shadow mb-4 bg-white rounded">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-white">
                    <h6 class="m-0 font-weight-bold text-primary">Actividad Reciente</h6>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        @if(isset($userActivity) && count($userActivity) > 0)
                            @foreach($userActivity as $activity)
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h3 class="timeline-title">{{ $activity['nombre'] }}</h3>
                                    <p>{{ $activity['accion'] }}: {{ $activity['detalles'] }}</p>
                                    <p class="text-muted small">{{ date('d/m/Y H:i', strtotime($activity['fecha'])) }}</p>
                                </div>
                            </div>
                            @endforeach
                        @else
                            <p class="text-center py-3">No hay actividad reciente.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}
.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}
.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}
.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}
.timeline {
    position: relative;
    padding: 0;
    list-style: none;
}
.timeline-item {
    position: relative;
    padding-left: 2rem;
    margin-bottom: 1rem;
}
.timeline-marker {
    position: absolute;
    left: 0;
    width: 15px;
    height: 15px;
    border-radius: 50%;
    background-color: #4e73df;
    content: "";
}
.timeline-marker:before {
    content: "";
    position: absolute;
    left: 7px;
    height: 100%;
    width: 1px;
    background-color: #e3e6f0;
}
.timeline-item:last-child .timeline-marker:before {
    display: none;
}
.timeline-title {
    font-size: 1rem;
    font-weight: bold;
    margin-bottom: 0.2rem;
}
</style>
@endsection 