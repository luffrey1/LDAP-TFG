@extends('layouts.dashboard')

@section('title', 'Importar Alumnos desde LDAP')

@section('content')
<section class="section">
    <div class="section-header">
        <h1><i class="fas fa-users"></i> Importar Alumnos desde LDAP</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
            <div class="breadcrumb-item"><a href="{{ route('profesor.alumnos.index') }}">Alumnos</a></div>
            <div class="breadcrumb-item">Importar desde LDAP</div>
        </div>
    </div>

    <div class="section-body">
        <h2 class="section-title">Búsqueda e Importación de Alumnos</h2>
        <p class="section-lead">Busque alumnos en el directorio LDAP y asígnelos a sus clases.</p>

        @include('partials.session_messages')

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Buscar Alumnos</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('profesor.alumnos.ldap.buscar') }}" method="GET">
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" name="termino" value="{{ $terminoBusqueda }}" 
                                    placeholder="Buscar por nombre, apellido o identificador...">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search"></i> Buscar
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        @if(!empty($terminoBusqueda) && empty($resultados))
                            <div class="alert alert-info">
                                No se encontraron resultados para "{{ $terminoBusqueda }}". 
                                Pruebe con otro término de búsqueda.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if(!empty($resultados))
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Resultados de la búsqueda ({{ count($resultados) }})</h4>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('profesor.alumnos.ldap.importar') }}" method="POST">
                            @csrf
                            
                            <div class="form-group">
                                <label for="clase_grupo_id">Asignar a Clase / Grupo <span class="text-danger">*</span></label>
                                <select name="clase_grupo_id" id="clase_grupo_id" class="form-control select2" required>
                                    <option value="">Seleccione un grupo</option>
                                    @foreach($grupos as $grupo)
                                        <option value="{{ $grupo->id }}" 
                                            {{ (isset($grupoSeleccionado) && $grupoSeleccionado->id == $grupo->id) ? 'selected' : '' }}>
                                            {{ $grupo->nombre }} ({{ $grupo->nivel }} {{ $grupo->curso }}º {{ $grupo->seccion }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div class="table-responsive">
                                <table class="table table-striped" id="ldap-users-table">
                                    <thead>
                                        <tr>
                                            <th class="text-center">
                                                <div class="custom-checkbox custom-control">
                                                    <input type="checkbox" id="selectAll" class="custom-control-input">
                                                    <label for="selectAll" class="custom-control-label"></label>
                                                </div>
                                            </th>
                                            <th>ID Usuario</th>
                                            <th>Nombre</th>
                                            <th>Apellidos</th>
                                            <th>Email</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($resultados as $alumno)
                                        <tr>
                                            <td class="text-center">
                                                <div class="custom-checkbox custom-control">
                                                    <input type="checkbox" name="alumnos[]" 
                                                        value="{{ $alumno['uid'] }}" 
                                                        id="alumno-{{ $alumno['uid'] }}" 
                                                        class="custom-control-input alumno-checkbox"
                                                        {{ $alumno['ya_importado'] ? 'checked' : '' }}>
                                                    <label for="alumno-{{ $alumno['uid'] }}" class="custom-control-label"></label>
                                                </div>
                                            </td>
                                            <td>{{ $alumno['uid'] }}</td>
                                            <td>{{ $alumno['nombre'] }}</td>
                                            <td>{{ $alumno['apellidos'] }}</td>
                                            <td>{{ $alumno['email'] }}</td>
                                            <td>
                                                @if($alumno['ya_importado'])
                                                    <span class="badge badge-info">
                                                        En grupo: {{ $alumno['clase_actual'] }}
                                                    </span>
                                                @else
                                                    <span class="badge badge-success">Disponible</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-file-import"></i> Importar Alumnos Seleccionados
                                </button>
                                <a href="{{ route('profesor.alumnos.index') }}" class="btn btn-light ml-2">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</section>
@endsection

@section('scripts')
<script>
    $(document).ready(function() {
        // Inicializar select2
        $('.select2').select2();
        
        // Seleccionar/deseleccionar todos
        $('#selectAll').click(function() {
            $('.alumno-checkbox').prop('checked', $(this).prop('checked'));
        });
        
        // Buscar en la tabla
        $('#search-input').on('keyup', function() {
            var value = $(this).val().toLowerCase();
            $('#ldap-users-table tbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
    });
</script>
@endsection 