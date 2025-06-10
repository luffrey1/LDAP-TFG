@extends('layouts.dashboard')

@section('title', 'Detalle de mensaje')

@section('content')
<div class="container-fluid">
    <!-- Botones de navegación y acciones -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('dashboard.mensajes', ['tipo' => 'recibidos']) }}" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left mr-1"></i> Volver
            </a>
        </div>
        <div>
            <div class="btn-group">
                @if(!$mensaje['en_papelera'])
                    <form action="{{ route('dashboard.mensajes.destacar', $mensaje['id']) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-link text-warning" title="{{ $mensaje['destacado'] ? 'Quitar de destacados' : 'Añadir a destacados' }}">
                            <i class="fas fa-star {{ $mensaje['destacado'] ? '' : 'text-muted' }}"></i>
                        </button>
                    </form>
                    <a href="{{ route('dashboard.mensajes.responder', $mensaje['id']) }}" class="btn btn-primary">
                        <i class="fas fa-reply mr-1"></i> Responder
                    </a>
                    <a href="{{ route('dashboard.mensajes.reenviar', $mensaje['id']) }}" class="btn btn-outline-primary">
                        <i class="fas fa-share mr-1"></i> Reenviar
                    </a>
                    <form action="{{ route('dashboard.mensajes.eliminar', $mensaje['id']) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-outline-danger" onclick="return confirm('¿Estás seguro de mover este mensaje a la papelera?')">
                            <i class="fas fa-trash mr-1"></i> Eliminar
                        </button>
                    </form>
                @else
                    <form action="{{ route('dashboard.mensajes.restaurar', $mensaje['id']) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-outline-success">
                            <i class="fas fa-undo mr-1"></i> Restaurar
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <!-- Contenido del mensaje -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">{{ $mensaje['asunto'] }}</h6>
        </div>
        <div class="card-body">
            <!-- Información del remitente y destinatarios -->
            <div class="message-header mb-4">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div class="mb-2">
                            <span class="text-muted">De:</span>
                            <strong>{{ $mensaje['remitente']['nombre'] }}</strong>
                            <span class="text-muted">&lt;{{ $mensaje['remitente']['email'] }}&gt;</span>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted">Para:</span>
                            @foreach($mensaje['destinatarios'] as $index => $destinatario)
                                <span>{{ $destinatario['nombre'] }} &lt;{{ $destinatario['email'] }}&gt;{{ $index < count($mensaje['destinatarios']) - 1 ? ',' : '' }}</span>
                            @endforeach
                        </div>
                        @if(!empty($mensaje['cc']))
                            <div class="mb-2">
                                <span class="text-muted">CC:</span>
                                @foreach($mensaje['cc'] as $index => $cc)
                                    <span>{{ $cc['nombre'] }} &lt;{{ $cc['email'] }}&gt;{{ $index < count($mensaje['cc']) - 1 ? ',' : '' }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <div class="text-muted">
                        <small>{{ \Carbon\Carbon::parse($mensaje['fecha'])->format('d/m/Y H:i') }}</small>
                    </div>
                </div>
            </div>

            <!-- Archivos adjuntos -->
            @if(!empty($mensaje['archivos']))
            <div class="attachments mb-4">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-paperclip mr-2"></i>
                    <span class="font-weight-bold">Archivos adjuntos ({{ count($mensaje['archivos']) }})</span>
                </div>
                <div class="row">
                    @foreach($mensaje['archivos'] as $adjunto)
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 bg-white text-dark border-primary">
                            <div class="card-body p-3">
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
                                    <div class="flex-grow-1">
                                        <div class="text-truncate" title="{{ $adjunto['nombre'] }}">
                                            {{ $adjunto['nombre'] }}
                                        </div>
                                        <small class="text-muted">{{ $adjunto['tamano'] }}</small>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <a href="{{ route('dashboard.mensajes.ver', ['id' => $mensaje['id'], 'adjunto' => $adjunto['id']]) }}" class="btn btn-sm btn-outline-primary w-100" target="_blank" rel="noopener">
                                        <i class="fas fa-eye mr-1"></i> Ver
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Contenido del mensaje -->
            <div class="message-content">
                {!! $mensaje['contenido'] !!}
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    .message-content {
        line-height: 1.6;
    }
    .message-content img {
        max-width: 100%;
        height: auto;
    }
    .message-header {
        border-bottom: 1px solid #e3e6f0;
        padding-bottom: 1rem;
    }
    .attachments .card {
        transition: transform 0.2s;
        background: #fff !important;
        color: #222 !important;
        border: 1px solid #4e73df;
    }
    .attachments .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0 8px #4e73df33;
    }
    .attachments .btn-outline-primary {
        color: #224abe;
        border-color: #4e73df;
    }
    .attachments .btn-outline-primary:hover {
        background: #4e73df;
        color: #fff;
    }
</style>
@endpush
@endsection 