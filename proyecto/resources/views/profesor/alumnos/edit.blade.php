@extends('layouts.dashboard')

@section('title', 'Editar Alumno')

@section('content')
<section class="section">
    <div class="section-header">
        <h1><i class="fas fa-user-edit"></i> Editar Alumno</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard.index') }}">Dashboard</a></div>
            <div class="breadcrumb-item"><a href="{{ route('profesor.alumnos.index') }}">Alumnos</a></div>
            <div class="breadcrumb-item">Editar Alumno</div>
        </div>
    </div>

    <div class="section-body">
        <h2 class="section-title">Editar Información del Alumno</h2>
        <p class="section-lead">Modifique la información del alumno según sea necesario.</p>

        @include('partials.session_messages')

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('profesor.alumnos.update', $alumno->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="form-group row">
                                <label for="nombre" class="col-sm-3 col-form-label">Nombre <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <input type="text" name="nombre" id="nombre" class="form-control @error('nombre') is-invalid @enderror" value="{{ old('nombre', $alumno->nombre) }}" required>
                                    @error('nombre')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="apellidos" class="col-sm-3 col-form-label">Apellidos <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <input type="text" name="apellidos" id="apellidos" class="form-control @error('apellidos') is-invalid @enderror" value="{{ old('apellidos', $alumno->apellidos) }}" required>
                                    @error('apellidos')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="email" class="col-sm-3 col-form-label">Email</label>
                                <div class="col-sm-9">
                                    <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $alumno->email) }}">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="dni" class="col-sm-3 col-form-label">DNI/Identificación</label>
                                <div class="col-sm-9">
                                    <input type="text" name="dni" id="dni" class="form-control @error('dni') is-invalid @enderror" value="{{ old('dni', $alumno->dni) }}">
                                    @error('dni')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="numero_expediente" class="col-sm-3 col-form-label">Número de Expediente</label>
                                <div class="col-sm-9">
                                    <input type="text" name="numero_expediente" id="numero_expediente" class="form-control @error('numero_expediente') is-invalid @enderror" value="{{ old('numero_expediente', $alumno->numero_expediente) }}">
                                    @error('numero_expediente')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="fecha_nacimiento" class="col-sm-3 col-form-label">Fecha de Nacimiento</label>
                                <div class="col-sm-9">
                                    <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" class="form-control @error('fecha_nacimiento') is-invalid @enderror" value="{{ old('fecha_nacimiento', $alumno->fecha_nacimiento ? date('Y-m-d', strtotime($alumno->fecha_nacimiento)) : '') }}">
                                    @error('fecha_nacimiento')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="clase_grupo_id" class="col-sm-3 col-form-label">Grupo <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <select name="clase_grupo_id" id="clase_grupo_id" class="form-control select2 @error('clase_grupo_id') is-invalid @enderror" required>
                                        <option value="">Seleccione un grupo</option>
                                        @foreach($grupos as $grupo)
                                        <option value="{{ $grupo->id }}" {{ old('clase_grupo_id', $alumno->clase_grupo_id) == $grupo->id ? 'selected' : '' }}>
                                            {{ $grupo->nombre }} ({{ $grupo->curso }}º {{ $grupo->seccion }})
                                        </option>
                                        @endforeach
                                    </select>
                                    @error('clase_grupo_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-sm-9 offset-sm-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Guardar Cambios
                                    </button>
                                    <a href="{{ route('profesor.alumnos.index') }}" class="btn btn-light ml-2">
                                        <i class="fas fa-times"></i> Cancelar
                                    </a>
                                </div>
                            </div>
                        </form>
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
        // Inicializar select2
        $('.select2').select2();
    });
</script>
@endsection 