@extends('layouts.dashboard')

@section('title', 'Ver Mensaje')

@section('content')
<div class="container-fluid">
    <!-- Botones de navegación -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('dashboard.mensajes') }}" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-1"></i> Volver a bandeja de entrada
            </a>
        </div>
        <div>
            <div class="btn-group">
                <a href="{{ route('dashboard.mensajes.responder', $mensaje['id']) }}" class="btn btn-primary">
                    <i class="fas fa-reply me-1"></i> Responder
                </a>
                <a href="{{ route('dashboard.mensajes.reenviar', $mensaje['id']) }}" class="btn btn-outline-primary">
                    <i class="fas fa-share me-1"></i> Reenviar
                </a>
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                    <i class="fas fa-trash me-1"></i> Eliminar
                </button>
            </div>
        </div>
    </div>

    <!-- Alertas -->
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

    <!-- Tarjeta del mensaje -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                {{ $mensaje['asunto'] }}
            </h6>
            <div>
                <button type="button" class="btn btn-sm btn-outline-warning toggle-favorito" data-id="{{ $mensaje['id'] }}">
                    <i class="fas fa-star {{ $mensaje['favorito'] ? 'text-warning' : '' }}"></i>
                    <span class="ms-1 d-none d-md-inline">{{ $mensaje['favorito'] ? 'Quitar de favoritos' : 'Añadir a favoritos' }}</span>
                </button>
            </div>
        </div>
        <div class="card-body">
            <!-- Información del remitente -->
            <div class="d-flex align-items-center mb-4">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px; font-weight: bold;">
                    {{ strtoupper(substr($mensaje['remitente']['nombre'], 0, 1)) }}
                </div>
                <div>
                    <h5 class="mb-0">{{ $mensaje['remitente']['nombre'] }}</h5>
                    <div class="text-muted small">
                        {{ $mensaje['remitente']['email'] ?? '' }}
                    </div>
                    <div class="text-muted small">
                        {{ \Carbon\Carbon::parse($mensaje['fecha'])->format('d/m/Y H:i') }}
                    </div>
                </div>
            </div>

            <!-- Destinatarios -->
            <div class="mb-4">
                <div class="row mb-1">
                    <div class="col-md-1 text-muted">Para:</div>
                    <div class="col-md-11">
                        @foreach($mensaje['destinatarios'] as $destinatario)
                            {{ $destinatario['nombre'] }}{{ !$loop->last ? ', ' : '' }}
                        @endforeach
                    </div>
                </div>
                
                @if(!empty($mensaje['cc']))
                <div class="row mb-1">
                    <div class="col-md-1 text-muted">CC:</div>
                    <div class="col-md-11">
                        @foreach($mensaje['cc'] as $cc)
                            {{ $cc['nombre'] }}{{ !$loop->last ? ', ' : '' }}
                        @endforeach
                    </div>
                </div>
                @endif
                
                @if(!empty($mensaje['bcc']))
                <div class="row mb-1">
                    <div class="col-md-1 text-muted">CCO:</div>
                    <div class="col-md-11">
                        @foreach($mensaje['bcc'] as $bcc)
                            {{ $bcc['nombre'] }}{{ !$loop->last ? ', ' : '' }}
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- Separador -->
            <hr class="my-4">

            <!-- Contenido del mensaje -->
            <div class="mensaje-contenido mb-4">
                {!! $mensaje['contenido'] !!}
            </div>

            <!-- Archivos adjuntos -->
            @if(!empty($mensaje['adjuntos']))
                <div class="card mb-0 mt-4">
                    <div class="card-header bg-light py-2">
                        <h6 class="m-0 font-weight-bold">
                            <i class="fas fa-paperclip me-1"></i> Archivos adjuntos ({{ count($mensaje['adjuntos']) }})
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @foreach($mensaje['adjuntos'] as $adjunto)
                                <div class="col-lg-4 col-md-6 mb-3">
                                    <div class="card h-100">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-center">
                                                <div class="me-3">
                                                    @php
                                                        $extension = pathinfo($adjunto['nombre'], PATHINFO_EXTENSION);
                                                        $iconClass = 'fas fa-file text-secondary';
                                                        
                                                        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp'])) {
                                                            $iconClass = 'fas fa-file-image text-info';
                                                        } elseif (in_array($extension, ['doc', 'docx'])) {
                                                            $iconClass = 'fas fa-file-word text-primary';
                                                        } elseif (in_array($extension, ['xls', 'xlsx'])) {
                                                            $iconClass = 'fas fa-file-excel text-success';
                                                        } elseif (in_array($extension, ['ppt', 'pptx'])) {
                                                            $iconClass = 'fas fa-file-powerpoint text-danger';
                                                        } elseif ($extension == 'pdf') {
                                                            $iconClass = 'fas fa-file-pdf text-danger';
                                                        } elseif (in_array($extension, ['zip', 'rar', 'tar', 'gz'])) {
                                                            $iconClass = 'fas fa-file-archive text-warning';
                                                        } elseif (in_array($extension, ['txt', 'log'])) {
                                                            $iconClass = 'fas fa-file-alt text-secondary';
                                                        }
                                                    @endphp
                                                    <i class="{{ $iconClass }} fa-2x"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 text-truncate" style="max-width: 180px;">{{ $adjunto['nombre'] }}</h6>
                                                    <small class="text-muted">{{ $adjunto['tamanio'] }}</small>
                                                </div>
                                            </div>
                                            <div class="mt-2 text-end">
                                                <a href="#" class="btn btn-sm btn-outline-primary" title="Descargar archivo">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                    data-bs-toggle="modal" data-bs-target="#previewModal{{ $loop->index }}" title="Vista previa">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Modal de vista previa para el archivo -->
                                <div class="modal fade" id="previewModal{{ $loop->index }}" tabindex="-1" aria-labelledby="previewModalLabel{{ $loop->index }}" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="previewModalLabel{{ $loop->index }}">{{ $adjunto['nombre'] }}</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                @if(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp']))
                                                    <div class="text-center">
                                                        <img src="{{ asset('img/placeholder-image.jpg') }}" alt="{{ $adjunto['nombre'] }}" class="img-fluid">
                                                    </div>
                                                @elseif($extension == 'pdf')
                                                    <div class="ratio ratio-16x9">
                                                        <iframe src="{{ asset('img/placeholder-pdf.html') }}" allowfullscreen></iframe>
                                                    </div>
                                                @else
                                                    <div class="text-center py-5">
                                                        <i class="{{ $iconClass }} fa-5x mb-4"></i>
                                                        <h5>No hay vista previa disponible</h5>
                                                        <p class="text-muted">Este tipo de archivo no puede ser mostrado en el navegador.</p>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                                <a href="#" class="btn btn-primary">
                                                    <i class="fas fa-download me-1"></i> Descargar
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
        </div>
        <div class="card-footer bg-white">
            <div class="d-flex justify-content-between">
                <a href="{{ route('dashboard.mensajes') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Volver
                </a>
                <div>
                    <a href="{{ route('dashboard.mensajes.responder', $mensaje['id']) }}" class="btn btn-primary">
                        <i class="fas fa-reply me-1"></i> Responder
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación de eliminación -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmar eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de que desea eliminar este mensaje? Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form action="{{ route('dashboard.mensajes.eliminar', $mensaje['id']) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Toggle favorito
        $('.toggle-favorito').click(function(e) {
            e.preventDefault();
            const mensajeId = $(this).data('id');
            
            $.ajax({
                url: '/mensajes/' + mensajeId + '/toggle-favorito',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    const isFavorito = response.favorito;
                    const $button = $('.toggle-favorito');
                    
                    // Actualizar icono y texto
                    $button.find('i').toggleClass('text-warning', isFavorito);
                    $button.find('span').text(isFavorito ? 'Quitar de favoritos' : 'Añadir a favoritos');
                    
                    // Mostrar notificación
                    const message = isFavorito ? 'Mensaje añadido a favoritos' : 'Mensaje eliminado de favoritos';
                    const html = `
                        <div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
                            <div class="toast align-items-center text-white bg-${isFavorito ? 'success' : 'secondary'} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                                <div class="d-flex">
                                    <div class="toast-body">
                                        <i class="fas fa-${isFavorito ? 'check-circle' : 'info-circle'} me-2"></i> ${message}
                                    </div>
                                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    $('body').append(html);
                    $('.toast').toast('show');
                },
                error: function() {
                    alert('Ha ocurrido un error. Por favor, inténtelo de nuevo.');
                }
            });
        });
        
        // Marcar mensaje como leído
        $.ajax({
            url: '/mensajes/{{ $mensaje["id"] }}/marcar-leido',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            }
        });
    });
</script>
@endsection

@section('styles')
<style>
    /* Estilo para el contenido del mensaje */
    .mensaje-contenido {
        font-size: 1rem;
        line-height: 1.6;
        min-height: 200px;
    }
    
    /* Estilos para citas en el mensaje */
    .mensaje-contenido blockquote {
        border-left: 3px solid #e3e6f0;
        padding-left: 1rem;
        color: #6c757d;
    }
    
    /* Animación de botones */
    .btn-outline-warning:hover {
        background-color: rgba(255, 193, 7, 0.1);
        color: #ff9800;
    }
    
    /* Estilos para impresión */
    @media print {
        .btn, .card-footer, .modal, .alert {
            display: none !important;
        }
    }
</style>
@endsection 