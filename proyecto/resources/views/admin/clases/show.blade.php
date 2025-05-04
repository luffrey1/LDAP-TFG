@extends('layouts.dashboard')

@section('title', "Detalles de Clase: {$grupo->nombre}")

@section('content')
<section class="section">
    <div class="section-header">
        <h1><i class="fas fa-chalkboard"></i> Detalles de Clase</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard.index') }}">Dashboard</a></div>
            <div class="breadcrumb-item"><a href="{{ route('admin.clases.index') }}">Clases</a></div>
            <div class="breadcrumb-item">{{ $grupo->nombre }}</div>
        </div>
    </div>

    <div class="section-body">
        @include('partials.session_messages')

        <div class="row">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h4>Información del Grupo</h4>
                        <div class="card-header-action">
                            <a href="{{ route('profesor.clases.edit', $grupo->id) }}" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li class="mb-3">
                                <strong>Nombre:</strong> {{ $grupo->nombre }}
                            </li>
                            <li class="mb-3">
                                <strong>Nivel:</strong> {{ $grupo->nivel }}
                            </li>
                            <li class="mb-3">
                                <strong>Curso:</strong> {{ $grupo->curso }}º
                            </li>
                            <li class="mb-3">
                                <strong>Sección:</strong> {{ $grupo->seccion }}
                            </li>
                            <li class="mb-3">
                                <strong>Código:</strong> <code>{{ $grupo->codigo }}</code>
                            </li>
                            <li class="mb-3">
                                <strong>Estado:</strong>
                                @if($grupo->activo)
                                <span class="badge badge-success">Activo</span>
                                @else
                                <span class="badge badge-danger">Inactivo</span>
                                @endif
                            </li>
                            <li class="mb-3">
                                <strong>Creado:</strong> {{ $grupo->created_at->format('d/m/Y H:i') }}
                            </li>
                            <li class="mb-3">
                                <strong>Actualizado:</strong> {{ $grupo->updated_at->format('d/m/Y H:i') }}
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h4>Profesor Asignado</h4>
                    </div>
                    <div class="card-body">
                        @if($grupo->profesor)
                        <div class="user-item">
                            <div class="user-details">
                                <div class="user-name">{{ $grupo->profesor->name }}</div>
                                <div class="text-muted">{{ $grupo->profesor->email }}</div>
                                <div class="user-cta">
                                    <a href="{{ route('admin.users.show', $grupo->profesor->id) }}" class="btn btn-sm btn-primary">Ver Perfil</a>
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="empty-state">
                            <div class="empty-state-icon bg-danger">
                                <i class="fas fa-times"></i>
                            </div>
                            <h2>Sin profesor asignado</h2>
                            <p class="lead">
                                Este grupo no tiene un profesor asignado.
                            </p>
                            <a href="{{ route('profesor.clases.edit', $grupo->id) }}" class="btn btn-warning mt-4">
                                Asignar profesor
                            </a>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h4>Alumnos Matriculados ({{ $alumnos->count() }})</h4>
                        <div class="card-header-action">
                            <a href="{{ route('profesor.alumnos.create', ['grupo_id' => $grupo->id]) }}" class="btn btn-primary">
                                <i class="fas fa-user-plus"></i> Añadir Alumno
                            </a>
                            <a href="{{ route('profesor.alumnos.import', ['grupo_id' => $grupo->id]) }}" class="btn btn-success">
                                <i class="fas fa-file-import"></i> Importar
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped" id="alumnos-table">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Usuario LDAP</th>
                                        <th>Identificación</th>
                                        <th>Email</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($alumnos as $alumno)
                                    <tr>
                                        <td>{{ $alumno->nombre }} {{ $alumno->apellidos }}</td>
                                        <td><code>{{ $alumno->username }}</code></td>
                                        <td>{{ $alumno->identificacion }}</td>
                                        <td>{{ $alumno->email }}</td>
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
                                        <td colspan="6" class="text-center">
                                            <div class="empty-state">
                                                <div class="empty-state-icon">
                                                    <i class="fas fa-user-graduate"></i>
                                                </div>
                                                <h2>No hay alumnos matriculados</h2>
                                                <p class="lead">
                                                    Este grupo no tiene alumnos matriculados aún.
                                                </p>
                                                <div class="mt-4">
                                                    <a href="{{ route('profesor.alumnos.create', ['grupo_id' => $grupo->id]) }}" class="btn btn-primary mr-2">
                                                        <i class="fas fa-user-plus"></i> Añadir Alumno
                                                    </a>
                                                    <a href="{{ route('profesor.alumnos.import', ['grupo_id' => $grupo->id]) }}" class="btn btn-success">
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