@extends('layouts.dashboard')

@section('title', 'Gestión de Clases')

@section('content')
<section class="section">
    <div class="section-header">
        <h1><i class="fas fa-chalkboard"></i> Gestión de Clases</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard.index') }}">Dashboard</a></div>
            <div class="breadcrumb-item">Gestión de Clases</div>
        </div>
    </div>

    <div class="section-body">
        <h2 class="section-title">Lista de Clases</h2>
        <p class="section-lead">Administre las clases y grupos del centro educativo.</p>

        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Acciones</h4>
                    </div>
                    <div class="card-body">
                        <a href="{{ route('profesor.clases.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nueva Clase
                        </a>
                    </div>
                </div>
            </div>
        </div>

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

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Clases</h4>
                    </div>
                    <div class="card-body">
                        @if(count($grupos) > 0)
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Nivel</th>
                                        <th>Curso</th>
                                        <th>Sección</th>
                                        <th>Profesor</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($grupos as $grupo)
                                    <tr>
                                        <td>{{ $grupo->nombre }}</td>
                                        <td>{{ $grupo->nivel }}</td>
                                        <td>{{ $grupo->curso }}</td>
                                        <td>{{ $grupo->seccion }}</td>
                                        <td>{{ $grupo->profesor->name ?? 'Sin asignar' }}</td>
                                        <td>
                                            @if($grupo->activo)
                                            <span class="badge badge-success">Activo</span>
                                            @else
                                            <span class="badge badge-danger">Inactivo</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{{ route('profesor.clases.show', $grupo->id) }}" class="btn btn-primary btn-sm">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('profesor.clases.mias.ver', $grupo->id) }}" class="btn btn-info btn-sm" title="Ver como tutor">
                                                    <i class="fas fa-chalkboard-teacher"></i>
                                                </a>
                                                <a href="{{ route('profesor.clases.edit', $grupo->id) }}" class="btn btn-warning btn-sm">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </a>
                                                <form action="{{ route('profesor.clases.destroy', $grupo->id) }}" method="POST" onsubmit="return confirm('¿Está seguro de eliminar esta clase?');" style="display: inline;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="text-center py-5">
                            <i class="fas fa-chalkboard fa-4x mb-3 text-muted"></i>
                            <h4>No hay clases registradas</h4>
                            <p class="text-muted">Comience creando una nueva clase.</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection 