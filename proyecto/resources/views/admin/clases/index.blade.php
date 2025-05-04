@extends('layouts.dashboard')

@section('title', 'Gestión de Clases - Admin')

@section('content')
<section class="section">
    <div class="section-header">
        <h1><i class="fas fa-chalkboard"></i> Gestión de Clases</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard.index') }}">Dashboard</a></div>
            <div class="breadcrumb-item">Admin</div>
            <div class="breadcrumb-item">Clases</div>
        </div>
    </div>

    <div class="section-body">
        <h2 class="section-title">Administración de Clases y Grupos</h2>
        <p class="section-lead">Gestione todas las clases, grupos y sus alumnos en el sistema.</p>

        <div class="row mb-4">
            <div class="col-12">
                <a href="{{ route('profesor.clases.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Crear Nuevo Grupo
                </a>
            </div>
        </div>

        @include('partials.session_messages')

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Listado Completo de Clases</h4>
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
                            <table class="table table-striped" id="clases-table">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Nivel</th>
                                        <th>Curso/Sección</th>
                                        <th>Código</th>
                                        <th>Profesor</th>
                                        <th>Alumnos</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($grupos as $grupo)
                                    <tr class="{{ !$grupo->activo ? 'table-secondary' : '' }}">
                                        <td>{{ $grupo->nombre }}</td>
                                        <td>{{ $grupo->nivel }}</td>
                                        <td>{{ $grupo->curso }}º {{ $grupo->seccion }}</td>
                                        <td><code>{{ $grupo->codigo }}</code></td>
                                        <td>
                                            @if($grupo->profesor)
                                                <a href="{{ route('admin.users.show', $grupo->profesor->id) }}">
                                                    {{ $grupo->profesor->name }}
                                                </a>
                                            @else
                                                <span class="text-muted">No asignado</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="badge badge-info">{{ $grupo->alumnos_count ?? $grupo->numero_alumnos }}</div>
                                        </td>
                                        <td>
                                            @if($grupo->activo)
                                            <div class="badge badge-success">Activo</div>
                                            @else
                                            <div class="badge badge-danger">Inactivo</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="buttons">
                                                <a href="{{ route('admin.clases.show', $grupo->id) }}" class="btn btn-sm btn-info" data-toggle="tooltip" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('profesor.clases.edit', $grupo->id) }}" class="btn btn-sm btn-warning" data-toggle="tooltip" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form action="{{ route('profesor.clases.destroy', $grupo->id) }}" method="POST" class="d-inline delete-form">
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
                                        <td colspan="8" class="text-center">
                                            <div class="empty-state">
                                                <div class="empty-state-icon">
                                                    <i class="fas fa-question"></i>
                                                </div>
                                                <h2>No se encontraron clases</h2>
                                                <p class="lead">
                                                    No hay grupos o clases registrados en el sistema.
                                                </p>
                                                <a href="{{ route('profesor.clases.create') }}" class="btn btn-primary mt-4">
                                                    <i class="fas fa-plus"></i> Crear Nuevo Grupo
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
            $("#clases-table tbody tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });

        // Confirmar eliminación
        $('.delete-form').on('submit', function(e) {
            e.preventDefault();
            var form = this;
            
            Swal.fire({
                title: '¿Estás seguro?',
                text: "Esta acción no se puede deshacer. Solo se pueden eliminar grupos sin alumnos.",
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