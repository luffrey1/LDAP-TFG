@extends('layouts.dashboard')

@section('title', 'Redactar mensaje')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-pen mr-1"></i> Redactar nuevo mensaje
                    </h6>
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnProfesores">
                            <i class="fas fa-chalkboard-teacher"></i> Todos los profesores
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnAlumnos">
                            <i class="fas fa-user-graduate"></i> Todos los alumnos
                        </button>
                    </div>
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
                        
                        <!-- Campo de búsqueda de destinatarios -->
                        <div class="form-group">
                            <label for="destinatario">Para:</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="buscarDestinatario" placeholder="Buscar destinatario...">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" id="btnLimpiarBusqueda">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div id="resultadosBusqueda" class="list-group mt-2" style="display: none; position: absolute; z-index: 1000; width: 100%; max-height: 200px; overflow-y: auto; background: white; border: 1px solid #ddd; border-radius: 4px;"></div>
                            <input type="hidden" name="destinatario" id="destinatario" value="{{ $destinatario ?? '' }}">
                            <div id="destinatarioSeleccionado" class="mt-2"></div>
                        </div>

                        <!-- CC -->
                        <div class="form-group">
                            <label for="cc" class="d-flex justify-content-between">
                                <span>CC:</span>
                                <a href="#" class="text-muted small toggle-cc">Mostrar CC</a>
                            </label>
                            <div class="cc-field d-none">
                                <select class="form-control" id="cc" name="cc[]" multiple>
                                    @if(isset($usuariosAgrupados))
                                        <!-- Profesores -->
                                        @if(isset($usuariosAgrupados['profesores']) && count($usuariosAgrupados['profesores']) > 0)
                                            <optgroup label="Profesores">
                                                @foreach($usuariosAgrupados['profesores'] as $profesor)
                                                    <option value="{{ $profesor['id'] }}">{{ $profesor['name'] }} ({{ $profesor['email'] }})</option>
                                                @endforeach
                                            </optgroup>
                                        @endif
                                        
                                        <!-- Alumnos -->
                                        @if(isset($usuariosAgrupados['alumnos']) && count($usuariosAgrupados['alumnos']) > 0)
                                            <optgroup label="Alumnos">
                                                @foreach($usuariosAgrupados['alumnos'] as $alumno)
                                                    <option value="{{ $alumno['id'] }}">{{ $alumno['name'] }} ({{ $alumno['email'] }})</option>
                                                @endforeach
                                            </optgroup>
                                        @endif
                                        
                                        <!-- Administradores -->
                                        @if(isset($usuariosAgrupados['admins']) && count($usuariosAgrupados['admins']) > 0)
                                            <optgroup label="Administradores">
                                                @foreach($usuariosAgrupados['admins'] as $admin)
                                                    <option value="{{ $admin['id'] }}">{{ $admin['name'] }} ({{ $admin['email'] }})</option>
                                                @endforeach
                                            </optgroup>
                                        @endif
                                    @else
                                        @foreach($usuarios as $usuario)
                                            <option value="{{ $usuario['id'] }}">{{ $usuario['name'] }} ({{ $usuario['email'] }})</option>
                                        @endforeach
                                    @endif
                                </select>
                                <small class="text-muted">Enviar copia a estos destinatarios (mantenga CTRL presionado para seleccionar varios)</small>
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
                            <textarea class="form-control" id="contenido" name="contenido" rows="15">{{ $contenido ?? old('contenido') }}</textarea>
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
    </div>
</div>

