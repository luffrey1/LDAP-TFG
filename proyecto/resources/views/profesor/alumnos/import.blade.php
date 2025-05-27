@extends('layouts.dashboard')

@section('title', 'Importar Alumnos')

@section('content')
<section class="section">
    <div class="section-header">
        <h1><i class="fas fa-file-import"></i> Importar Alumnos</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard.index') }}">Dashboard</a></div>
            <div class="breadcrumb-item"><a href="{{ route('profesor.clases.index') }}">Clases</a></div>
            @if(isset($grupo))
            <div class="breadcrumb-item"><a href="{{ route('profesor.clases.show', $grupo->id) }}">{{ $grupo->nombre }}</a></div>
            @endif
            <div class="breadcrumb-item">Importar Alumnos</div>
        </div>
    </div>

    <div class="section-body">
        <h2 class="section-title">Importar Alumnos desde Archivo</h2>
        <p class="section-lead">Importe múltiples alumnos utilizando un archivo CSV o Excel.</p>

        @include('partials.session_messages')

        <div class="row">
            <div class="col-12 col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Instrucciones</h4>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <div class="alert-title">Formato del archivo</div>
                            <p>El archivo debe contener las siguientes columnas:</p>
                            <ul class="mb-0">
                                <li><strong>nombre</strong> - Nombre del alumno</li>
                                <li><strong>apellidos</strong> - Apellidos del alumno</li>
                                <li><strong>identificacion</strong> - DNI o identificación del alumno</li>
                                <li><strong>email</strong> - Correo electrónico (opcional)</li>
                            </ul>
                        </div>

                        <p>Puede descargar una plantilla de ejemplo para comenzar:</p>
                        <a href="{{ route('profesor.alumnos.template') }}" class="btn btn-info">
                            <i class="fas fa-download"></i> Descargar Plantilla
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h4>Subir Archivo</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('profesor.alumnos.import.process') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            
                            @if(!isset($grupo))
                            <div class="form-group">
                                <label for="clase_grupo_id">Clase / Grupo</label>
                                <select name="clase_grupo_id" id="clase_grupo_id" class="form-control select2" required>
                                    <option value="">Seleccione un grupo</option>
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
                            
                            <div class="form-group">
                                <label>Archivo de Alumnos</label>
                                <div class="custom-file">
                                    <input type="file" name="archivo_csv" class="custom-file-input" id="archivo_csv" accept=".csv, .xlsx, .xls" required>
                                    <label class="custom-file-label" for="archivo_csv">Seleccionar archivo</label>
                                    <div class="form-text text-muted">Formatos aceptados: CSV, Excel (.xlsx, .xls)</div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="separador">Separador CSV</label>
                                <select name="separador" id="separador" class="form-control" required>
                                    <option value=",">Coma (,)</option>
                                    <option value=";">Punto y coma (;)</option>
                                    <option value="\t">Tabulador</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" name="tiene_encabezados" id="tiene_encabezados" class="custom-control-input" checked>
                                    <label class="custom-control-label" for="tiene_encabezados">El archivo contiene fila de encabezados</label>
                                </div>
                            </div>

                            <!-- Previsualización simple -->
                            <div id="preview" class="table-responsive mt-3" style="display: none;">
                                <h5>Previsualización:</h5>
                                <div style="max-height: 400px; overflow-y: auto;">
                                    <table class="table table-bordered table-striped">
                                        <thead style="position: sticky; top: 0; background-color: #f8f9fa;">
                                            <tr id="preview-header">
                                            </tr>
                                        </thead>
                                        <tbody id="preview-content">
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-3">
                                    <button type="button" id="download-csv" class="btn btn-success">
                                        <i class="fas fa-download"></i> Descargar CSV con Contraseñas
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-upload"></i> Importar Alumnos
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

        // Mostrar nombre del archivo y previsualizar
        $('.custom-file-input').on('change', function() {
            let fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').addClass("selected").html(fileName);
            
            // Previsualizar CSV
            const file = this.files[0];
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
                                headerHtml += `<th style="font-weight: bold;">${cell.trim()}</th>`;
                            });
                            headerHtml += '<th style="font-weight: bold;">Contraseña</th>';
                            $('#preview-header').html(headerHtml);
                            csvData.push([...cells, 'Contraseña']);
                        } else {  // Resto de filas (datos)
                            html += '<tr>';
                            cells.forEach(cell => {
                                html += `<td>${cell.trim()}</td>`;
                            });
                            // Añadir columna de contraseña
                            const password = generatePassword();
                            html += `<td><span class="password">${password}</span></td>`;
                            html += '</tr>';
                            csvData.push([...cells, password]);
                        }
                    }
                });
                
                $('#preview-content').html(html);
                $('#preview').show();

                // Guardar los datos del CSV generado para la descarga
                window.csvData = csvData;
            };
            
            reader.readAsText(file);
        });

        // Actualizar previsualización cuando cambie el separador
        $('#separador').on('change', function() {
            const fileInput = document.getElementById('archivo_csv');
            if(fileInput.files.length > 0) {
                $(fileInput).trigger('change');
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
            link.setAttribute('download', 'alumnos_con_contraseñas.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });

        // Inicializar select2
        $('.select2').select2();
    });
</script>
@endsection 