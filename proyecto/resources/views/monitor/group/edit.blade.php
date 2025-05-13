@extends('layouts.dashboard')

@section('title', 'Editar Grupo de Monitoreo')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-edit me-2"></i> Editar Grupo de Monitoreo
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

                    <form action="{{ route('monitor.groups.update', $group->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre del Grupo *</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-tag"></i></span>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="{{ old('name', $group->name) }}" required>
                            </div>
                            <small class="text-muted">Para aulas, use el formato B27, B21, etc. según el esquema del instituto.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Descripción</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-align-left"></i></span>
                                <textarea class="form-control" id="description" name="description" 
                                          rows="3">{{ old('description', $group->description) }}</textarea>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="type" class="form-label">Tipo de Grupo</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-sitemap"></i></span>
                                    <select class="form-select" id="type" name="type">
                                        <option value="classroom" {{ old('type', $group->type) == 'classroom' ? 'selected' : '' }}>Aula</option>
                                        <option value="lab" {{ old('type', $group->type) == 'lab' ? 'selected' : '' }}>Laboratorio</option>
                                        <option value="office" {{ old('type', $group->type) == 'office' ? 'selected' : '' }}>Oficina</option>
                                        <option value="department" {{ old('type', $group->type) == 'department' ? 'selected' : '' }}>Departamento</option>
                                        <option value="other" {{ old('type', $group->type) == 'other' ? 'selected' : '' }}>Otro</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label for="location" class="form-label">Ubicación</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                    <input type="text" class="form-control" id="location" name="location" 
                                           value="{{ old('location', $group->location) }}">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title"><i class="fas fa-info-circle me-2"></i> Información del Grupo</h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Total de equipos:</strong> {{ $group->total_hosts_count }}</p>
                                            <p class="mb-1"><strong>Equipos en línea:</strong> {{ $group->online_hosts_count }}</p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Creado:</strong> {{ $group->created_at->format('d/m/Y H:i') }}</p>
                                            <p class="mb-1"><strong>Última actualización:</strong> {{ $group->updated_at->format('d/m/Y H:i') }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                            <a href="{{ route('monitor.groups.show', $group->id) }}" class="btn btn-info me-md-2">
                                <i class="fas fa-eye me-1"></i> Ver Detalles
                            </a>
                            <a href="{{ route('monitor.groups.index') }}" class="btn btn-secondary me-md-2">
                                <i class="fas fa-times me-1"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-1"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 