@push('styles')
<style>
    .list-group-item {
        cursor: pointer;
        padding: 8px 15px;
        border: none;
        border-bottom: 1px solid #eee;
    }
    .list-group-item:last-child {
        border-bottom: none;
    }
    .list-group-item:hover {
        background-color: #f8f9fc;
    }
    .destinatario-tag {
        display: inline-block;
        padding: 0.25rem 0.5rem;
        margin: 0.25rem;
        background-color: #4e73df;
        color: white;
        border-radius: 0.25rem;
    }
    .destinatario-tag .remove {
        margin-left: 0.5rem;
        cursor: pointer;
    }
    #resultadosBusqueda {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    #resultadosBusqueda .list-group-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    #resultadosBusqueda .list-group-item .user-info {
        flex-grow: 1;
    }
    #resultadosBusqueda .list-group-item .user-email {
        font-size: 0.8em;
        color: #666;
    }
    /* Estilos existentes */
    .card-body, .form-control, .custom-file-label, .custom-file-input, #contenido {
        background-color: #fff !important;
        color: #222 !important;
    }
    .form-group label, .custom-file-label {
        color: #222 !important;
        font-weight: 600;
    }
    .form-control:focus, .custom-file-input:focus {
        border-color: #4e73df;
        box-shadow: 0 0 0 0.2rem rgba(78,115,223,.25);
    }
    .custom-file-label {
        background: #f8f9fc !important;
        border: 1px solid #4e73df;
        color: #222 !important;
    }
    .custom-file-input:focus ~ .custom-file-label {
        border-color: #4e73df;
        box-shadow: 0 0 0 0.2rem rgba(78,115,223,.25);
    }
    .adjunto-item {
        background-color: #f1f3fa !important;
        color: #222 !important;
    }
    select[multiple] {
        background: #fff !important;
        color: #222 !important;
        border: 1px solid #4e73df;
    }
    optgroup {
        color: #2e59d9 !important;
    }
    optgroup option {
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
    #error-adjuntos {
        color: #e74a3b;
        font-weight: bold;
        margin-top: 5px;
        display: none;
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    let timeoutId;
    const resultadosBusqueda = $('#resultadosBusqueda');
    const destinatarioInput = $('#destinatario');
    const destinatarioSeleccionado = $('#destinatarioSeleccionado');
    
    // Función para buscar destinatarios
    function buscarDestinatarios() {
        const query = document.getElementById('destinatario').value.trim();
        console.log('Input detectado:', query);
        
        if (query.length < 2) {
            document.getElementById('resultadosBusqueda').style.display = 'none';
            return;
        }
        
        console.log('Buscando destinatarios con query:', query);
        
        fetch(`{{ route('dashboard.mensajes.buscar-destinatarios') }}?query=${encodeURIComponent(query)}`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        })
        .then(response => {
            console.log('Status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Respuesta recibida:', data);
            const resultadosDiv = document.getElementById('resultadosBusqueda');
            
            if (!Array.isArray(data)) {
                console.error('Respuesta no es un array:', data);
                resultadosDiv.innerHTML = '<div class="p-2 text-red-500">Error en el formato de respuesta</div>';
                resultadosDiv.style.display = 'block';
                return;
            }
            
            if (data.length === 0) {
                resultadosDiv.innerHTML = '<div class="p-2 text-gray-500">No se encontraron resultados</div>';
                resultadosDiv.style.display = 'block';
                return;
            }
            
            let html = '';
            data.forEach(usuario => {
                html += `
                    <div class="p-2 hover:bg-gray-100 cursor-pointer" 
                         onclick="seleccionarDestinatario('${usuario.name}', '${usuario.email}')">
                        <div class="font-medium">${usuario.name}</div>
                        <div class="text-sm text-gray-500">${usuario.email}</div>
                    </div>
                `;
            });
            
            resultadosDiv.innerHTML = html;
            resultadosDiv.style.display = 'block';
        })
        .catch(error => {
            console.error('Error en la búsqueda:', error);
            const resultadosDiv = document.getElementById('resultadosBusqueda');
            resultadosDiv.innerHTML = '<div class="p-2 text-red-500">Error al buscar destinatarios</div>';
            resultadosDiv.style.display = 'block';
        });
    }
    
    // Evento de búsqueda con debounce
    $('#buscarDestinatario').on('input', function() {
        console.log('Input detectado:', $(this).val());
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => {
            buscarDestinatarios();
        }, 300);
    });
    
    // Seleccionar destinatario
    resultadosBusqueda.on('click', '.list-group-item', function(e) {
        e.preventDefault();
        const id = $(this).data('id');
        const nombre = $(this).data('nombre');
        
        destinatarioInput.val(id);
        destinatarioSeleccionado.html(`
            <div class="destinatario-tag">
                ${nombre}
                <span class="remove" onclick="removerDestinatario()">
                    <i class="fas fa-times"></i>
                </span>
            </div>
        `);
        
        $('#buscarDestinatario').val('');
        resultadosBusqueda.hide();
    });
    
    // Botón para limpiar búsqueda
    $('#btnLimpiarBusqueda').click(function() {
        $('#buscarDestinatario').val('');
        resultadosBusqueda.hide();
    });
    
    // Botón para seleccionar todos los profesores
    $('#btnProfesores').click(function() {
        destinatarioInput.val('grupo_profesores');
        destinatarioSeleccionado.html(`
            <div class="destinatario-tag">
                Todos los profesores
                <span class="remove" onclick="removerDestinatario()">
                    <i class="fas fa-times"></i>
                </span>
            </div>
        `);
    });
    
    // Botón para seleccionar todos los alumnos
    $('#btnAlumnos').click(function() {
        destinatarioInput.val('grupo_alumnos');
        destinatarioSeleccionado.html(`
            <div class="destinatario-tag">
                Todos los alumnos
                <span class="remove" onclick="removerDestinatario()">
                    <i class="fas fa-times"></i>
                </span>
            </div>
        `);
    });
    
    // Cerrar resultados al hacer clic fuera
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#buscarDestinatario, #resultadosBusqueda').length) {
            resultadosBusqueda.hide();
        }
    });

    // Mostrar/ocultar campos CC y BCC
    $('.toggle-cc').click(function(e) {
        e.preventDefault();
        $('.cc-field').toggleClass('d-none');
        $(this).text(function(i, text) {
            return text === "Mostrar CC" ? "Ocultar CC" : "Mostrar CC";
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
                        // Limpiar el input de archivos
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
    
    // Animación para el mensaje original
    $('[data-toggle="collapse"]').click(function() {
        $(this).find('i.fas.fa-chevron-down').toggleClass('fa-chevron-up');
    });
    
    // Guardar borrador
    $('#btn-guardar-borrador').click(function() {
        $('<input>').attr({
            type: 'hidden',
            name: 'guardar_borrador',
            value: '1'
        }).appendTo('#form-mensaje');
        
        $('#form-mensaje').submit();
    });
    
    // Verificar destinatarios antes de enviar
    $('#form-mensaje').on('submit', function(e) {
        const destinatario = $('#destinatario').val();
        
        if (!destinatario || destinatario === '') {
            e.preventDefault();
            alert('Debe seleccionar un destinatario.');
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
    
    // Validación de adjuntos
    const allowedTypes = [
        'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'image/jpeg', 'image/png', 'image/gif', 'application/zip', 'application/x-rar-compressed'
    ];
    const maxFileSize = 10 * 1024 * 1024; // 10MB
    $('#adjuntos').on('change', function() {
        const files = Array.from(this.files);
        let errorMsg = '';
        files.forEach(function(file) {
            if (file.size > maxFileSize) {
                errorMsg = 'El archivo ' + file.name + ' supera el tamaño máximo de 10MB.';
            } else if (!allowedTypes.includes(file.type) && !file.type.startsWith('image/')) {
                errorMsg = 'El archivo ' + file.name + ' no es de un tipo permitido.';
            }
        });
        if (errorMsg) {
            $('#error-adjuntos').text(errorMsg).show();
            $('#adjuntos').val('');
            $('#lista-adjuntos').empty();
            $('.custom-file-label').text('Seleccionar archivos');
        } else {
            $('#error-adjuntos').hide();
        }
    });
});

// Función para remover destinatario
function removerDestinatario() {
    $('#destinatario').val('');
    $('#destinatarioSeleccionado').empty();
}
</script>
@endpush
@endsection 