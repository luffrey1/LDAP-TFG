@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Encabezado de página -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
    </div>

    @php
        $moduloMensajeriaActivo = \App\Models\SistemaConfig::obtenerConfig('modulo_mensajeria_activo', true);
        $moduloCalendarioActivo = \App\Models\SistemaConfig::obtenerConfig('modulo_calendario_activo', true);
        $moduloDocumentosActivo = \App\Models\SistemaConfig::obtenerConfig('modulo_documentos_activo', true);
        $isAdmin = session('auth_user.is_admin') || session('auth_user.username') === 'ldap-admin';
    @endphp

    <!-- Tarjetas de estadísticas -->
    <div class="row">
        <!-- Usuarios -->
        <div class="col-xl-3 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="100">
            <div class="card border-left-primary shadow h-100 py-2 stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Usuarios</div>
                            <div class="h5 mb-0 font-weight-bold text-white counter">{{ $stats['usuarios'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-users fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 5px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: 80%" aria-valuenow="80" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>

        @if($moduloDocumentosActivo)
        <!-- Documentos -->
        <div class="col-xl-3 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="200">
            <div class="card border-left-success shadow h-100 py-2 stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Documentos</div>
                            <div class="h5 mb-0 font-weight-bold text-white counter">{{ $stats['documentos'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 5px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 60%" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($moduloMensajeriaActivo)
        <!-- Mensajes Nuevos -->
        <div class="col-xl-3 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="300">
            <div class="card border-left-info shadow h-100 py-2 stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Mensajes Nuevos</div>
                            <div class="h5 mb-0 font-weight-bold text-white counter">{{ $stats['mensajes_nuevos'] }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-envelope fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 5px;">
                        <div class="progress-bar bg-info" role="progressbar" style="width: 70%" aria-valuenow="70" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @if($moduloCalendarioActivo)
        <!-- Eventos Próximos -->
        <div class="col-xl-3 col-md-6 mb-4" data-aos="fade-up" data-aos-delay="400">
            <div class="card border-left-warning shadow h-100 py-2 stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Eventos Próximos</div>
                            <div class="h5 mb-0 font-weight-bold text-white">
                                <span class="counter">{{ $stats['eventos_proximos'] }}</span>
                                <small class="text-muted ml-2">en los próximos 7 días</small>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 5px;">
                        <div class="progress-bar bg-warning" role="progressbar" 
                            style="width: {{ min(($stats['eventos_proximos'] / 10) * 100, 100) }}%" 
                            aria-valuenow="{{ $stats['eventos_proximos'] }}" 
                            aria-valuemin="0" 
                            aria-valuemax="10">
                        </div>
                    </div>
                    <div class="mt-2 text-xs text-muted">
                        <i class="fas fa-info-circle mr-1"></i>
                        {{ $stats['eventos_proximos'] > 0 ? 'Tienes eventos programados' : 'No hay eventos próximos' }}
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="row">
        @if($moduloDocumentosActivo)
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
                <div class="card-body p-0">
                    @if(isset($recentDocuments) && count($recentDocuments) > 0)
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Categoría</th>
                                    <th>Subido por</th>
                                    <th>Fecha</th>
                                    <th>Tamaño</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentDocuments as $document)
                                <tr>
                                    <td class="fw-semibold">{{ $document['nombre'] }}</td>
                                    <td><span class="badge bg-light text-dark">{{ ucfirst($document['carpeta']) }}</span></td>
                                    <td>{{ $document['subido_por_nombre'] }}</td>
                                    <td>{{ date('d/m/Y', strtotime($document['fecha_subida'])) }}</td>
                                    <td>{{ $document['tamaño'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i class="fas fa-file fa-3x text-gray-300 mb-3"></i>
                        <p>No hay documentos recientes.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        @if($moduloMensajeriaActivo)
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
                    @if(isset($recentMessages) && count($recentMessages) > 0)
                    <div class="list-group list-group-flush">
                        @foreach($recentMessages as $message)
                        <div class="list-group-item p-3 position-relative">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">{{ $message['asunto'] }}</h6>
                                <small class="text-muted">{{ date('d/m/Y', strtotime($message['fecha'])) }}</small>
                            </div>
                            <p class="mb-1 text-truncate">{{ strip_tags($message['contenido']) }}</p>
                            <small>De: {{ $message['remitente_nombre'] }}</small>
                            @if(!$message['leido'])
                            <span class="position-absolute top-0 start-100 translate-middle p-1 bg-primary rounded-circle">
                                <span class="visually-hidden">Nuevo mensaje</span>
                            </span>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-5">
                        <i class="fas fa-envelope fa-3x text-gray-300 mb-3"></i>
                        <p>No hay mensajes recientes.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="row">
        @if($moduloCalendarioActivo)
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
                            
                            @if(isset($evento['descripcion']) && !empty($evento['descripcion']))
                            <p class="mb-2 text-muted small">{{ \Illuminate\Support\Str::limit($evento['descripcion'], 100) }}</p>
                            @endif
                            
                            <div class="d-flex justify-content-between align-items-center">
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
                                
                                <span class="badge bg-light text-dark">{{ $evento['tipo_evento'] ?? 'Evento' }}</span>
                            </div>
                            
                            <div class="mt-1 d-flex justify-content-between align-items-center">
                                <span class="text-muted small">
                                    <i class="fas fa-user me-1"></i> {{ $evento['creador_nombre'] ?? 'Usuario' }}
                                </span>
                                <a href="{{ route('dashboard.calendario') }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye me-1"></i> Ver
                                </a>
                            </div>
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
        @endif

        <!-- Actividad Reciente -->
        <div class="col-xl-6 col-lg-6 mb-4" data-aos="fade-up" data-aos-delay="350">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-history me-2"></i>Actividad Reciente
                    </h6>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        @if(isset($userActivity) && count($userActivity) > 0)
                            @foreach($userActivity as $activity)
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content text-white">
                                    <h5 class="timeline-title">{{ $activity['nombre'] }} {{ $activity['accion'] }}</h5>
                                    <p class="mb-1">{{ $activity['detalles'] }}</p>
                                    <small class="text-muted">{{ date('d/m/Y H:i', strtotime($activity['fecha'])) }}</small>
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="text-center py-3">
                                <i class="fas fa-info-circle fa-2x text-gray-300 mb-3"></i>
                                <p>No hay actividad reciente.</p>
                            </div>
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
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar contadores con CountUp.js
        const counterElements = document.querySelectorAll('.counter');
        counterElements.forEach(function(el) {
            const value = parseInt(el.textContent);
            // La versión UMD usa una sintaxis ligeramente diferente
            const counter = new countUp.CountUp(el, value, {
                duration: 2.5,
                useEasing: true,
                useGrouping: true,
                separator: ',',
                decimal: '.'
            });
            counter.start();
        });
        
        // Función para actualizar estadísticas (simulado)
        const refreshBtn = document.getElementById('refreshStats');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
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
        }
    });
</script>
@endsection 