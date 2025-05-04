@extends('layouts.dashboard')

@section('title', 'Detalles del Grupo')

@section('content')
<section class="section">
    <div class="section-header">
        <h1><i class="fas fa-chalkboard"></i> {{ $grupo->nombre }}</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard') }}">Dashboard</a></div>
            <div class="breadcrumb-item"><a href="{{ route('profesor.clases.index') }}">Clases</a></div>
            <div class="breadcrumb-item">Detalles</div>
        </div>
    </div>

    <div class="section-body">
        <div class="row">
            <div class="col-12 col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h4>Información del Grupo</h4>
                    </div>
                    <div class="card-body">
                        <div class="profile-widget-description pb-0">
                            <div class="profile-widget-name mb-3">
                                {{ $grupo->nombre }} 
                                <div class="text-muted d-inline font-weight-normal">
                                    <div class="slash"></div> 
                                    {{ $grupo->nivel }} - {{ $grupo->curso }}º {{ $grupo->seccion }}
                                </div>
                            </div>
                            <p><strong>Código:</strong> <code>{{ $grupo->codigo }}</code></p>
                            <p><strong>Descripción:</strong> {{ $grupo->descripcion ?: 'Sin descripción' }}</p>
                            <p><strong>Profesor tutor:</strong> {{ $grupo->profesor->name }}</p>
                            <p><strong>Estado:</strong> 
                                @if($grupo->activo)
                                <span class="badge badge-success">Activo</span>
                                @else
                                <span class="badge badge-danger">Inactivo</span>
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="card-footer text-right">
                        <a href="{{ route('profesor.clases.edit', $grupo->id) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Editar Grupo
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4>
                            Alumnos
                            <span class="badge badge-primary ml-2">{{ count($alumnos) }}</span>
                        </h4>
                        <div class="card-header-action">
                            <a href="{{ route('profesor.alumnos.create') }}?clase_grupo_id={{ $grupo->id }}" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Añadir Alumno
                            </a>
                            <a href="{{ route('profesor.alumnos.ldap.buscar') }}?clase_grupo_id={{ $grupo->id }}" class="btn btn-success">
                                <i class="fas fa-search"></i> Buscar en LDAP
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped" id="alumnos-table">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Expediente</th>
                                        <th>Usuario LDAP</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($alumnos as $alumno)
                                    <tr class="{{ !$alumno->activo ? 'table-secondary' : '' }}">
                                        <td>{{ $alumno->nombre_completo }}</td>
                                        <td>{{ $alumno->email ?: '-' }}</td>
                                        <td>{{ $alumno->numero_expediente ?: '-' }}</td>
                                        <td>
                                            @if($alumno->cuenta_creada)
                                            <div class="badge badge-success">{{ $alumno->usuario_ldap }}</div>
                                            @else
                                            <div class="badge badge-warning">No creada</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="buttons">
                                                <a href="{{ route('profesor.alumnos.show', $alumno->id) }}" class="btn btn-sm btn-info" data-toggle="tooltip" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('profesor.alumnos.edit', $alumno->id) }}" class="btn btn-sm btn-warning" data-toggle="tooltip" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="{{ route('profesor.alumnos.actividades', $alumno->id) }}" class="btn btn-sm btn-primary" data-toggle="tooltip" title="Ver actividad">
                                                    <i class="fas fa-chart-line"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center">
                                            <div class="empty-state">
                                                <div class="empty-state-icon">
                                                    <i class="fas fa-user-graduate"></i>
                                                </div>
                                                <h2>No hay alumnos registrados</h2>
                                                <p class="lead">
                                                    Este grupo no tiene alumnos asignados.
                                                </p>
                                                <a href="{{ route('profesor.alumnos.create') }}?clase_grupo_id={{ $grupo->id }}" class="btn btn-primary mt-4">
                                                    <i class="fas fa-plus"></i> Añadir Alumno
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
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
        // Búsqueda en la tabla
        $("#search-input").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#alumnos-table tbody tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
    });
</script>
@endsection 