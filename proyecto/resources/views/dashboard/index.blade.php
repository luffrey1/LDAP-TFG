@extends('layouts.dashboard')

@section('title', 'Dashboard - Panel de Control')

@section('content')
<div class="container-fluid">
    <!-- Cabecera -->
    <div class="d-flex justify-content-between align-items-center mb-4" data-aos="fade-up">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Panel de Control</h1>
            <p class="text-muted">Bienvenido, <span class="fw-bold">{{ session('auth_user')['nombre'] }}</span>. Última conexión: {{ now()->format('d/m/Y H:i') }}</p>
        </div>
        <div>
            <button class="btn btn-primary shadow-sm" id="refreshStats">
                <i class="fas fa-sync-alt fa-sm text-white-50 me-1"></i> Actualizar
            </button>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="100">
            <div class="card border-left-primary shadow h-100 py-2 stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Usuarios</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800 counter">{{ $stats['usuarios'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 5px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: 85%" aria-valuenow="85" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="200">
            <div class="card border-left-success shadow h-100 py-2 stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Documentos</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800 counter">{{ $stats['documentos'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 5px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 70%" aria-valuenow="70" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="300">
            <div class="card border-left-info shadow h-100 py-2 stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Mensajes Nuevos</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800 counter">{{ $stats['mensajes_nuevos'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-envelope fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 5px;">
                        <div class="progress-bar bg-info" role="progressbar" style="width: 50%" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="400">
            <div class="card border-left-warning shadow h-100 py-2 stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Eventos Próximos</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800 counter">{{ $stats['eventos_proximos'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 5px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: 65%" aria-valuenow="65" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Documentos Recientes -->
        <div class="col-xl-6 col-lg-6 mb-4" data-aos="fade-up" data-aos-delay="100">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-file-alt me-2"></i>Documentos Recientes
                    </h6>
                    <a href="{{ route('dashboard.gestion-documental') }}" class="btn btn-sm btn-primary shadow-sm">
                        <i class="fas fa-arrow-right fa-sm text-white-50 me-1"></i> Ver todos
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-borderless" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Categoría</th>
                                    <th>Subido por</th>
                                    <th>Fecha</th>
                                    <th>Tamaño</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($recentDocuments) && count($recentDocuments) > 0)
                                    @foreach($recentDocuments as $doc)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                @if($doc['extension'] == 'pdf')
                                                <div class="icon-circle bg-danger text-white me-2">
                                                    <i class="fas fa-file-pdf"></i>
                                                </div>
                                                @elseif(in_array($doc['extension'], ['docx', 'doc']))
                                                <div class="icon-circle bg-primary text-white me-2">
                                                    <i class="fas fa-file-word"></i>
                                                </div>
                                                @elseif(in_array($doc['extension'], ['xlsx', 'xls']))
                                                <div class="icon-circle bg-success text-white me-2">
                                                    <i class="fas fa-file-excel"></i>
                                                </div>
                                                @else
                                                <div class="icon-circle bg-secondary text-white me-2">
                                                    <i class="fas fa-file"></i>
                                                </div>
                                                @endif
                                                <span class="text-truncate">{{ $doc['nombre'] }}</span>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-info">{{ ucfirst($doc['carpeta']) }}</span></td>
                                        <td>{{ $doc['subido_por_nombre'] }}</td>
                                        <td>{{ date('d/m/Y', strtotime($doc['fecha_subida'])) }}</td>
                                        <td>{{ $doc['tamaño'] }}</td>
                                    </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="5" class="text-center">No hay documentos recientes</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mensajes Recientes -->
        <div class="col-xl-6 col-lg-6 mb-4" data-aos="fade-up" data-aos-delay="200">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-envelope me-2"></i>Mensajes Recientes
                    </h6>
                    <a href="{{ route('dashboard.mensajes') }}" class="btn btn-sm btn-primary shadow-sm">
                        <i class="fas fa-arrow-right fa-sm text-white-50 me-1"></i> Ver todos
                    </a>
                </div>
                <div class="card-body p-0">
                    @if(count($recentMessages) > 0)
                        <div class="list-group list-group-flush">
                            @foreach($recentMessages as $mensaje)
                            <a href="{{ route('dashboard.mensajes.ver', $mensaje['id']) }}" class="list-group-item list-group-item-action px-4 py-3 {{ $mensaje['leido'] ? '' : 'bg-light' }}">
                                <div class="d-flex w-100 justify-content-between align-items-center">
                                    <h6 class="mb-1 {{ $mensaje['leido'] ? '' : 'fw-bold' }}">{{ $mensaje['asunto'] }}</h6>
                                    <small class="text-muted">{{ date('d/m/Y', strtotime($mensaje['fecha'])) }}</small>
                                </div>
                                <p class="mb-1 text-truncate text-muted small">{{ $mensaje['contenido'] }}</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-primary">
                                        <i class="fas fa-user-circle me-1"></i>{{ $mensaje['remitente_nombre'] }}
                                    </small>
                                    @if(!$mensaje['leido'])
                                    <span class="badge bg-primary">Nuevo</span>
                                    @endif
                                </div>
                            </a>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-gray-300 mb-3"></i>
                            <p>No hay mensajes recientes.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Eventos Próximos -->
        <div class="col-xl-6 col-lg-6 mb-4" data-aos="fade-up" data-aos-delay="150">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-calendar-alt me-2"></i>Eventos Próximos
                    </h6>
                    <a href="{{ route('dashboard.calendario') }}" class="btn btn-sm btn-primary shadow-sm">
                        <i class="fas fa-arrow-right fa-sm text-white-50 me-1"></i> Ver calendario
                    </a>
                </div>
                <div class="card-body p-0">
                    @if(isset($upcomingEvents) && count($upcomingEvents) > 0)
                    <div class="list-group list-group-flush">
                        @foreach($upcomingEvents as $evento)
                        <div class="list-group-item px-4 py-3">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <h6 class="mb-1 fw-semibold">{{ $evento['titulo'] }}</h6>
                                <div class="event-badge" style="background-color: {{ $evento['color'] }}"></div>
                            </div>
                            <p class="mb-1 d-flex align-items-center">
                                <i class="fas fa-clock text-muted me-2"></i>
                                <span class="small">
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
                    <div class="text-center py-5">
                        <i class="fas fa-calendar fa-3x text-gray-300 mb-3"></i>
                        <p>No hay eventos próximos.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Actividad Reciente -->
        <div class="col-xl-6 col-lg-6 mb-4" data-aos="fade-up" data-aos-delay="250">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-history me-2"></i>Actividad Reciente
                    </h6>
                </div>
                <div class="card-body">
                    @if(isset($userActivity) && count($userActivity) > 0)
                    <div class="timeline">
                        @foreach($userActivity as $activity)
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h5 class="timeline-title">{{ $activity['nombre'] }}</h5>
                                <p class="text-muted mb-1">{{ $activity['accion'] }}: {{ $activity['detalles'] }}</p>
                                <p class="text-primary small">{{ date('d/m/Y H:i', strtotime($activity['fecha'])) }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i class="fas fa-history fa-3x text-gray-300 mb-3"></i>
                        <p>No hay actividad reciente.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    .icon-circle {
        height: 2.5rem;
        width: 2.5rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    
    .event-badge {
        width: 1rem;
        height: 1rem;
        border-radius: 50%;
    }
    
    .border-left-primary {
        border-left: 0.25rem solid var(--primary-color) !important;
    }
    
    .border-left-success {
        border-left: 0.25rem solid var(--success-color) !important;
    }
    
    .border-left-info {
        border-left: 0.25rem solid var(--info-color) !important;
    }
    
    .border-left-warning {
        border-left: 0.25rem solid var(--warning-color) !important;
    }
    
    .counter {
        transition: all 1s ease;
    }
</style>
@endsection

@section('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/countup.js/2.0.8/countUp.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar contadores
        const counters = document.querySelectorAll('.counter');
        counters.forEach(counter => {
            const value = parseInt(counter.innerText);
            const countUp = new CountUp(counter, value, {
                duration: 2.5,
                useEasing: true,
                useGrouping: true
            });
            
            if (!countUp.error) {
                countUp.start();
            }
        });
        
        // Función para actualizar estadísticas (simulado)
        document.getElementById('refreshStats').addEventListener('click', function() {
            this.disabled = true;
            const button = this;
            
            // Agregar ícono de spinner
            this.innerHTML = '<i class="fas fa-spinner fa-spin fa-sm text-white-50 me-1"></i> Actualizando...';
            
            // Simular actualización de datos
            setTimeout(function() {
                button.innerHTML = '<i class="fas fa-sync-alt fa-sm text-white-50 me-1"></i> Actualizar';
                button.disabled = false;
                
                // Mostrar notificación
                showNotification('Datos actualizados correctamente', 'success');
            }, 1500);
        });
    });
</script>
@endsection 