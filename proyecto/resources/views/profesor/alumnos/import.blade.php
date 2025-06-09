@extends('layouts.dashboard')

@section('title', 'Importar Usuarios')

@section('content')
<section class="section">
    <div class="section-header">
        <h1><i class="fas fa-file-import"></i> Importar Usuarios</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard.index') }}">Dashboard</a></div>
            <div class="breadcrumb-item"><a href="{{ route('profesor.clases.index') }}">Clases</a></div>
            @if(isset($grupo))
            <div class="breadcrumb-item"><a href="{{ route('profesor.clases.show', $grupo->id) }}">{{ $grupo->nombre }}</a></div>
            @endif
            <div class="breadcrumb-item">Importar Usuarios</div>
        </div>
    </div>

    <div class="section-body">
        <h2 class="section-title">Importar Usuarios desde Archivo</h2>
        <p class="section-lead">Importe múltiples usuarios utilizando un archivo CSV o Excel.</p>

        @include('partials.session_messages')

        <div class="row">
            <div class="col-12 col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-info-circle me-2"></i>Instrucciones</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info border-0 shadow-sm">
                            <div class="alert-title fw-bold mb-2">Formato del archivo</div>
                            <p class="mb-3">El archivo debe contener las siguientes columnas:</p>
                            <ul class="list-group list-group-flush mb-3">
                                <li class="list-group-item bg-transparent border-0 ps-0">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <strong>nombre</strong> - Nombre del usuario
                                </li>
                                <li class="list-group-item bg-transparent border-0 ps-0">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <strong>apellidos</strong> - Apellidos del usuario
                                </li>
                                <li class="list-group-item bg-transparent border-0 ps-0">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <strong>identificacion</strong> - DNI o identificación del usuario
                                </li>
                                <li class="list-group-item bg-transparent border-0 ps-0">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    <strong>email</strong> - Correo electrónico (opcional)
                                </li>
                            </ul>
                        </div>

                        <div class="text-center">
                            <p class="mb-3 text-white">Puede descargar una plantilla de ejemplo para comenzar:</p>
                            <a href="{{ route('profesor.alumnos.template') }}" class="btn btn-info btn-lg shadow-sm">
                                <i class="fas fa-download me-2"></i> Descargar Plantilla
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="card shadow-sm" id="import-card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-upload me-2"></i>Subir Archivo</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('profesor.alumnos.import.process') }}" method="POST" enctype="multipart/form-data" class="dropzone" id="importForm">
                            @csrf
                            <input type="hidden" name="_method" value="POST">
                            
                            <div class="form-group mb-4">
                                <label for="tipo_importacion" class="form-label fw-bold text-black fs-5">Tipo de importación</label>
                                <select name="tipo_importacion" id="tipo_importacion" class="form-select shadow-sm bg-white border-2">
                                    <option value="alumno" selected>Alumnos</option>
                                    <option value="profesor">Profesores</option>
                                </select>
                            </div>
                            
                            @if(!isset($grupo))
                            <div class="form-group mb-4">
                                <label for="clase_grupo_id" class="form-label fw-bold text-black fs-5">Clase / Grupo</label>
                                <select name="clase_grupo_id" id="clase_grupo_id" class="form-select shadow-sm bg-white border-2" required>
                                    <option value="" class="text-dark fw-medium">Selecciona una clase o grupo</option>
                                    @foreach($grupos as $grupo_select)
                                        <option value="{{ $grupo_select->id }}" {{ isset($grupo) && $grupo->id == $grupo_select->id ? 'selected' : '' }}>
                                            {{ $grupo_select->nombre }} ({{ $grupo_select->curso }}º {{ $grupo_select->seccion }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @else
                            <input type="hidden" name="clase_grupo_id" value="{{ $grupo->id }}">
                            @endif
                            
                            <div class="form-group mb-4">
                                <label class="form-label fw-bold text-black fs-5">Archivo de Usuarios</label>
                                <div class="dropzone-area p-5 text-center border-2 border-dashed rounded-3 bg-light" id="dropzone">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                                    <h5 class="mb-3">Arrastre y suelte su archivo aquí</h5>
                                    <p class="text-muted mb-3">o</p>
                                    <div class="custom-file">
                                        <input type="file" name="archivo_csv" class="form-control" id="archivo_csv" accept=".csv, .xlsx, .xls" required>
                                        <label class="btn btn-outline-primary" for="archivo_csv">
                                            <i class="fas fa-folder-open me-2"></i>Seleccionar archivo
                                        </label>
                                    </div>
                                    <div class="form-text text-muted mt-2">Formatos aceptados: CSV, Excel (.xlsx, .xls)</div>
                                </div>
                            </div>

                            <div class="form-group mb-4">
                                <label for="separador" class="form-label fw-bold text-black fs-5">Separador CSV</label>
                                <select name="separador" id="separador" class="form-select shadow-sm bg-white border-2" required>
                                    <option value=",">Coma (,)</option>
                                    <option value=";">Punto y coma (;)</option>
                                    <option value="\t">Tabulador</option>
                                </select>
                            </div>

                            <div class="form-group mb-4">
                                <input type="hidden" name="tiene_encabezados" value="0">
                                <div class="form-check">
                                    <input type="checkbox" name="tiene_encabezados" id="tiene_encabezados" class="form-check-input" value="1" checked>
                                    <label class="form-check-label text-black fw-bold" for="tiene_encabezados">El archivo contiene fila de encabezados</label>
                                </div>
                            </div>

                            <div class="form-group mb-4">
                                <input type="hidden" name="crear_cuentas_ldap" value="0">
                                <div class="form-check">
                                    <input type="checkbox" name="crear_cuentas_ldap" id="crear_cuentas_ldap" class="form-check-input" value="1" checked>
                                    <label class="form-check-label text-black fw-bold" for="crear_cuentas_ldap">Crear cuentas LDAP para los usuarios</label>
                                </div>
                            </div>

                            <!-- Previsualización mejorada -->
                            <div id="preview" class="mt-4" style="display: none;">
                                <div class="card shadow-sm">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0"><i class="fas fa-table me-2"></i>Previsualización</h5>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="table-responsive" style="max-height: 400px;">
                                            <table class="table table-bordered table-hover mb-0">
                                                <thead class="table-light sticky-top">
                                                    <tr id="preview-header">
                                                    </tr>
                                                </thead>
                                                <tbody id="preview-content">
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-3 text-center">
                                    <button type="button" id="download-csv" class="btn btn-success btn-lg shadow-sm">
                                        <i class="fas fa-download me-2"></i> Descargar CSV con Contraseñas
                                    </button>
                                </div>
                                
                                <div class="alert alert-warning mt-3 shadow-sm">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Importante:</strong> Las contraseñas solo las puedes ver ahora. Se almacenarán hasheadas en el sistema por seguridad.
                                </div>
                            </div>

                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-primary btn-lg btn-block w-100 shadow-sm">
                                    <i class="fas fa-upload me-2"></i> Importar Usuarios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        // Función para generar contraseña aleatoria
        function generatePassword() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
            let password = '';
            for (let i = 0; i < 12; i++) {
                password += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return password;
        }

        // Configuración del área de dropzone
        const dropzone = document.getElementById('dropzone');
        const fileInput = document.getElementById('archivo_csv');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            dropzone.classList.add('bg-primary', 'bg-opacity-10');
        }

        function unhighlight(e) {
            dropzone.classList.remove('bg-primary', 'bg-opacity-10');
        }

        dropzone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            fileInput.files = files;
            handleFiles(files);
        }

        // Mostrar nombre del archivo y previsualizar
        $('.custom-file-input').on('change', function() {
            handleFiles(this.files);
        });

        function handleFiles(files) {
            const file = files[0];
            if (!file) return;

            // Actualizar UI
            const fileName = file.name;
            $('.custom-file-label').addClass("selected").html(fileName);
            
            // Previsualizar CSV
            const reader = new FileReader();
            const separator = $('#separador').val();
            
            reader.onload = function(e) {
                const text = e.target.result;
                const lines = text.split('\n');
                let html = '';
                let csvData = [];
                
                // Mostrar todas las líneas
                lines.forEach((line, index) => {
                    if (line.trim() !== '') {  // Ignorar líneas vacías
                        const cells = line.split(separator);
                        if (index === 0) {  // Primera fila (títulos)
                            let headerHtml = '';
                            cells.forEach(cell => {
                                headerHtml += `<th class="text-nowrap">${cell.trim()}</th>`;
                            });
                            headerHtml += '<th class="text-nowrap">Contraseña</th>';
                            $('#preview-header').html(headerHtml);
                            csvData.push([...cells, 'Contraseña']);
                        } else {  // Resto de filas (datos)
                            html += '<tr>';
                            cells.forEach(cell => {
                                html += `<td class="text-nowrap">${cell.trim()}</td>`;
                            });
                            // Añadir columna de contraseña
                            const password = generatePassword();
                            html += `<td class="text-nowrap"><code class="bg-light px-2 py-1 rounded">${password}</code></td>`;
                            html += '</tr>';
                            csvData.push([...cells, password]);
                        }
                    }
                });
                
                $('#preview-content').html(html);
                $('#preview').slideDown();

                // Guardar los datos del CSV generado para la descarga
                window.csvData = csvData;
            };
            
            reader.readAsText(file);
        }

        // Actualizar previsualización cuando cambie el separador
        $('#separador').on('change', function() {
            const fileInput = document.getElementById('archivo_csv');
            if(fileInput.files.length > 0) {
                handleFiles(fileInput.files);
            }
        });

        // Botón para descargar el CSV generado
        $('#download-csv').on('click', function() {
            if (!window.csvData) return;
            const separator = $('#separador').val();
            let csvContent = window.csvData.map(row => row.join(separator)).join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.setAttribute('href', url);
            link.setAttribute('download', 'usuarios_con_contraseñas.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });

        // Manejar el envío del formulario
        $('#importForm').on('submit', function(e) {
            // Asegurarse de que los checkboxes tengan un valor
            if (!$('#tiene_encabezados').is(':checked')) {
                $('input[name="tiene_encabezados"]').val('0');
            }
            if (!$('#crear_cuentas_ldap').is(':checked')) {
                $('input[name="crear_cuentas_ldap"]').val('0');
            }
        });

        // Inicializar select2 con estilo mejorado
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
    });
</script>

@section('styles')
<style>
.dropzone-area {
    transition: all 0.3s ease;
    border: 2px dashed #0d6efd !important;
    background-color: #f8f9fa !important;
}

.dropzone-area:hover {
    border-color: #0a58ca !important;
    background-color: #e9ecef !important;
}

.table-responsive {
    scrollbar-width: thin;
    scrollbar-color: #dee2e6 #f8f9fa;
}

.table-responsive::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f8f9fa;
}

