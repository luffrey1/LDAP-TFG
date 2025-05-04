@extends('layouts.dashboard')

@section('title', 'Detalles de la Clase')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">{{ $clase->nombre }}</h3>
                    <div>
                        <a href="{{ route('profesor.clases.mias') }}" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                        <a href="{{ route('profesor.alumnos.clase', $clase->id) }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-user-plus"></i> Gestionar Alumnos
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h4>Información General</h4>
                            <table class="table table-bordered">
                                <tr>
                                    <th>Código:</th>
                                    <td>{{ $clase->codigo }}</td>
                                </tr>
                                <tr>
                                    <th>Nivel:</th>
                                    <td>{{ $clase->nivel }}</td>
                                </tr>
                                <tr>
                                    <th>Curso:</th>
                                    <td>{{ $clase->curso }}º {{ $clase->seccion }}</td>
                                </tr>
                                <tr>
                                    <th>Profesor:</th>
                                    <td>{{ $clase->profesor->name ?? 'No asignado' }}</td>
                                </tr>
                                <tr>
                                    <th>Descripción:</th>
                                    <td>{{ $clase->descripcion ?: 'No hay descripción' }}</td>
                                </tr>
                                <tr>
                                    <th>Estado:</th>
                                    <td>
                                        @if($clase->activo)
                                            <span class="badge badge-success">Activo</span>
                                        @else
                                            <span class="badge badge-danger">Inactivo</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h4>Alumnos en la Clase</h4>
                            @if(count($alumnos) > 0)
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Nombre</th>
                                                <th>Email</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($alumnos as $alumno)
                                            <tr>
                                                <td>{{ $alumno->nombre }} {{ $alumno->apellidos }}</td>
                                                <td>{{ $alumno->email ?: 'No disponible' }}</td>
                                                <td>
                                                    <a href="{{ route('profesor.alumnos.show', $alumno->id) }}" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="alert alert-info">
                                    No hay alumnos asignados a esta clase.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 