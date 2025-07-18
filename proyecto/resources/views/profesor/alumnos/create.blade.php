@extends('layouts.dashboard')

@section('title', 'Nuevo Alumno')

@section('content')
<section class="section">
    <div class="section-header">
        <h1><i class="fas fa-user-plus"></i> Nuevo Alumno</h1>
        <div class="section-header-breadcrumb">
            <div class="breadcrumb-item active"><a href="{{ route('dashboard.index') }}">Dashboard</a></div>
            <div class="breadcrumb-item"><a href="{{ route('profesor.alumnos.index') }}">Alumnos</a></div>
            <div class="breadcrumb-item">Nuevo</div>
        </div>
    </div>

    <div class="section-body">
        <h2 class="section-title">Registro de Alumno</h2>
        <p class="section-lead">Complete el formulario para registrar un nuevo alumno.</p>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4>Formulario de Registro</h4>
                    </div>
                    <form action="{{ route('profesor.alumnos.store') }}" method="POST">
                        @csrf
                        <div class="card-body text-white">
                            @include('partials.session_messages')

                            <div class="form-group row text-white">
                                <label for="nombre" class="col-sm-3 col-form-label">Nombre <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <input type="text" name="nombre" id="nombre" class="form-control @error('nombre') is-invalid @enderror" value="{{ old('nombre') }}" required autofocus>
                                    @error('nombre')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row text-white">
                                <label for="apellidos" class="col-sm-3 col-form-label">Apellidos <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <input type="text" name="apellidos" id="apellidos" class="form-control @error('apellidos') is-invalid @enderror" value="{{ old('apellidos') }}" required>
                                    @error('apellidos')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row text-white">
                                <label for="email" class="col-sm-3 col-form-label">Email</label>
                                <div class="col-sm-9">
                                    <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}">
                                    @error('email')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Este email será utilizado para crear la cuenta LDAP si lo solicita.</small>
                                </div>
                            </div>

                            <div class="form-group row text-white">
                                <label for="password" class="col-sm-3 col-form-label">Contraseña <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <div class="input-group">
                                        <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" required>
                                        <button type="button" class="btn btn-outline-secondary" id="toggle-password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-secondary" id="generate-password">
                                            <i class="fas fa-key"></i> Generar
                                        </button>
                                    </div>
                                    @error('password')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Mínimo 8 caracteres. La contraseña se usará para la cuenta LDAP.</small>
                                </div>
                            </div>

                            <div class="form-group row text-white">
                                <label for="password_confirmation" class="col-sm-3 col-form-label">Confirmar Contraseña <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <div class="input-group">
                                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" required>
                                        <button type="button" class="btn btn-outline-secondary" id="toggle-password-confirmation">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group row text-white">
                                <label for="dni" class="col-sm-3 col-form-label">DNI / Documento</label>
                                <div class="col-sm-9">
                                    <input type="text" name="dni" id="dni" class="form-control @error('dni') is-invalid @enderror" value="{{ old('dni') }}">
                                    @error('dni')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row text-white">
                                <label for="numero_expediente" class="col-sm-3 col-form-label">Nº Expediente</label>
                                <div class="col-sm-9">
                                    <input type="text" name="numero_expediente" id="numero_expediente" class="form-control @error('numero_expediente') is-invalid @enderror" value="{{ old('numero_expediente') }}">
                                    @error('numero_expediente')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row text-white">
                                <label for="fecha_nacimiento" class="col-sm-3 col-form-label">Fecha de Nacimiento</label>
                                <div class="col-sm-9">
                                    <input type="date" name="fecha_nacimiento" id="fecha_nacimiento" class="form-control @error('fecha_nacimiento') is-invalid @enderror" value="{{ old('fecha_nacimiento') }}">
                                    @error('fecha_nacimiento')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row text-white">
                                <label for="clase_grupo_id" class="col-sm-3 col-form-label">Grupo de Clase <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <select name="clase_grupo_id" id="clase_grupo_id" class="form-control @error('clase_grupo_id') is-invalid @enderror" required>
                                        <option value="">Seleccione un grupo</option>
                                        @foreach($grupos as $grupo)
                                            <option value="{{ $grupo->id }}" {{ old('clase_grupo_id') == $grupo->id || request('clase_grupo_id') == $grupo->id ? 'selected' : '' }}>
                                                {{ $grupo->nombre }} ({{ $grupo->nivel }} {{ $grupo->curso }}º {{ $grupo->seccion }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('clase_grupo_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row text-white">
                                <div class="col-sm-3">Opciones</div>
                                <div class="col-sm-9">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" name="crear_cuenta_ldap" value="1" class="custom-control-input" id="crear_cuenta_ldap" {{ old('crear_cuenta_ldap') ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="crear_cuenta_ldap">Crear cuenta LDAP automáticamente</label>
                                        @error('crear_cuenta_ldap')
                                            <div class="text-danger mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer text-right">
                            <a href="{{ route('profesor.alumnos.index') }}" class="btn btn-secondary mr-2">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Alumno
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('scripts')
<script>
document.getElementById('generate-password').addEventListener('click', function() {
    // Generar contraseña aleatoria de 12 caracteres
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789!@#$%^&*';
    let password = '';
    for (let i = 0; i < 12; i++) {
        password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    
    // Actualizar campos de contraseña
    document.getElementById('password').value = password;
    document.getElementById('password_confirmation').value = password;
});

// Función para alternar la visibilidad de la contraseña
function togglePasswordVisibility(inputId, buttonId) {
    const input = document.getElementById(inputId);
    const button = document.getElementById(buttonId);
    const icon = button.querySelector('i');
    
    button.addEventListener('click', function() {
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
}

// Inicializar los botones de visibilidad
togglePasswordVisibility('password', 'toggle-password');
togglePasswordVisibility('password_confirmation', 'toggle-password-confirmation');
</script>
@endsection 