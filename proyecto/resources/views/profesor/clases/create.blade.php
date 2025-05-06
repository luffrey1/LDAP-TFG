@extends('layouts.dashboard')

@section('title', 'Nuevo Grupo')

@section('content')
<section class="section">
    <div class="section-header">
        <h1><i class="fas fa-chalkboard-teacher"></i> Nuevo Grupo</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard.index') }}">Dashboard</a></div>
            <div class="breadcrumb-item"><a href="{{ route('profesor.clases.index') }}">Clases</a></div>
            <div class="breadcrumb-item">Nuevo</div>
        </div>
    </div>

    <div class="section-body">
        <h2 class="section-title">Registro de Grupo</h2>
        <p class="section-lead">Complete el formulario para crear un nuevo grupo o clase.</p>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Formulario de Registro</h4>
                    </div>
                    <form action="{{ route('profesor.clases.store') }}" method="POST">
                        @csrf
                        <div class="card-body">
                            @include('partials.session_messages')

                            <div class="form-group row">
                                <label for="nombre" class="col-sm-3 col-form-label">Nombre del Grupo <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <input type="text" name="nombre" id="nombre" class="form-control @error('nombre') is-invalid @enderror" value="{{ old('nombre') }}" required autofocus>
                                    @error('nombre')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Ejemplo: "1º ESO A", "2º Bachillerato Ciencias", etc.</small>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="nivel" class="col-sm-3 col-form-label">Nivel <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <select name="nivel" id="nivel" class="form-control @error('nivel') is-invalid @enderror" required>
                                        <option value="">Seleccione un nivel</option>
                                        @foreach($niveles as $nivel)
                                            <option value="{{ $nivel }}" {{ old('nivel') == $nivel ? 'selected' : '' }}>{{ $nivel }}</option>
                                        @endforeach
                                    </select>
                                    @error('nivel')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="curso" class="col-sm-3 col-form-label">Curso <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <input type="text" name="curso" id="curso" class="form-control @error('curso') is-invalid @enderror" value="{{ old('curso') }}" required>
                                    @error('curso')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Ejemplo: "1", "2", "3", etc.</small>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="seccion" class="col-sm-3 col-form-label">Sección <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <input type="text" name="seccion" id="seccion" class="form-control @error('seccion') is-invalid @enderror" value="{{ old('seccion') }}" required>
                                    @error('seccion')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Ejemplo: "A", "B", "C", "Ciencias", etc.</small>
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="descripcion" class="col-sm-3 col-form-label">Descripción</label>
                                <div class="col-sm-9">
                                    <textarea name="descripcion" id="descripcion" class="form-control @error('descripcion') is-invalid @enderror" rows="3">{{ old('descripcion') }}</textarea>
                                    @error('descripcion')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="profesor_id" class="col-sm-3 col-form-label">Profesor</label>
                                <div class="col-sm-9">
                                    <select name="profesor_id" id="profesor_id" class="form-control @error('profesor_id') is-invalid @enderror" required>
                                        <option value="">Seleccione un profesor</option>
                                        @foreach($profesores as $profesor)
                                            @php
                                                $profesorId = $profesor->username ?? $profesor->id;
                                                $esLdap = !is_numeric($profesor->id) && strpos($profesor->id, 'ldap_') === 0;
                                            @endphp
                                            <option value="{{ $profesorId }}" {{ old('profesor_id') == $profesorId ? 'selected' : '' }}>
                                                {{ $profesor->name }} 
                                                @if($esLdap) (LDAP) @endif
                                                @if($profesor->role == 'admin') (Admin) @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('profesor_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            <a href="{{ route('profesor.clases.index') }}" class="btn btn-secondary mr-2">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Grupo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection 