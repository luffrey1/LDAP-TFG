@extends('layouts.dashboard')

@section('title', 'Detalle del Mensaje - Área Privada')

@section('content')
<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="mt-4">Detalle del mensaje</h1>
        <div>
            <a href="{{ route('mensajes.index') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Volver a la bandeja
            </a>
        </div>
    </div>
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-envelope-open me-1"></i>
                {{ $mensaje['asunto'] }}
            </div>
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-outline-primary" title="Responder">
                    <i class="fas fa-reply"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary" title="Reenviar">
                    <i class="fas fa-share"></i>
                </button>
                <form action="{{ route('mensajes.destroy', $mensaje['id']) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Está seguro que desea eliminar este mensaje?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
            </div>
        </div>
        <div class="card-body">
            <div class="mb-4 border-bottom pb-3">
                <div class="d-flex align-items-center mb-2">
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-weight: bold;">
                        {{ strtoupper(substr($mensaje['remitente'], 0, 1)) }}
                    </div>
                    <div>
                        <h5 class="mb-0">{{ $mensaje['remitente'] }}</h5>
                        <div class="text-muted small">
                            {{ $mensaje['fecha'] }} {{ $mensaje['hora'] }}
                        </div>
                    </div>
                </div>
                <div class="ms-5 ps-2">
                    <span class="text-muted">Para: Mi nombre</span>
                </div>
            </div>
            
            <div class="mensaje-contenido mb-4">
                {!! nl2br(e($mensaje['contenido'])) !!}
            </div>
            
            @if(count($mensaje['archivos']) > 0)
                <div class="archivos-adjuntos border-top pt-3">
                    <h6 class="mb-3">
                        <i class="fas fa-paperclip me-1"></i>
                        Archivos adjuntos ({{ count($mensaje['archivos']) }})
                    </h6>
                    <div class="row">
                        @foreach($mensaje['archivos'] as $archivo)
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card h-100">
                                    <div class="card-body py-2 px-3">
                                        <div class="d-flex align-items-center">
                                            @php
                                                $iconClass = 'fas fa-file-alt';
                                                if(preg_match('/\.(jpg|jpeg|png|gif)$/i', $archivo['nombre'])) {
                                                    $iconClass = 'fas fa-file-image';
                                                } elseif(preg_match('/\.(pdf)$/i', $archivo['nombre'])) {
                                                    $iconClass = 'fas fa-file-pdf';
                                                } elseif(preg_match('/\.(doc|docx)$/i', $archivo['nombre'])) {
                                                    $iconClass = 'fas fa-file-word';
                                                } elseif(preg_match('/\.(xls|xlsx)$/i', $archivo['nombre'])) {
                                                    $iconClass = 'fas fa-file-excel';
                                                } elseif(preg_match('/\.(ppt|pptx)$/i', $archivo['nombre'])) {
                                                    $iconClass = 'fas fa-file-powerpoint';
                                                } elseif(preg_match('/\.(zip|rar|7z)$/i', $archivo['nombre'])) {
                                                    $iconClass = 'fas fa-file-archive';
                                                }
                                            @endphp
                                            <i class="{{ $iconClass }} fa-2x text-primary me-3"></i>
                                            <div class="flex-grow-1">
                                                <p class="mb-0 text-truncate">{{ $archivo['nombre'] }}</p>
                                                <small class="text-muted">{{ $archivo['tamaño'] }}</small>
                                            </div>
                                            <div class="btn-group">
                                                <a href="#" class="btn btn-sm btn-outline-secondary" title="Ver" data-bs-toggle="modal" data-bs-target="#previewModal{{ $loop->index }}">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="#" class="btn btn-sm btn-outline-primary" title="Descargar">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Modal de vista previa para el archivo -->
                            <div class="modal fade" id="previewModal{{ $loop->index }}" tabindex="-1" aria-labelledby="previewModalLabel{{ $loop->index }}" aria-hidden="true">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="previewModalLabel{{ $loop->index }}">{{ $archivo['nombre'] }}</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            @if(preg_match('/\.(jpg|jpeg|png|gif)$/i', $archivo['nombre']))
                                                <div class="text-center">
                                                    <img src="{{ asset('img/placeholder.png') }}" alt="{{ $archivo['nombre'] }}" class="img-fluid">
                                                </div>
                                            @elseif(preg_match('/\.(pdf)$/i', $archivo['nombre']))
                                                <div class="ratio ratio-16x9">
                                                    <iframe src="{{ asset('img/placeholder-pdf.html') }}" allowfullscreen></iframe>
                                                </div>
                                            @else
                                                <div class="text-center py-5">
                                                    <i class="{{ $iconClass }} fa-4x text-muted mb-3"></i>
                                                    <p>La vista previa no está disponible para este tipo de archivo.</p>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                            <a href="#" class="btn btn-primary">Descargar</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
        <div class="card-footer">
            <div class="d-flex justify-content-between">
                <div>
                    <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">
                        <i class="fas fa-arrow-left me-1"></i> Volver
                    </button>
                </div>
                <div>
                    <button type="button" class="btn btn-primary me-2">
                        <i class="fas fa-reply me-1"></i> Responder
                    </button>
                    <button type="button" class="btn btn-outline-primary">
                        <i class="fas fa-share me-1"></i> Reenviar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Respuesta -->
<div class="modal fade" id="replyModal" tabindex="-1" aria-labelledby="replyModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="replyModalLabel">Responder mensaje</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('mensajes.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="respuesta_a" value="{{ $mensaje['id'] }}">
                <input type="hidden" name="destinatario" value="{{ $mensaje['remitente_id'] }}">
                <input type="hidden" name="asunto" value="RE: {{ $mensaje['asunto'] }}">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="contenido" class="form-label">Mensaje</label>
                        <textarea class="form-control" id="contenido" name="contenido" rows="10" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="archivos" class="form-label">Adjuntar archivos</label>
                        <input class="form-control" type="file" id="archivos" name="adjuntos[]" multiple>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Enviar respuesta</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Activar modales para las previsualizaciones de archivos
        const previewModals = document.querySelectorAll('[data-bs-toggle="modal"]');
        previewModals.forEach(function(element) {
            element.addEventListener('click', function(e) {
                e.preventDefault();
            });
        });
        
        // Configurar botones de responder para abrir el modal
        const replyButtons = document.querySelectorAll('button[title="Responder"]');
        replyButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const replyModal = new bootstrap.Modal(document.getElementById('replyModal'));
                replyModal.show();
            });
        });
    });
</script>
@endsection 