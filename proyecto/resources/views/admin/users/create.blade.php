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

                    <form method="POST" action="{{ route('admin.users.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="uid" class="form-label">{{ __('Nombre de Usuario') }} <span class="text-danger">*</span></label>
                            <input id="uid" type="text" class="form-control @error('uid') is-invalid @enderror" name="uid" value="{{ old('uid') }}" required autocomplete="uid" autofocus>
                            <div class="form-text">{{ __('El nombre de usuario no podrá cambiarse después de la creación.') }}</div>
                            @error('uid')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="nombre" class="form-label">{{ __('Nombre') }} <span class="text-danger">*</span></label>
                            <input id="nombre" type="text" class="form-control @error('nombre') is-invalid @enderror" name="nombre" value="{{ old('nombre') }}" required>
                            @error('nombre')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="apellidos" class="form-label">{{ __('Apellidos') }} <span class="text-danger">*</span></label>
                            <input id="apellidos" type="text" class="form-control @error('apellidos') is-invalid @enderror" name="apellidos" value="{{ old('apellidos') }}" required>
                            @error('apellidos')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">{{ __('Correo Electrónico') }} <span class="text-danger">*</span></label>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required readonly>
                            <div class="form-text">{{ __('Se genera automáticamente a partir del nombre y apellidos.') }}</div>
                            @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="dn_preview" class="form-label">{{ __('DN (Canonical Name)') }}</label>
                            <input id="dn_preview" type="text" class="form-control bg-light" disabled>
                            <div class="form-text">{{ __('Este será el identificador único del usuario en LDAP.') }}</div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="uidNumber" class="form-label">{{ __('UID Number') }}</label>
                                <input id="uidNumber" type="number" class="form-control bg-light" name="uidNumber" value="{{ old('uidNumber') }}">
                                <div class="form-text">{{ __('Se asignará automáticamente si se deja vacío.') }}</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="gidNumber" class="form-label">{{ __('GID Number') }}</label>
                                <input id="gidNumber" type="number" class="form-control bg-light" name="gidNumber" value="{{ old('gidNumber', '9000') }}">
                                <div class="form-text">{{ __('Grupo principal del usuario (9000 para everybody).') }}</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="homeDirectory" class="form-label">{{ __('Home Directory') }}</label>
                            <input id="homeDirectory" type="text" class="form-control bg-light" name="homeDirectory" value="{{ old('homeDirectory', '/home/') }}" readonly>
                            <div class="form-text">{{ __('Se genera automáticamente a partir del nombre de usuario.') }}</div>
                        </div>

                        <div class="mb-3">
                            <label for="loginShell" class="form-label">{{ __('Shell') }}</label>
                            <input id="loginShell" type="text" class="form-control bg-light" name="loginShell" value="{{ old('loginShell', '/bin/bash') }}">
                            <div class="form-text">{{ __('Shell por defecto del usuario.') }}</div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">{{ __('Contraseña') }} <span class="text-danger">*</span></label>
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required>
                            <div class="form-text">{{ __('Mínimo 8 caracteres.') }}</div>
                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">{{ __('Confirmar Contraseña') }} <span class="text-danger">*</span></label>
                            <input id="password_confirmation" type="password" class="form-control" name="password_confirmation" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">{{ __('Rol Predefinido') }}</label>
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn btn-outline-secondary" id="btn-role-admin">Administrador</button>
                                <button type="button" class="btn btn-outline-secondary" id="btn-role-profesor">Profesor</button>
                                <button type="button" class="btn btn-outline-secondary" id="btn-role-alumno">Alumno</button>
                            </div>
                            <div class="form-text">{{ __('Seleccione un rol para preconfigurar los grupos.') }}</div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">{{ __('Grupos') }} <span class="text-danger">*</span></label>
                            
                            <!-- Debug información de grupos disponibles -->
                            @if(empty($groups) || count($groups) == 0)
                                <div class="alert alert-warning">No hay grupos disponibles</div>
                            @endif
                            
                            <!-- Selector de grupos con estilos mejorados -->
                            <select id="grupos" name="grupos[]" class="form-select form-control" multiple size="6" style="height: auto !important; min-height: 150px !important; width: 100% !important; display: block !important; font-size: 14px !important; padding: 8px !important;">
                                @foreach($groups as $group)
                                    @php
                                        if (is_array($group)) {
                                            $groupName = isset($group['cn'][0]) ? $group['cn'][0] : 'Grupo sin nombre';
                                        } else {
                                            $groupName = $group->getFirstAttribute('cn') ?? 'Grupo sin nombre';
                                        }
                                    @endphp
                                    <option value="{{ $groupName }}" {{ in_array($groupName, old('grupos', [])) ? 'selected' : '' }}
                                        style="padding: 8px !important; margin: 2px !important;"
                                        class="group-option {{ $groupName }}">
                                        {{ $groupName }}
                                    </option>
                                @endforeach
                            </select>
                            
                            <div class="form-text">{{ __('Mantenga presionada la tecla Ctrl (o Command en Mac) para seleccionar múltiples grupos.') }}</div>
                            
                            @error('grupos')
                                <div class="text-danger">
                                    <strong>{{ $message }}</strong>
                                </div>
                            @enderror
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>{{ __('Crear Usuario') }}
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
        const uidInput = document.getElementById('uid');
        const nombreInput = document.getElementById('nombre');
        const apellidosInput = document.getElementById('apellidos');
        const emailInput = document.getElementById('email');
        const dnPreview = document.getElementById('dn_preview');
        const homeDirectory = document.getElementById('homeDirectory');
        const btnRoleAdmin = document.getElementById('btn-role-admin');
        const btnRoleProfesor = document.getElementById('btn-role-profesor');
        const btnRoleAlumno = document.getElementById('btn-role-alumno');
        const gruposSelect = document.getElementById('grupos');

        // Función para actualizar el email basado en nombre y apellidos
        function updateEmail() {
            if (nombreInput.value && apellidosInput.value) {
                const nombre = nombreInput.value.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/\s+/g, "");
                const apellido = apellidosInput.value.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/\s+/g, "");
                emailInput.value = nombre + apellido + '@tierno.es';
            }
        }

        // Función para actualizar el DN y home directory
        function updateDnAndHome() {
            if (uidInput.value) {
                dnPreview.value = `uid=${uidInput.value},ou=people,dc=tierno,dc=es`;
                homeDirectory.value = `/home/${uidInput.value}`;
            } else {
                dnPreview.value = '';
                homeDirectory.value = '/home/';
            }
        }

        // Función para seleccionar grupos según el rol
        function selectGroupsByRole(role) {
            // Primero deseleccionamos todos los grupos
            for (let i = 0; i < gruposSelect.options.length; i++) {
                gruposSelect.options[i].selected = false;
            }

            // Luego seleccionamos los grupos según el rol
            const commonGroups = ['everybody'];
            let roleGroups = [];

            if (role === 'admin') {
                roleGroups = ['ldapadmins', 'docker'];
                btnRoleAdmin.classList.add('active', 'btn-secondary');
                btnRoleAdmin.classList.remove('btn-outline-secondary');
                btnRoleProfesor.classList.remove('active', 'btn-secondary');
                btnRoleProfesor.classList.add('btn-outline-secondary');
                btnRoleAlumno.classList.remove('active', 'btn-secondary');
                btnRoleAlumno.classList.add('btn-outline-secondary');
            } else if (role === 'profesor') {
                roleGroups = ['profesores', 'docker'];
                btnRoleProfesor.classList.add('active', 'btn-secondary');
                btnRoleProfesor.classList.remove('btn-outline-secondary');
                btnRoleAdmin.classList.remove('active', 'btn-secondary');
                btnRoleAdmin.classList.add('btn-outline-secondary');
                btnRoleAlumno.classList.remove('active', 'btn-secondary');
                btnRoleAlumno.classList.add('btn-outline-secondary');
            } else if (role === 'alumno') {
                roleGroups = ['alumnos'];
                btnRoleAlumno.classList.add('active', 'btn-secondary');
                btnRoleAlumno.classList.remove('btn-outline-secondary');
                btnRoleAdmin.classList.remove('active', 'btn-secondary');
                btnRoleAdmin.classList.add('btn-outline-secondary');
                btnRoleProfesor.classList.remove('active', 'btn-secondary');
                btnRoleProfesor.classList.add('btn-outline-secondary');
            }

            const allGroups = [...commonGroups, ...roleGroups];

            // Seleccionar los grupos correspondientes
            for (let i = 0; i < gruposSelect.options.length; i++) {
                const option = gruposSelect.options[i];
                if (allGroups.includes(option.value)) {
                    option.selected = true;
                }
            }
        }

        // Eventos para actualizar el email
        nombreInput.addEventListener('input', updateEmail);
        apellidosInput.addEventListener('input', updateEmail);

        // Evento para actualizar el DN y home directory
        uidInput.addEventListener('input', updateDnAndHome);

        // Eventos para los botones de rol
        btnRoleAdmin.addEventListener('click', () => selectGroupsByRole('admin'));
        btnRoleProfesor.addEventListener('click', () => selectGroupsByRole('profesor'));
        btnRoleAlumno.addEventListener('click', () => selectGroupsByRole('alumno'));

        // Inicializar valores
        updateDnAndHome();
        updateEmail();
    });
</script>
@endpush
@endsection 