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
                                    <input type="file" name="file" class="custom-file-input" id="file" accept=".csv, .xlsx, .xls" required>
                                    <label class="custom-file-label" for="file">Seleccionar archivo</label>
                                    <div class="form-text text-muted">Formatos aceptados: CSV, Excel (.xlsx, .xls)</div>
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="custom-control custom-checkbox">
                                    <input type="checkbox" name="header_row" id="header_row" class="custom-control-input" checked>
                                    <label class="custom-control-label" for="header_row">El archivo contiene fila de encabezados</label>
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
        // Mostrar nombre del archivo al seleccionarlo
        $('.custom-file-input').on('change', function() {
            let fileName = $(this).val().split('\\').pop();
            $(this).next('.custom-file-label').addClass("selected").html(fileName);
        });

        // Inicializar select2
        $('.select2').select2();
    });
</script>
@endsection 