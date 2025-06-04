@extends('layouts.dashboard')

@section('title', 'Previsualización de Importación')

@section('content')
<div class="section">
    <div class="section-header">
        <h1><i class="fas fa-file-import"></i> Previsualización de Importación</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard.index') }}">Dashboard</a></div>
            <div class="breadcrumb-item">Alumnos</div>
            <div class="breadcrumb-item">Importar</div>
            <div class="breadcrumb-item">Previsualización</div>
        </div>
    </div>

    <div class="section-body">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Previsualización de Datos</h4>
                        <div class="card-header-action">
                            <button class="btn btn-primary" id="download-csv">
                                <i class="fas fa-download"></i> Descargar CSV con Contraseñas
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        @if(count($errores) > 0)
                            <div class="alert alert-warning">
                                <h6><i class="fas fa-exclamation-triangle"></i> Se encontraron los siguientes errores:</h6>
                                <ul class="mb-0">
                                    @foreach($errores as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table class="table table-striped" id="preview-table">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Apellidos</th>
                                        <th>Email</th>
                                        <th>DNI</th>
                                        <th>Nº Expediente</th>
                                        <th>Fecha Nacimiento</th>
                                        <th>Contraseña</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($alumnos as $alumno)
                                    <tr>
                                        <td>{{ $alumno['nombre'] }}</td>
                                        <td>{{ $alumno['apellidos'] }}</td>
                                        <td>{{ $alumno['email'] ?? '-' }}</td>
                                        <td>{{ $alumno['dni'] ?? '-' }}</td>
                                        <td>{{ $alumno['numero_expediente'] ?? '-' }}</td>
                                        <td>{{ $alumno['fecha_nacimiento'] ? date('d/m/Y', strtotime($alumno['fecha_nacimiento'])) : '-' }}</td>
                                        <td><code>{{ $alumno['password'] }}</code></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <form action="{{ route('profesor.alumnos.import.process') }}" method="POST" enctype="multipart/form-data" class="mt-4" id="import-form">
                            @csrf
                            <input type="hidden" name="confirmar_importacion" value="1">
                            <input type="hidden" name="clase_grupo_id" value="{{ $grupo->id }}">
                            <input type="hidden" name="alumnos_data" value="{{ json_encode($alumnos) }}">
                            
                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" class="custom-control-input" id="crear_cuentas_ldap" name="crear_cuentas_ldap" value="1" checked>
                                    <label class="custom-control-label" for="crear_cuentas_ldap">Crear cuentas LDAP para los alumnos</label>
                                </div>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary" id="confirm-import">
                                    <i class="fas fa-check"></i> Confirmar Importación
                                </button>
                                <a href="{{ route('profesor.alumnos.import') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    // Función para descargar CSV
    $('#download-csv').click(function() {
        // Crear encabezados
        let csv = 'Nombre,Apellidos,Email,DNI,Nº Expediente,Fecha Nacimiento,Contraseña\n';
        
        // Añadir datos
        $('#preview-table tbody tr').each(function() {
            let row = [];
            $(this).find('td').each(function(index) {
                let value = $(this).text().trim();
                // Si es la contraseña, quitar las etiquetas code
                if (index === 6) {
                    value = value.replace(/<\/?code>/g, '');
                }
                // Escapar comas y comillas
                if (value.includes(',') || value.includes('"')) {
                    value = '"' + value.replace(/"/g, '""') + '"';
                }
                row.push(value);
            });
            csv += row.join(',') + '\n';
        });
        
        // Crear y descargar archivo
        let blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        let link = document.createElement('a');
        let url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', 'alumnos_con_contraseñas.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    // Asegurarse de que el formulario se envía como POST
    $('#import-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var formData = new FormData(this);
        
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                window.location.href = response.redirect || '{{ route("profesor.alumnos.index") }}';
            },
            error: function(xhr) {
                if (xhr.status === 405) {
                    alert('Error: Método no permitido. Por favor, intente nuevamente.');
                } else {
                    alert('Error al procesar la importación. Por favor, intente nuevamente.');
                }
            }
        });
    });
});
</script>
@endsection 