.table-responsive::-webkit-scrollbar-thumb {
    background-color: #dee2e6;
    border-radius: 4px;
}

.sticky-top {
    position: sticky;
    top: 0;
    z-index: 1;
}

.btn {
    transition: all 0.2s ease;
}

.btn:hover {
    transform: translateY(-1px);
}

.form-control:focus, .form-select:focus {
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
}

.alert {
    border-radius: 0.5rem;
}

.card {
    border-radius: 0.5rem;
    border: none;
}

.card-header {
    background-color: #0d6efd !important;
}

.card-header h4, .card-header h5 {
    color: #ffffff !important;
    font-weight: 600 !important;
    margin: 0 !important;
}

.table > :not(caption) > * > * {
    padding: 1rem;
}

code {
    font-size: 0.875em;
    color: #0d6efd;
}

.form-label {
    color: #000000 !important;
    font-size: 1.25rem !important;
    font-weight: 600 !important;
    margin-bottom: 0.75rem !important;
}

.form-select {
    color: #000000 !important;
    background-color: #ffffff !important;
    border: 2px solid #dee2e6 !important;
    font-weight: 500 !important;
    padding: 0.5rem 1rem !important;
}

.form-select option {
    color: #000000 !important;
    background-color: #ffffff !important;
    font-weight: 500 !important;
}

