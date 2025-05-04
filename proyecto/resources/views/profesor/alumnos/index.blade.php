@extends('layouts.dashboard')

@section('title', 'Gestión de Alumnos')

@section('content')
<section class="section">
    <div class="section-header">
        <h1><i class="fas fa-user-graduate"></i> Alumnos</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard.index') }}">Dashboard</a></div>
            <div class="breadcrumb-item">Alumnos</div>
        </div>
    </div>

    <div class="section-body">
        <h2 class="section-title">Gestión de Alumnos</h2>
        <p class="section-lead">Administre los alumnos de sus clases y grupos.</p>

        <div class="row mb-4">
            <div class="col-12">
                <div class="btn-group">
                    <a href="{{ route('profesor.alumnos.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nuevo Alumno
                    </a>
                    <a href="{{ route('profesor.alumnos.import') }}" class="btn btn-info">
                        <i class="fas fa-file-import"></i> Importar CSV
                    </a>
                    <a href="{{ route('profesor.alumnos.ldap.buscar') }}" class="btn btn-success">
                        <i class="fas fa-search"></i> Buscar en LDAP
                    </a>
                </div>
            </div>
        </div>

        @include('partials.session_messages')

        @if(session('importErrors'))
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <h5><i class="fas fa-exclamation-triangle"></i> Advertencia</h5>
            <p>Se encontraron errores durante la importación:</p>
            <ul>
                @foreach(session('importErrors') as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        @endif

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Listado de Alumnos</h4>
                        <div class="card-header-form">
                            <div class="input-group">
                                <input type="text" class="form-control" id="search-input" placeholder="Buscar...">
                                <div class="input-group-btn">
                                    <button class="btn btn-primary"><i class="fas fa-search"></i></button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped" id="alumnos-table">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>Grupo</th>
                                        <th>Expediente</th>
                                        <th>Usuario LDAP</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($alumnos as $alumno)
                                    <tr class="{{ !$alumno->activo ? 'table-secondary' : '' }}">
                                        <td>{{ $alumno->nombre_completo }}</td>
                                        <td>{{ $alumno->email ?: '-' }}</td>
                                        <td>
                                            <a href="{{ route('profesor.clases.show', $alumno->clase_grupo_id) }}">
                                                {{ $alumno->grupo->nombre_completo }}
                                            </a>
                                        </td>
                                        <td>{{ $alumno->numero_expediente ?: '-' }}</td>
                                        <td>
                                            @if($alumno->cuenta_creada)
                                            <div class="badge badge-success">{{ $alumno->usuario_ldap }}</div>
                                            @else
                                            <div class="badge badge-warning">No creada</div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($alumno->activo)
                                            <div class="badge badge-success">Activo</div>
                                            @else
                                            <div class="badge badge-danger">Inactivo</div>
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
                                                <form action="{{ route('profesor.alumnos.destroy', $alumno->id) }}" method="POST" class="d-inline delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" data-toggle="tooltip" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            <div class="empty-state">
                                                <div class="empty-state-icon">
                                                    <i class="fas fa-question"></i>
                                                </div>
                                                <h2>No se encontraron alumnos</h2>
                                                <p class="lead">
                                                    No hay alumnos registrados en sus grupos.
                                                </p>
                                                <div class="btn-group mt-4">
                                                    <a href="{{ route('profesor.alumnos.create') }}" class="btn btn-primary">
                                                        <i class="fas fa-plus"></i> Nuevo Alumno
                                                    </a>
                                                    <a href="{{ route('profesor.alumnos.import') }}" class="btn btn-info">
                                                        <i class="fas fa-file-import"></i> Importar Alumnos
                                                    </a>
                                                </div>
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

        // Confirmar eliminación
        $('.delete-form').on('submit', function(e) {
            e.preventDefault();
            var form = this;
            
            Swal.fire({
                title: '¿Estás seguro?',
                text: "Esta acción no se puede deshacer.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
@endsection 