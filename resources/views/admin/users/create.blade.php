@extends('layouts.dashboard')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>{{ __('Crear Nuevo Usuario LDAP') }}</span>
                        <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-light">
                            <i class="fas fa-arrow-left"></i> {{ __('Volver') }}
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('status') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.users.store') }}" id="createUserForm">
                        @csrf

                        <!-- Tipo de Usuario -->
                        <div class="mb-3">
                            <label class="form-label">Tipo de Usuario <span class="text-danger">*</span></label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="user_type" id="user_type_alumno" value="alumno" checked>
                                <label class="form-check-label" for="user_type_alumno">
                                    Alumno
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="user_type" id="user_type_profesor" value="profesor">
                                <label class="form-check-label" for="user_type_profesor">
                                    Profesor
                                </label>
                            </div>
                        </div>

                        <!-- Nombre -->
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('nombre') is-invalid @enderror" 
                                id="nombre" name="nombre" value="{{ old('nombre') }}" required>
                            @error('nombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Apellido -->
                        <div class="mb-3">
                            <label for="apellido" class="form-label">Apellido <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('apellido') is-invalid @enderror" 
                                id="apellido" name="apellido" value="{{ old('apellido') }}" required>
                            @error('apellido')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Username -->
                        <div class="mb-3">
                            <label for="username" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('username') is-invalid @enderror" 
                                id="username" name="username" value="{{ old('username') }}" required>
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- DN (solo lectura) -->
                        <div class="mb-3">
                            <label class="form-label">DN</label>
                            <input type="text" class="form-control" id="dn" readonly>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" 
                                id="email" name="email" value="{{ old('email') }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- UID -->
                        <div class="mb-3">
                            <label for="uid" class="form-label">UID</label>
                            <input type="text" class="form-control @error('uid') is-invalid @enderror" 
                                id="uid" name="uid" placeholder="Automático" readonly>
                            @error('uid')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- GID (oculto) -->
                        <input type="hidden" id="gid" name="gid">

                         <!-- Password -->
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña <span class="text-danger">*</span></label>
                            <input type="password" class="form-control @error('password') is-invalid @enderror" 
                                id="password" name="password" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Password Confirmation -->
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirmar Contraseña <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" 
                                id="password_confirmation" name="password_confirmation" required>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Crear Usuario
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('createUserForm');
    const nombreInput = document.getElementById('nombre');
    const apellidoInput = document.getElementById('apellido');
    const usernameInput = document.getElementById('username');
    const emailInput = document.getElementById('email');
    const dnInput = document.getElementById('dn');
    const userTypeInputs = document.querySelectorAll('input[name="user_type"]');
    const gidInput = document.getElementById('gid');

    // Función para generar el username
    function generateUsername() {
        const nombre = nombreInput.value.trim().toLowerCase();
        const apellido = apellidoInput.value.trim().toLowerCase();
        if (nombre && apellido) {
            // Tomar primera letra del nombre y el apellido completo, eliminar espacios
            const username = nombre.charAt(0) + apellido.replace(/\s+/g, '');
            usernameInput.value = username;
            updateDN();
            generateEmail();
        }
    }

    // Función para generar el email
    function generateEmail() {
        const username = usernameInput.value.trim();
        if (username) {
            emailInput.value = username + '@test.tierno.es';
        }
    }

    // Función para actualizar el DN
    function updateDN() {
        const username = usernameInput.value.trim();
        if (username) {
            dnInput.value = `uid=${username},ou=people,dc=test,dc=tierno,dc=es`;
        } else {
            dnInput.value = '';
        }
    }

    // Función para actualizar el GID según el tipo de usuario
    function updateGID() {
        const userType = document.querySelector('input[name="user_type"]:checked').value;
        // Aquí deberías hacer una consulta LDAP para obtener el GID real
        // Por ahora, usamos valores fijos como placeholder
        gidInput.value = userType === 'profesor' ? '1000' : '500'; // Ejemplo: 1000 para profesores, 500 para alumnos
    }

    // Eventos para autocompletar campos
    nombreInput.addEventListener('input', generateUsername);
    apellidoInput.addEventListener('input', generateUsername);
    usernameInput.addEventListener('input', function() {
        updateDN();
        generateEmail();
    });

    // Evento para cambiar el tipo de usuario
    userTypeInputs.forEach(input => {
        input.addEventListener('change', updateGID);
    });

    // Inicializar valores al cargar la página si hay datos viejos
    if (old('username')) {
        updateDN();
        generateEmail();
    }
    updateGID();

});
</script>
@endpush
@endsection 