.form-select option:first-child {
    color: #495057 !important;
    font-weight: 500 !important;
    background-color: #f8f9fa !important;
}

.select2-container--bootstrap-5 .select2-selection {
    background-color: #ffffff !important;
    border: 2px solid #dee2e6 !important;
    color: #000000 !important;
    font-weight: 500 !important;
    padding: 0.5rem 1rem !important;
}

.select2-container--bootstrap-5 .select2-selection--single {
    height: 42px !important;
    padding: 0.5rem 1rem !important;
}

.select2-container--bootstrap-5 .select2-selection--single .select2-selection__rendered {
    color: #000000 !important;
    line-height: 28px !important;
    font-weight: 500 !important;
}

.select2-container--bootstrap-5 .select2-selection--single .select2-selection__placeholder {
    color: #495057 !important;
    font-weight: 500 !important;
}

.select2-container--bootstrap-5 .select2-results__option {
    color: #000000 !important;
    background-color: #ffffff !important;
    font-weight: 500 !important;
    padding: 0.5rem 1rem !important;
}

.select2-container--bootstrap-5 .select2-results__option--highlighted {
    background-color: #e9ecef !important;
    color: #000000 !important;
    font-weight: 600 !important;
}

.select2-container--bootstrap-5 .select2-results__option--selected {
    background-color: #0d6efd !important;
    color: #ffffff !important;
    font-weight: 600 !important;
}

.select2-container--bootstrap-5 .select2-results__option[aria-selected="true"] {
    background-color: #0d6efd !important;
    color: #ffffff !important;
}

.select2-container--bootstrap-5 .select2-results__option--highlighted[aria-selected="true"] {
    background-color: #0a58ca !important;
    color: #ffffff !important;
}

#importForm .form-control, 
#importForm .form-select {
    background-color: #fff !important;
    color: #000 !important;
    border: 2px solid #bdbdbd !important;
    font-weight: 500 !important;
    box-shadow: 0 1px 2px rgba(0,0,0,0.03);
    transition: border-color 0.2s, box-shadow 0.2s;
}
#importForm .form-control:focus, 
#importForm .form-select:focus {
    border-color: #0d6efd !important;
    color: #000 !important;
    background-color: #fff !important;
    box-shadow: 0 0 0 0.15rem rgba(13,110,253,.08) !important;
}
#importForm .form-label, 
#importForm .form-check-label {
    color: #000 !important;
    font-size: 1.15rem !important;
    font-weight: 700 !important;
    margin-bottom: 0.5rem !important;
    letter-spacing: 0.01em;
}
#importForm .form-control::placeholder {
    color: #000 !important;
    opacity: 1 !important;
}
#importForm .form-text, #importForm .text-muted {
    color: #000 !important;
    opacity: 0.8 !important;
}

.card#import-card {
    background: #fff !important;
    box-shadow: 0 4px 24px rgba(0,0,0,0.08), 0 1.5px 4px rgba(0,0,0,0.03);
    border-radius: 0.5rem;
}
</style>
@endsection 