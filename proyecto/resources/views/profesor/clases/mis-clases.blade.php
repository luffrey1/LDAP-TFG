@extends('layouts.dashboard')

@section('title', 'Mis Clases')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center mb-4">
        <h1 class="h2">Mis Clases</h1>
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
        @if($misClases->isEmpty())
            <div class="col-12">
                <div class="card mb-4">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-chalkboard-teacher fa-4x mb-3 text-muted"></i>
                        <h4>No tienes clases asignadas</h4>
                        <p class="text-muted">Cuando seas asignado como profesor/tutor de una clase, aparecerá aquí.</p>
                    </div>
                </div>
            </div>
        @else
            @foreach($misClases as $clase)
            <div class="col-md-6 col-xl-4 mb-4">
                <div class="card h-100 text-white">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ $clase->nombre }}</h5>
                        <span class="badge bg-primary">{{ $clase->codigo }}</span>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <i class="fas fa-users me-2"></i> <span class="fw-bold">{{ $clase->cantidad_alumnos }}</span> alumnos
                        </div>
                        
                        <div class="mb-3">
                            <i class="fas fa-graduation-cap me-2"></i> Nivel: <span class="fw-bold">{{ $clase->nivel }}</span>
                        </div>
                        
                        <div class="mb-3">
                            <i class="fas fa-calendar me-2"></i> Curso: <span class="fw-bold">{{ $clase->curso }}º {{ $clase->seccion }}</span>
                        </div>
                        
                        @if($clase->descripcion)
                        <div class="mb-3">
                            <p class="text-muted">{{ \Illuminate\Support\Str::limit($clase->descripcion, 100) }}</p>
                        </div>
                        @endif
                    </div>
                    <div class="card-footer bg-transparent border-top-0">
                        <a href="{{ route('profesor.clases.mias.ver', $clase->id) }}" class="btn btn-primary w-100">
                            <i class="fas fa-eye me-1"></i> Ver detalles
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        @endif
    </div>
</div>
@endsection 