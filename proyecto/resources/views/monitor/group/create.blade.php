@extends('layouts.dashboard')

@section('title', 'Crear Grupo de Monitoreo')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-plus-circle me-2"></i> Crear Nuevo Grupo de Monitoreo
                    </h4>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <strong>¡Error!</strong> Hay problemas con los datos ingresados.
                            <ul class="mb-0 mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                        </div>
                    @endif

                    <form action="{{ route('monitor.groups.store') }}" method="POST">
                        @csrf
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre del Grupo *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                <input type="text" class="form-control" id="name" name="name" 
                                       placeholder="Ej: B27, Aula Informática, etc." value="{{ old('name') }}" required>
                            </div>
                            <small class="text-muted">Para aulas, use el formato B27, B21, etc. según el esquema del instituto.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Descripción</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-align-left"></i></span>
                                <textarea class="form-control" id="description" name="description" 
                                          rows="3" placeholder="Descripción del grupo">{{ old('description') }}</textarea>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="type" class="form-label">Tipo de Grupo</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-sitemap"></i></span>
                                    <select class="form-select" id="type" name="type">
                                        <option value="classroom" {{ old('type') == 'classroom' ? 'selected' : '' }}>Aula</option>
                                        <option value="lab" {{ old('type') == 'lab' ? 'selected' : '' }}>Laboratorio</option>
                                        <option value="office" {{ old('type') == 'office' ? 'selected' : '' }}>Oficina</option>
                                        <option value="department" {{ old('type') == 'department' ? 'selected' : '' }}>Departamento</option>
                                        <option value="other" {{ old('type') == 'other' ? 'selected' : '' }}>Otro</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="location" class="form-label">Ubicación</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                    <input type="text" class="form-control" id="location" name="location" 
                                           placeholder="Ej: Planta 2, Edificio B, etc." value="{{ old('location') }}">
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <a href="{{ route('monitor.groups.index') }}" class="btn btn-secondary me-md-2">
                                <i class="fas fa-times me-1"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i> Guardar Grupo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 