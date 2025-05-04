@extends('layouts.dashboard')

@section('title', 'Detalle de Clase')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center mb-4">
        <div>
            <h1 class="h2">{{ $clase->nombre }}</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('profesor.clases.mias') }}">Mis Clases</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $clase->nombre }}</li>
                </ol>
            </nav>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="row">
        <!-- Información de la clase -->
        <div class="col-md-4 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Información de la Clase</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="fw-bold">Código:</label>
                        <p>{{ $clase->codigo }}</p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="fw-bold">Nivel:</label>
                        <p>{{ $clase->nivel }}</p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="fw-bold">Curso y sección:</label>
                        <p>{{ $clase->curso }}º {{ $clase->seccion }}</p>
                    </div>
                    
                    @if($clase->descripcion)
                    <div class="mb-3">
                        <label class="fw-bold">Descripción:</label>
                        <p>{{ $clase->descripcion }}</p>
                    </div>
                    @endif
                    
                    <div class="mb-3">
                        <label class="fw-bold">Alumnos en la clase:</label>
                        <p>{{ count($alumnos) }}</p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="fw-bold">Estado:</label>
                        <p>
                            @if($clase->activo)
                            <span class="badge bg-success">Activo</span>
                            @else
                            <span class="badge bg-danger">Inactivo</span>
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Lista de alumnos -->
        <div class="col-md-8 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Alumnos</h5>
                    <div>
                        <a href="{{ route('profesor.alumnos.create') }}?clase_grupo_id={{ $clase->id }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Añadir Alumno
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($alumnos->isEmpty())
                        <div class="text-center py-5">
                            <i class="fas fa-user-graduate fa-4x mb-3 text-muted"></i>
                            <h4>No hay alumnos en esta clase</h4>
                            <p class="text-muted">Cuando se añadan alumnos a esta clase, aparecerán aquí.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Email</th>
                                        <th>DNI</th>
                                        <th>Fecha de inscripción</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($alumnos as $alumnoClase)
                                    <tr>
                                        <td>{{ $alumnoClase->nombre }} {{ $alumnoClase->apellidos }}</td>
                                        <td>{{ $alumnoClase->email ?? 'N/A' }}</td>
                                        <td>{{ $alumnoClase->dni ?? 'N/A' }}</td>
                                        <td>{{ $alumnoClase->created_at->format('d/m/Y') }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('profesor.alumnos.show', $alumnoClase->id) }}" class="btn btn-sm btn-info" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="{{ route('profesor.alumnos.edit', $alumnoClase->id) }}" class="btn btn-sm btn-warning" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="{{ route('profesor.alumnos.actividades', $alumnoClase->id) }}" class="btn btn-sm btn-primary" title="Ver actividades">
                                                    <i class="fas fa-chart-line"></i>
                                                </a>
                                                <form action="{{ route('profesor.alumnos.destroy', $alumnoClase->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Está seguro de eliminar este alumno?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
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
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 