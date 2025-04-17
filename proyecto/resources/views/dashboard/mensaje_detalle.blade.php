@extends('layouts.dashboard')

@section('title', 'Detalle de mensaje')

@section('content')
<div class="container-fluid">
    <!-- Botones de navegación y acciones -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('mensajes.index', ['tipo' => 'recibidos']) }}" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left mr-1"></i> Volver
            </a>
        </div>
        <div>
            <div class="btn-group">
                @if(!$mensaje['en_papelera'])
                    <form action="{{ route('mensajes.destroy', $mensaje['id']) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger" onclick="return confirm('¿Estás seguro de mover este mensaje a la papelera?')">
                            <i class="fas fa-trash mr-1"></i> Eliminar
                        </button>
                    </form>
                @else
                    <form action="{{ route('mensajes.restore', $mensaje['id']) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-success">
                            <i class="fas fa-trash-restore mr-1"></i> Restaurar
                        </button>
                    </form>
                    <form action="{{ route('mensajes.destroy', $mensaje['id']) }}" method="POST" class="d-inline ml-2">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="permanent" value="1">
                        <button type="submit" class="btn btn-outline-danger" onclick="return confirm('¿Estás seguro de eliminar permanentemente este mensaje?')">
                            <i class="fas fa-times mr-1"></i> Eliminar permanentemente
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <!-- Mensaje -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">
                {{ $mensaje['asunto'] }}
            </h6>
            <div>
                <form action="{{ route('mensajes.toggle-starred', $mensaje['id']) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-link text-warning" title="{{ $mensaje['destacado'] ? 'Quitar de destacados' : 'Añadir a destacados' }}">
                        <i class="fas fa-star {{ $mensaje['destacado'] ? '' : 'text-muted' }}"></i>
                    </button>
                </form>
                <div class="dropdown d-inline">
                    <button class="btn btn-link text-gray-500" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdownMenuButton">
                        <a class="dropdown-item" href="{{ route('mensajes.reply', $mensaje['id']) }}">
                            <i class="fas fa-reply mr-2"></i> Responder
                        </a>
                        <a class="dropdown-item" href="{{ route('mensajes.forward', $mensaje['id']) }}">
                            <i class="fas fa-share mr-2"></i> Reenviar
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="javascript:window.print()">
                            <i class="fas fa-print mr-2"></i> Imprimir
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-body">
            <!-- Información del remitente -->
            <div class="d-flex align-items-center mb-4">
                @if(isset($mensaje['remitente']['avatar']))
                    <img src="{{ asset($mensaje['remitente']['avatar']) }}" alt="{{ $mensaje['remitente']['nombre'] }}" class="rounded-circle mr-3" width="50" height="50">
                @else
                    <div class="avatar-initial rounded-circle d-flex justify-content-center align-items-center bg-primary text-white mr-3" style="width: 50px; height: 50px;">
                        {{ strtoupper(substr($mensaje['remitente']['nombre'], 0, 1)) }}
                    </div>
                @endif
                <div>
                    <h5 class="font-weight-bold mb-0">{{ $mensaje['remitente']['nombre'] }}</h5>
                    <p class="text-muted mb-0">
                        {{ $mensaje['remitente']['email'] }}
                        @if(isset($mensaje['remitente']['departamento']))
                            · {{ $mensaje['remitente']['departamento'] }}
                        @endif
                    </p>
                </div>
            </div>

            <!-- Destinatarios y fecha -->
            <div class="d-flex justify-content-between mb-4">
                <div>
                    <p class="mb-1">
                        <span class="text-muted">Para:</span>
                        @foreach($mensaje['destinatarios'] as $index => $destinatario)
                            {{ $destinatario['nombre'] }}{{ $index < count($mensaje['destinatarios']) - 1 ? ', ' : '' }}
                        @endforeach
                    </p>
                    @if(!empty($mensaje['cc']))
                        <p class="mb-1">
                            <span class="text-muted">CC:</span>
                            @foreach($mensaje['cc'] as $index => $cc)
                                {{ $cc['nombre'] }}{{ $index < count($mensaje['cc']) - 1 ? ', ' : '' }}
                            @endforeach
                        </p>
                    @endif
                </div>
                <div class="text-right">
                    <p class="text-muted mb-0">{{ \Carbon\Carbon::parse($mensaje['fecha'])->format('d/m/Y H:i') }}</p>
                </div>
            </div>

            <!-- Contenido del mensaje -->
            <div class="mensaje-contenido mb-4">
                {!! $mensaje['contenido'] !!}
            </div>

            <!-- Archivos adjuntos -->
            @if(!empty($mensaje['adjuntos']))
                <div class="card mb-4">
                    <div class="card-header bg-light py-2">
                        <h6 class="mb-0">
                            <i class="fas fa-paperclip mr-1"></i> 
                            Archivos adjuntos ({{ count($mensaje['adjuntos']) }})
                        </h6>
                    </div>
                    <div class="card-body py-2">
                        <div class="row">
                            @foreach($mensaje['adjuntos'] as $adjunto)
                                <div class="col-md-4 mb-2">
                                    <div class="card">
                                        <div class="card-body p-2">
                                            <div class="d-flex align-items-center">
                                                <div class="mr-3">
                                                    @if(in_array($adjunto['tipo'], ['jpg', 'jpeg', 'png', 'gif']))
                                                        <i class="fas fa-file-image fa-2x text-primary"></i>
                                                    @elseif(in_array($adjunto['tipo'], ['pdf']))
                                                        <i class="fas fa-file-pdf fa-2x text-danger"></i>
                                                    @elseif(in_array($adjunto['tipo'], ['doc', 'docx']))
                                                        <i class="fas fa-file-word fa-2x text-primary"></i>
                                                    @elseif(in_array($adjunto['tipo'], ['xls', 'xlsx']))
                                                        <i class="fas fa-file-excel fa-2x text-success"></i>
                                                    @elseif(in_array($adjunto['tipo'], ['ppt', 'pptx']))
                                                        <i class="fas fa-file-powerpoint fa-2x text-warning"></i>
                                                    @elseif(in_array($adjunto['tipo'], ['zip', 'rar']))
                                                        <i class="fas fa-file-archive fa-2x text-secondary"></i>
                                                    @else
                                                        <i class="fas fa-file fa-2x text-info"></i>
                                                    @endif
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 text-truncate" style="max-width: 150px;" title="{{ $adjunto['nombre'] }}">{{ $adjunto['nombre'] }}</h6>
                                                    <small class="text-muted">{{ $adjunto['tamano'] }}</small>
                                                </div>
                                            </div>
                                            <div class="mt-2 text-right">
                                                <a href="#" class="btn btn-sm btn-outline-primary" title="Descargar">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <a href="#" class="btn btn-sm btn-outline-secondary" title="Ver">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Historial de mensajes (si es una conversación) -->
            @if(!empty($mensaje['historial']))
                <div class="card">
                    <div class="card-header bg-light py-2">
                        <a data-toggle="collapse" href="#collapseHistorial" role="button" aria-expanded="false" aria-controls="collapseHistorial" class="text-decoration-none text-dark">
                            <h6 class="mb-0 d-flex justify-content-between align-items-center">
                                <span><i class="fas fa-history mr-1"></i> Historial de la conversación ({{ count($mensaje['historial']) }})</span>
                                <i class="fas fa-chevron-down"></i>
                            </h6>
                        </a>
                    </div>
                    <div class="collapse" id="collapseHistorial">
                        <div class="card-body">
                            @foreach($mensaje['historial'] as $historico)
                                <div class="border-bottom pb-3 mb-3">
                                    <div class="d-flex justify-content-between mb-2">
                                        <div>
                                            <strong>{{ $historico['remitente']['nombre'] }}</strong>
                                            <span class="text-muted">escribió:</span>
                                        </div>
                                        <small class="text-muted">{{ \Carbon\Carbon::parse($historico['fecha'])->format('d/m/Y H:i') }}</small>
                                    </div>
                                    <div class="pl-3 border-left">
                                        {!! $historico['contenido'] !!}
                                        
                                        @if(!empty($historico['adjuntos']))
                                            <div class="mt-2">
                                                <small class="text-muted"><i class="fas fa-paperclip mr-1"></i> {{ count($historico['adjuntos']) }} adjuntos</small>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
        <div class="card-footer bg-white py-3">
            <div class="row">
                <div class="col-md-6">
                    <a href="{{ route('mensajes.reply', $mensaje['id']) }}" class="btn btn-primary">
                        <i class="fas fa-reply mr-1"></i> Responder
                    </a>
                    <a href="{{ route('mensajes.forward', $mensaje['id']) }}" class="btn btn-secondary ml-2">
                        <i class="fas fa-share mr-1"></i> Reenviar
                    </a>
                </div>
                <div class="col-md-6 text-right">
                    <a href="#" class="btn btn-light" onclick="window.print(); return false;">
                        <i class="fas fa-print mr-1"></i> Imprimir
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    /* Estilos para impresión */
    @media print {
        .sidebar, .topbar, .card-header, .card-footer, .btn-group {
            display: none !important;
        }
        .container-fluid {
            padding: 0 !important;
            margin: 0 !important;
            width: 100% !important;
        }
        .card {
            border: none !important;
            box-shadow: none !important;
        }
    }
    
    /* Estilos para el contenido del mensaje */
    .mensaje-contenido {
        font-size: 1rem;
        line-height: 1.6;
    }
    
    .mensaje-contenido p {
        margin-bottom: 1rem;
    }
    
    .mensaje-contenido blockquote {
        border-left: 3px solid #e3e6f0;
        padding-left: 1rem;
        color: #6c757d;
        margin-left: 0;
    }
    
    /* Animación para los botones de acción */
    .btn:hover {
        transform: translateY(-2px);
        transition: transform 0.2s;
    }
</style>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Marcar el mensaje como leído
        $.post('{{ route("mensajes.mark-read", $mensaje["id"]) }}');
        
        // Animación para el historial
        $('[data-toggle="collapse"]').click(function() {
            $(this).find('i.fas.fa-chevron-down').toggleClass('fa-chevron-up');
        });
    });
</script>
@endsection 