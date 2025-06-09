@extends('layouts.dashboard')

@section('title', 'Mensajes')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            @if($tipo == 'recibidos')
                <i class="fas fa-inbox mr-2"></i> Bandeja de entrada
            @elseif($tipo == 'enviados')
                <i class="fas fa-paper-plane mr-2"></i> Mensajes enviados
            @elseif($tipo == 'destacados')
                <i class="fas fa-star mr-2"></i> Mensajes destacados
            @elseif($tipo == 'papelera')
                <i class="fas fa-trash mr-2"></i> Papelera
            @endif
        </h1>
        <a href="{{ route('dashboard.mensajes.nuevo') }}" class="btn btn-primary">
            <i class="fas fa-pen mr-1"></i> Nuevo mensaje
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    @endif

    <div class="row">
        <!-- Sidebar de Mensajes -->
        <div class="col-md-3 mb-4">
            <div class="card shadow mb-4">
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <a href="{{ route('dashboard.mensajes', ['tipo' => 'recibidos']) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $tipo == 'recibidos' ? 'active' : '' }}">
                            <div>
                                <i class="fas fa-inbox mr-2"></i> Recibidos
                            </div>
                            <span class="badge badge-primary badge-pill">{{ $contadores['recibidos'] }}</span>
                        </a>
                        @if($contadores['no_leidos'] > 0)
                        <a href="{{ route('dashboard.mensajes', ['tipo' => 'recibidos', 'filter' => 'no_leidos']) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-envelope mr-2"></i> No leídos
                            </div>
                            <span class="badge badge-danger badge-pill">{{ $contadores['no_leidos'] }}</span>
                        </a>
                        @endif
                        <a href="{{ route('dashboard.mensajes', ['tipo' => 'recibidos', 'filter' => 'recientes']) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ (request('filter') == 'recientes') ? 'active' : '' }}">
                            <div>
                                <i class="fas fa-clock mr-2"></i> Recientes
                            </div>
                            <span class="badge badge-info badge-pill">{{ $contadores['recientes'] }}</span>
                        </a>
                        <a href="{{ route('dashboard.mensajes', ['tipo' => 'destacados']) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $tipo == 'destacados' ? 'active' : '' }}">
                            <div>
                                <i class="fas fa-star mr-2"></i> Destacados
                            </div>
                            <span class="badge badge-primary badge-pill">{{ $contadores['destacados'] }}</span>
                        </a>
                        <a href="{{ route('dashboard.mensajes', ['tipo' => 'enviados']) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $tipo == 'enviados' ? 'active' : '' }}">
                            <div>
                                <i class="fas fa-paper-plane mr-2"></i> Enviados
                            </div>
                            <span class="badge badge-primary badge-pill">{{ $contadores['enviados'] }}</span>
                        </a>
                        <a href="{{ route('dashboard.mensajes', ['tipo' => 'papelera']) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center {{ $tipo == 'papelera' ? 'active' : '' }}">
                            <div>
                                <i class="fas fa-trash mr-2"></i> Papelera
                            </div>
                            <span class="badge badge-primary badge-pill">{{ $contadores['papelera'] }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Mensajes -->
        <div class="col-md-9">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        @if($tipo == 'recibidos')
                            Mensajes recibidos
                        @elseif($tipo == 'enviados')
                            Mensajes enviados
                        @elseif($tipo == 'destacados')
                            Mensajes destacados
                        @elseif($tipo == 'papelera')
                            Mensajes en papelera
                        @endif
                    </h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                            <div class="dropdown-header">Opciones:</div>
                            <a class="dropdown-item" href="{{ route('dashboard.mensajes', ['tipo' => $tipo, 'sort' => 'recent']) }}">
                                <i class="fas fa-sort-amount-down mr-2"></i> Más recientes primero
                            </a>
                            <a class="dropdown-item" href="{{ route('dashboard.mensajes', ['tipo' => $tipo, 'sort' => 'oldest']) }}">
                                <i class="fas fa-sort-amount-up mr-2"></i> Más antiguos primero
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="{{ route('dashboard.mensajes', ['tipo' => $tipo]) }}">
                                <i class="fas fa-sync-alt mr-2"></i> Actualizar
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if(count($mensajes) > 0)
                        <div class="list-group list-group-flush">
                            @foreach($mensajes as $mensaje)
                                <a href="{{ route('dashboard.mensajes.ver', $mensaje['id']) }}" class="list-group-item list-group-item-action {{ !$mensaje['leido'] ? 'font-weight-bold bg-light' : '' }}">
                                    <div class="d-flex w-100 justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            @if(isset($mensaje['remitente']))
                                                <div class="avatar-circle mr-3">
                                                    @if(isset($mensaje['remitente']['avatar']))
                                                        <img src="{{ asset($mensaje['remitente']['avatar']) }}" alt="{{ $mensaje['remitente']['nombre'] }}" class="rounded-circle" width="50" height="50">
                                                    @else
                                                        <div class="avatar-initial rounded-circle d-flex justify-content-center align-items-center bg-primary text-white" style="width: 50px; height: 50px;">
                                                            {{ strtoupper(substr($mensaje['remitente']['nombre'], 0, 1)) }}
                                                        </div>
                                                    @endif
                                                </div>
                                                <div>
                                                    <h5 class="mb-1">{{ $mensaje['remitente']['nombre'] }}</h5>
                                            @else
                                                <div class="avatar-circle mr-3">
                                                    @if(count($mensaje['destinatarios']) == 1)
                                                        <div class="avatar-initial rounded-circle d-flex justify-content-center align-items-center bg-info text-white" style="width: 50px; height: 50px;">
                                                            {{ strtoupper(substr($mensaje['destinatarios'][0]['nombre'], 0, 1)) }}
                                                        </div>
                                                    @else
                                                        <div class="avatar-initial rounded-circle d-flex justify-content-center align-items-center bg-info text-white" style="width: 50px; height: 50px;">
                                                            <i class="fas fa-users"></i>
                                                        </div>
                                                    @endif
                                                </div>
                                                <div>
                                                    <h5 class="mb-1">
                                                        Para:
                                                        @foreach($mensaje['destinatarios'] as $index => $destinatario)
                                                            {{ $destinatario['nombre'] }}{{ $index < count($mensaje['destinatarios']) - 1 ? ', ' : '' }}
                                                        @endforeach
                                                    </h5>
                                            @endif
                                                    <p class="mb-1">
                                                        @if($mensaje['destacado'])
                                                            <i class="fas fa-star text-warning mr-1"></i>
                                                        @endif
                                                        <strong>{{ $mensaje['asunto'] }}</strong> - 
                                                        {{ \Illuminate\Support\Str::limit(strip_tags($mensaje['contenido']), 100) }}
                                                    </p>
                                                </div>
                                        </div>
                                        <div class="text-right">
                                            <small class="text-muted">{{ \Carbon\Carbon::parse($mensaje['fecha'])->format('d/m/Y H:i') }}</small>
                                            @if(!empty($mensaje['adjuntos']))
                                                <br><small><i class="fas fa-paperclip text-muted"></i> {{ count($mensaje['adjuntos']) }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-5 text-white">
                            <img src="{{ asset('img/undraw_empty.svg') }}" alt="No hay mensajes" class="img-fluid mb-3" style="max-width: 200px;">
                            <h4 class="text-gray-500">No hay mensajes</h4>
                            <p class="text-gray-500">
                                @if($tipo == 'recibidos')
                                    Tu bandeja de entrada está vacía
                                @elseif($tipo == 'enviados')
                                    No has enviado ningún mensaje aún
                                @elseif($tipo == 'destacados')
                                    No tienes mensajes destacados
                                @elseif($tipo == 'papelera')
                                    La papelera está vacía
                                @endif
                            </p>
                            @if($tipo != 'enviados')
                                <a href="{{ route('dashboard.mensajes.nuevo') }}" class="btn btn-primary">
                                    <i class="fas fa-pen mr-1"></i> Escribir un mensaje
                                </a>
                            @endif
                        </div>
                    @endif
                </div>
                @if(count($mensajes) > 0)
                <div class="card-footer bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted">Mostrando {{ count($mensajes) }} mensajes</span>
                        </div>
                        <div>
                            <!-- Paginación (simulada) -->
                            <nav aria-label="Navegación de mensajes">
                                <ul class="pagination pagination-sm mb-0">
                                    <li class="page-item disabled">
                                        <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Anterior</a>
                                    </li>
                                    <li class="page-item active" aria-current="page">
                                        <a class="page-link" href="#">1</a>
                                    </li>
                                    <li class="page-item disabled">
                                        <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Siguiente</a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Marcar como leído al hacer clic
        $('.list-group-item-action').click(function() {
            $(this).removeClass('font-weight-bold bg-light');
        });
        
        // Animación para nuevos mensajes
        $('.list-group-item-action.font-weight-bold').each(function() {
            $(this).append('<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 8px;">Nuevo</span>');
        });
    });
