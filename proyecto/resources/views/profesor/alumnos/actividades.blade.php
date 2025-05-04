@extends('layouts.dashboard')

@section('title', 'Actividades del alumno')

@section('content')
<section class="section">
    <div class="section-header">
        <h1><i class="fas fa-chart-line"></i> Actividades de {{ $alumno->nombre_completo }}</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard.index') }}">Dashboard</a></div>
            <div class="breadcrumb-item"><a href="{{ route('profesor.alumnos.index') }}">Alumnos</a></div>
            <div class="breadcrumb-item">Actividades</div>
        </div>
    </div>

    <div class="section-body">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Registro de actividades</h4>
                        <div class="card-header-action">
                            <a href="{{ route('profesor.alumnos.show', $alumno->id) }}" class="btn btn-icon btn-primary">
                                <i class="fas fa-arrow-left"></i> Volver al perfil
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($actividades->isEmpty())
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <i class="fas fa-history"></i>
                                </div>
                                <h2>No hay actividades registradas</h2>
                                <p class="lead">
                                    Este alumno no tiene actividades registradas en el sistema.
                                </p>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Tipo</th>
                                            <th>Descripción</th>
                                            <th>Registrado por</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($actividades as $actividad)
                                        <tr>
                                            <td>{{ $actividad->fecha_hora->format('d/m/Y H:i') }}</td>
                                            <td>
                                                <span class="badge badge-{{ $actividad->tipo_badge }}">
                                                    {{ $actividad->tipo_texto }}
                                                </span>
                                            </td>
                                            <td>{{ $actividad->descripcion }}</td>
                                            <td>{{ $actividad->usuario ? $actividad->usuario->name : 'Sistema' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mt-4">
                                {{ $actividades->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection 