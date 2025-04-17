@extends('layouts.dashboard')

@section('title', 'Redactar mensaje')

@section('content')
<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-pen mr-1"></i> Redactar nuevo mensaje
            </h6>
        </div>
        <div class="card-body">
            <!-- Alertas -->
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
            
            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif
            
            <!-- Formulario de mensaje -->
            <form action="{{ route('dashboard.mensajes.enviar') }}" method="POST" enctype="multipart/form-data" id="form-mensaje">
                @csrf
                
                <!-- Campo oculto para identificar si es respuesta o reenvío -->
                @if(isset($respuesta_a))
                    <input type="hidden" name="respuesta_a" value="{{ $respuesta_a }}">
                @endif
                
                @if(isset($reenviar))
                    <input type="hidden" name="reenviar" value="{{ $reenviar }}">
                @endif
                
                <!-- Destinatarios -->
                <div class="form-group">
                    <label for="destinatarios">Para:</label>
                    <select class="selectpicker form-control" id="destinatarios" name="destinatarios[]" data-live-search="true" multiple data-actions-box="true" required>
                        @foreach($usuarios as $usuario)
                            <option value="{{ $usuario['id'] }}" 
                                @if(isset($destinatarios_seleccionados) && in_array($usuario['id'], $destinatarios_seleccionados)) selected @endif
                                data-subtext="{{ $usuario['email'] }}">
                                {{ $usuario['nombre'] }}
                                @if(isset($usuario['departamento'])) ({{ $usuario['departamento'] }}) @endif
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Busque y seleccione uno o más destinatarios</small>
                </div>
                
                <!-- CC -->
                <div class="form-group">
                    <label for="cc" class="d-flex justify-content-between">
                        <span>CC:</span>
                        <a href="#" class="text-muted small toggle-cc">Mostrar CC</a>
                    </label>
                    <div class="cc-field d-none">
                        <select class="selectpicker form-control" id="cc" name="cc[]" data-live-search="true" multiple data-actions-box="true">
                            @foreach($usuarios as $usuario)
                                <option value="{{ $usuario['id'] }}" 
                                    @if(isset($cc_seleccionados) && in_array($usuario['id'], $cc_seleccionados)) selected @endif
                                    data-subtext="{{ $usuario['email'] }}">
                                    {{ $usuario['nombre'] }}
                                    @if(isset($usuario['departamento'])) ({{ $usuario['departamento'] }}) @endif
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Enviar copia a estos destinatarios</small>
                    </div>
                </div>
                
                <!-- BCC -->
                <div class="form-group">
                    <label for="bcc" class="d-flex justify-content-between">
                        <span>BCC:</span>
                        <a href="#" class="text-muted small toggle-bcc">Mostrar BCC</a>
                    </label>
                    <div class="bcc-field d-none">
                        <select class="selectpicker form-control" id="bcc" name="bcc[]" data-live-search="true" multiple data-actions-box="true">
                            @foreach($usuarios as $usuario)
                                <option value="{{ $usuario['id'] }}" 
                                    @if(isset($bcc_seleccionados) && in_array($usuario['id'], $bcc_seleccionados)) selected @endif
                                    data-subtext="{{ $usuario['email'] }}">
                                    {{ $usuario['nombre'] }}
                                    @if(isset($usuario['departamento'])) ({{ $usuario['departamento'] }}) @endif
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Enviar copia oculta a estos destinatarios</small>
                    </div>
                </div>
                
                <!-- Asunto -->
                <div class="form-group">
                    <label for="asunto">Asunto:</label>
                    <input type="text" class="form-control" id="asunto" name="asunto" value="{{ $asunto ?? old('asunto') }}" required>
                </div>
                
                <!-- Contenido del mensaje -->
                <div class="form-group">
                    <label for="contenido">Mensaje:</label>
                    <textarea class="form-control editor" id="contenido" name="contenido" rows="15">{{ $contenido ?? old('contenido') }}</textarea>
                </div>
                
                <!-- Cita de mensaje anterior (si es respuesta) -->
                @if(isset($mensaje_original))
                <div class="form-group">
                    <div class="card">
                        <div class="card-header bg-light py-2">
                            <a data-toggle="collapse" href="#collapseCita" role="button" aria-expanded="false" aria-controls="collapseCita" class="text-decoration-none text-dark">
                                <h6 class="mb-0 d-flex justify-content-between align-items-center">
                                    <span><i class="fas fa-quote-left mr-1"></i> Mensaje original</span>
                                    <i class="fas fa-chevron-down"></i>
                                </h6>
                            </a>
                        </div>
                        <div class="collapse" id="collapseCita">
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <div>
                                        <strong>De:</strong> {{ $mensaje_original['remitente']['nombre'] }}
                                    </div>
                                    <div>
                                        <small class="text-muted">{{ \Carbon\Carbon::parse($mensaje_original['fecha'])->format('d/m/Y H:i') }}</small>
                                    </div>
                                </div>
                                <div class="d-flex mb-2">
                                    <div>
                                        <strong>Para:</strong>
                                        @foreach($mensaje_original['destinatarios'] as $index => $destinatario)
                                            {{ $destinatario['nombre'] }}{{ $index < count($mensaje_original['destinatarios']) - 1 ? ', ' : '' }}
                                        @endforeach
                                    </div>
                                </div>
                                <div class="d-flex mb-3">
                                    <div>
                                        <strong>Asunto:</strong> {{ $mensaje_original['asunto'] }}
                                    </div>
                                </div>
                                <div class="pl-3 border-left">
                                    {!! $mensaje_original['contenido'] !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
                
                <!-- Archivos adjuntos -->
                <div class="form-group">
                    <label for="adjuntos" class="d-block">Adjuntos:</label>
                    <div class="custom-file mb-2">
                        <input type="file" class="custom-file-input" id="adjuntos" name="adjuntos[]" multiple>
                        <label class="custom-file-label" for="adjuntos">Seleccionar archivos</label>
                    </div>
                    <small class="text-muted">Tamaño máximo por archivo: 10MB. Formatos permitidos: PDF, Word, Excel, PowerPoint, imágenes, ZIP.</small>
                    
                    <div id="lista-adjuntos" class="mt-3">
                        <!-- Aquí se mostrarán los archivos seleccionados -->
                    </div>
                    
                    <!-- Adjuntos originales (si es reenvío) -->
                    @if(isset($adjuntos_originales) && count($adjuntos_originales) > 0)
                    <div class="card mt-3">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0">
                                <i class="fas fa-paperclip mr-1"></i> 
                                Adjuntos originales
                            </h6>
                        </div>
                        <div class="card-body py-2">
                            <div class="row">
                                @foreach($adjuntos_originales as $index => $adjunto)
                                <div class="col-md-4 mb-2">
                                    <div class="card">
                                        <div class="card-body p-2">
                                            <div class="d-flex align-items-center">
                                                <div class="mr-2">
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
                                                    <small class="text-truncate d-block" style="max-width: 150px;" title="{{ $adjunto['nombre'] }}">{{ $adjunto['nombre'] }}</small>
                                                    <small class="text-muted">{{ $adjunto['tamano'] }}</small>
                                                </div>
                                            </div>
                                            <div class="custom-control custom-checkbox mt-2">
                                                <input type="checkbox" class="custom-control-input" id="incluir_adjunto_{{ $index }}" name="incluir_adjuntos[]" value="{{ $adjunto['id'] }}" checked>
                                                <label class="custom-control-label" for="incluir_adjunto_{{ $index }}">Incluir</label>
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
                
                <!-- Botones de acción -->
                <div class="form-group mb-0 text-right">
                    <button type="button" class="btn btn-light mr-2" id="btn-guardar-borrador">
                        <i class="fas fa-save mr-1"></i> Guardar borrador
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane mr-1"></i> Enviar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('styles')
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">
<style>
    /* Estilo para la lista de adjuntos */
    .adjunto-item {
        display: flex;
        align-items: center;
        padding: 8px;
        background-color: #f8f9fc;
        border-radius: 4px;
        margin-bottom: 8px;
    }
    
    .adjunto-item .adjunto-icon {
        margin-right: 10px;
    }
    
    .adjunto-item .adjunto-nombre {
        flex-grow: 1;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .adjunto-item .adjunto-tamano {
        margin: 0 10px;
        color: #6c757d;
    }
    
    .adjunto-item .adjunto-remove {
        color: #e74a3b;
        cursor: pointer;
    }
    
    /* Estilo para el editor de texto */
    .note-editor {
        border-color: #d1d3e2 !important;
    }
    
    .note-statusbar {
        background-color: #f8f9fc !important;
    }
    
    /* Estilo para campos de destinatarios */
    .bootstrap-select .dropdown-toggle {
        border-color: #d1d3e2 !important;
    }
    
    .bootstrap-select .dropdown-toggle:focus {
        border-color: #bac8f3 !important;
        box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25) !important;
    }
</style>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>
<script>
    $(document).ready(function() {
        // Inicializar el editor de texto
        $('.editor').summernote({
            placeholder: 'Escriba su mensaje aquí...',
            height: 250,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ],
            callbacks: {
                onImageUpload: function(files) {
                    alert('Por favor, adjunte las imágenes como archivos en lugar de insertarlas directamente.');
                }
            }
        });
        
        // Inicializar selectpicker para los destinatarios
        $('.selectpicker').selectpicker();
        
        // Mostrar/ocultar campos CC y BCC
        $('.toggle-cc').click(function(e) {
            e.preventDefault();
            $('.cc-field').toggleClass('d-none');
            $(this).text(function(i, text) {
                return text === "Mostrar CC" ? "Ocultar CC" : "Mostrar CC";
            });
        });
        
        $('.toggle-bcc').click(function(e) {
            e.preventDefault();
            $('.bcc-field').toggleClass('d-none');
            $(this).text(function(i, text) {
                return text === "Mostrar BCC" ? "Ocultar BCC" : "Mostrar BCC";
            });
        });
        
        // Mostrar nombre de archivos seleccionados
        $('#adjuntos').on('change', function() {
            const files = Array.from(this.files);
            const filesCount = files.length;
            const $fileLabel = $(this).next('.custom-file-label');
            
            if (filesCount > 0) {
                $fileLabel.text(filesCount + ' archivo' + (filesCount > 1 ? 's' : '') + ' seleccionado' + (filesCount > 1 ? 's' : ''));
                
                // Limpiar y actualizar la lista de adjuntos
                $('#lista-adjuntos').empty();
                
                files.forEach(function(file, index) {
                    // Determinar el icono basado en el tipo de archivo
                    let fileIcon = '<i class="fas fa-file fa-lg text-info"></i>';
                    const extension = file.name.split('.').pop().toLowerCase();
                    
                    if (['jpg', 'jpeg', 'png', 'gif'].includes(extension)) {
                        fileIcon = '<i class="fas fa-file-image fa-lg text-primary"></i>';
                    } else if (extension === 'pdf') {
                        fileIcon = '<i class="fas fa-file-pdf fa-lg text-danger"></i>';
                    } else if (['doc', 'docx'].includes(extension)) {
                        fileIcon = '<i class="fas fa-file-word fa-lg text-primary"></i>';
                    } else if (['xls', 'xlsx'].includes(extension)) {
                        fileIcon = '<i class="fas fa-file-excel fa-lg text-success"></i>';
                    } else if (['ppt', 'pptx'].includes(extension)) {
                        fileIcon = '<i class="fas fa-file-powerpoint fa-lg text-warning"></i>';
                    } else if (['zip', 'rar'].includes(extension)) {
                        fileIcon = '<i class="fas fa-file-archive fa-lg text-secondary"></i>';
                    }
                    
                    // Formatear el tamaño del archivo
                    const fileSize = formatFileSize(file.size);
                    
                    // Crear elemento de la lista de adjuntos
                    const $adjuntoItem = $(`
                        <div class="adjunto-item" data-index="${index}">
                            <div class="adjunto-icon">${fileIcon}</div>
                            <div class="adjunto-nombre">${file.name}</div>
                            <div class="adjunto-tamano">${fileSize}</div>
                            <div class="adjunto-remove"><i class="fas fa-times"></i></div>
                        </div>
                    `);
                    
                    // Eliminar archivo al hacer clic en el botón de eliminar
                    $adjuntoItem.find('.adjunto-remove').on('click', function() {
                        $adjuntoItem.remove();
                        
                        // Actualizar contador de archivos
                        const remainingFiles = $('#lista-adjuntos .adjunto-item').length;
                        if (remainingFiles === 0) {
                            $fileLabel.text('Seleccionar archivos');
                            // Limpiar el input de archivos (complejo debido a seguridad de navegadores)
                            $('#adjuntos').val('');
                        } else {
                            $fileLabel.text(remainingFiles + ' archivo' + (remainingFiles > 1 ? 's' : '') + ' seleccionado' + (remainingFiles > 1 ? 's' : ''));
                        }
                    });
                    
                    $('#lista-adjuntos').append($adjuntoItem);
                });
            } else {
                $fileLabel.text('Seleccionar archivos');
                $('#lista-adjuntos').empty();
            }
        });
        
        // Función para formatear el tamaño del archivo
        function formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            else if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
            else return (bytes / 1048576).toFixed(1) + ' MB';
        }
        
        // Animación para el mensaje original (si existe)
        $('[data-toggle="collapse"]').click(function() {
            $(this).find('i.fas.fa-chevron-down').toggleClass('fa-chevron-up');
        });
        
        // Guardar borrador
        $('#btn-guardar-borrador').click(function() {
            // Agregar campo para indicar que es un borrador
            $('<input>').attr({
                type: 'hidden',
                name: 'guardar_borrador',
                value: '1'
            }).appendTo('#form-mensaje');
            
            // Enviar formulario
            $('#form-mensaje').submit();
        });
        
        // Verificar destinatarios antes de enviar
        $('#form-mensaje').on('submit', function(e) {
            const destinatarios = $('#destinatarios').val();
            
            if (!destinatarios || destinatarios.length === 0) {
                e.preventDefault();
                alert('Debe seleccionar al menos un destinatario.');
                return false;
            }
            
            const asunto = $('#asunto').val();
            if (!asunto || asunto.trim() === '') {
                if (!confirm('¿Desea enviar el mensaje sin asunto?')) {
                    e.preventDefault();
                    return false;
                }
            }
            
            return true;
        });
    });
</script>
@endsection 