</script>
@endsection

@section('styles')
<style>
    .avatar-initial {
        font-weight: bold;
        font-size: 1.5rem;
    }
    .list-group-item-action {
        background: #fff !important;
        color: #222 !important;
        border-bottom: 1px solid #e3e6f0;
    }
    .list-group-item-action.active {
        background-color: #4e73df !important;
        color: #fff !important;
        border-color: #4e73df !important;
    }
    .list-group-item-action.font-weight-bold.bg-light {
        background: #eaf0fb !important;
        color: #222 !important;
        font-weight: bold;
    }
    .list-group-item-action:hover {
        transform: translateY(-1px);
        box-shadow: 0 .125rem .25rem rgba(0,0,0,.075);
        transition: all .2s ease-in-out;
        background: #f1f3fa !important;
    }
    .badge-primary, .badge-info, .badge-danger {
        color: #fff !important;
        font-weight: bold;
        background: #4e73df !important;
    }
    .badge-danger {
        background: #e74a3b !important;
    }
    .badge-info {
        background: #36b9cc !important;
    }
    .card-header, .card-body, .card-footer {
        background: #fff !important;
        color: #222 !important;
    }
    .text-gray-500, .text-white {
        color: #222 !important;
    }
    .btn-primary {
        background: #4e73df;
        border-color: #4e73df;
    }
    .btn-primary:hover {
        background: #224abe;
        border-color: #224abe;
    }
</style>
@endsection 