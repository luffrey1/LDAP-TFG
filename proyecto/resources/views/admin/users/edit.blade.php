@extends('layouts.dashboard')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>{{ __('Editar Usuario LDAP') }}</span>
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

                    <form method="POST" action="{{ route('admin.users.update', $encoded_dn) }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3 text-white">
                            <label class="form-label">{{ __('Tipo de Usuario') }}</label>
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn btn-outline-secondary" id="btn-role-profesor">Profesor</button>
                                <button type="button" class="btn btn-outline-secondary" id="btn-role-alumno">Alumno</button>
                            </div>
                        </div>

                        <div class="mb-3 text-white">
                            <label for="uid" class="form-label">{{ __('Nombre de Usuario') }}</label>
                            <input id="uid" type="text" class="form-control" name="uid" value="{{ is_array($user) ? ($user['uid'][0] ?? '') : $user->getFirstAttribute('uid') }}">
                            <div class="form-text">{{ __('Nombre de usuario del sistema.') }}</div>
                        </div>

                        <div class="mb-2 text-white">
                            <small class="form-text">{{ __('DN:') }} <span id="dn_preview_text">{{ base64_decode($encoded_dn) }}</span></small>
                        </div>

                        <div class="mb-3 text-white">
                            <label for="nombre" class="form-label">{{ __('Nombre') }} <span class="text-danger">*</span></label>
                            <input id="nombre" type="text" class="form-control @error('nombre') is-invalid @enderror" name="nombre" value="{{ old('nombre', is_array($user) ? ($user['givenname'][0] ?? '') : $user->getFirstAttribute('givenname')) }}" required>
                            @error('nombre')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3 text-white">
                            <label for="apellidos" class="form-label">{{ __('Apellidos') }} <span class="text-danger">*</span></label>
                            <input id="apellidos" type="text" class="form-control @error('apellidos') is-invalid @enderror" name="apellidos" value="{{ old('apellidos', is_array($user) ? ($user['sn'][0] ?? '') : $user->getFirstAttribute('sn')) }}" required>
                            @error('apellidos')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3 text-white">
                            <label for="email" class="form-label">{{ __('Correo Electrónico') }} <span class="text-danger">*</span></label>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', is_array($user) ? ($user['mail'][0] ?? '') : $user->getFirstAttribute('mail')) }}" required>
                            <div class="form-text">{{ __('Correo electrónico del usuario.') }}</div>
                            @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                            <div class="row">
                            <div class="col-md-6 mb-3 text-white">
                                <label for="uidNumber" class="form-label">{{ __('UID Number') }}</label>
                                <input id="uidNumber" type="number" class="form-control" name="uidNumber" value="{{ old('uidNumber', is_array($user) ? ($user['uidnumber'][0] ?? '') : $user->getFirstAttribute('uidnumber')) }}">
                                <div class="form-text">{{ __('Identificador numérico del usuario.') }}</div>
                            </div>

                            <div class="col-md-6 mb-3 text-white">
                                <label for="gidNumber" class="form-label">{{ __('GID Number') }}</label>
                                <input id="gidNumber" type="number" class="form-control" name="gidNumber" value="{{ old('gidNumber', is_array($user) ? ($user['gidnumber'][0] ?? '') : $user->getFirstAttribute('gidnumber')) }}">
                                <div class="form-text">{{ __('Grupo principal del usuario.') }}</div>
                            </div>
                        </div>

                        <div class="mb-3 text-white">
                            <label for="homeDirectory" class="form-label">{{ __('Home Directory') }}</label>
                            <input id="homeDirectory" type="text" class="form-control" name="homeDirectory" value="{{ old('homeDirectory', is_array($user) ? ($user['homedirectory'][0] ?? '') : $user->getFirstAttribute('homedirectory')) }}" readonly>
                            <div class="form-text">{{ __('Directorio home del usuario.') }}</div>
                        </div>

                        <div class="mb-3 text-white">
                            <label for="loginShell" class="form-label">{{ __('Shell') }}</label>
                            <input id="loginShell" type="text" class="form-control" name="loginShell" value="{{ old('loginShell', is_array($user) ? ($user['loginshell'][0] ?? '/bin/bash') : ($user->getFirstAttribute('loginshell') ?? '/bin/bash')) }}">
                            <div class="form-text">{{ __('Shell por defecto del usuario.') }}</div>
                        </div>

                        <div class="mb-3 text-white">
                            <label for="password" class="form-label">{{ __('Contraseña') }}</label>
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password">
                            <div class="form-text">{{ __('Dejar en blanco para mantener la contraseña actual. Mínimo 8 caracteres.') }}</div>
                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3 text-white">
                            <label for="password_confirmation" class="form-label">{{ __('Confirmar Contraseña') }}</label>
                            <input id="password_confirmation" type="password" class="form-control" name="password_confirmation">
                        </div>

                        <div class="mb-4 text-white">
                            <label class="form-label">{{ __('Grupos Activos') }}</label>
                            <div class="bg-light p-2 mb-2 border rounded">
                                @php
                                    $userGroupNames = [];
                                    if (!empty($userGroups)) {
                                        foreach($userGroups as $userGroup) {
                                            if(is_array($userGroup)) {
                                                if(isset($userGroup['cn'][0])) {
                                                    $userGroupNames[] = $userGroup['cn'][0];
                                                }
                                            } elseif (is_object($userGroup)) {
                                                $userGroupNames[] = $userGroup->getFirstAttribute('cn');
                                            }
                                        }
                                    }
                                @endphp
                                
                                @if(count($userGroupNames) > 0)
                                    @foreach($userGroupNames as $groupName)
                                        <span class="badge bg-secondary me-1">{{ $groupName }}</span>
                                    @endforeach
                                @else
                                    <span class="text-muted">No pertenece a ningún grupo</span>
                                @endif
                            </div>
                        </div>

                        <div class="mb-4 text-white">
                            <label class="form-label">{{ __('Grupos') }} <span class="text-danger">*</span></label>
                            
                            <!-- Debug información de grupos disponibles -->
                            @if(empty($allGroups) || count($allGroups) == 0)
                                <div class="alert alert-warning">No hay grupos disponibles</div>
                            @endif

                            <!-- Selector de grupos con estilos mejorados -->
                            <select id="grupos" name="grupos[]" class="form-select form-control" multiple size="6" style="height: auto !important; min-height: 150px !important; width: 100% !important; display: block !important; font-size: 14px !important; padding: 8px !important;">
                                @php
                                    $userGroupNames = [];
                                    if (!empty($userGroups)) {
                                        foreach($userGroups as $userGroup) {
                                            if(is_array($userGroup)) {
                                                if(isset($userGroup['cn'][0])) {
                                                    $userGroupNames[] = $userGroup['cn'][0];
                                                }
                                            } elseif (is_object($userGroup)) {
                                                $userGroupNames[] = $userGroup->getFirstAttribute('cn');
                                            }
                                        }
                                    }
                                @endphp
                                
                                @foreach($allGroups as $group)
                                    @php
                                        if (is_array($group)) {
                                            $groupName = isset($group['cn'][0]) ? $group['cn'][0] : 'Grupo sin nombre';
                                        } else {
                                            $groupName = $group->getFirstAttribute('cn') ?? 'Grupo sin nombre';
                                        }
                                        $selected = in_array($groupName, $userGroupNames);
                                    @endphp
                                    @if($groupName != 'ldapadmins' || session('auth_user.is_admin') || session('auth_user.username') === 'ldap-admin')
                                    <option value="{{ $groupName }}" {{ $selected ? 'selected' : '' }} 
                                        style="padding: 8px !important; margin: 2px !important;"
                                        class="group-option {{ $groupName }}">
                                        {{ $groupName }}
                                    </option>
                                    @endif
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
                                <i class="fas fa-save me-2"></i>{{ __('Actualizar Usuario') }}
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
        const nombreInput = document.getElementById('nombre');
        const apellidosInput = document.getElementById('apellidos');
        const emailInput = document.getElementById('email');
        const uidInput = document.getElementById('uid');
        const dnPreviewText = document.getElementById('dn_preview_text');
        const btnRoleProfesor = document.getElementById('btn-role-profesor');
        const btnRoleAlumno = document.getElementById('btn-role-alumno');
        const gruposSelect = document.getElementById('grupos');

        // Función para actualizar el DN cuando cambia el username
        function updateDn() {
            if (uidInput.value) {
                dnPreviewText.textContent = `uid=${uidInput.value},ou=people,dc=tierno,dc=es`;
            } else {
                dnPreviewText.textContent = 'uid=,ou=people,dc=tierno,dc=es';
            }
        }

        // Función para actualizar el email basado en nombre y apellidos
        function updateEmail() {
            if (nombreInput.value && apellidosInput.value) {
                const nombre = nombreInput.value.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/\s+/g, "");
                const apellido = apellidosInput.value.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "").replace(/\s+/g, "");
                emailInput.value = nombre + apellido + '@tierno.es';
            }
        }

        // Evento para actualizar DN cuando cambia el username
        uidInput.addEventListener('input', updateDn);

        // Eventos para actualizar el email
        nombreInput.addEventListener('input', updateEmail);
        apellidosInput.addEventListener('input', updateEmail);

        // Función para seleccionar grupos según el rol
        function selectGroupsByRole(role) {
            // Primero deseleccionamos todos los grupos
            for (let i = 0; i < gruposSelect.options.length; i++) {
                gruposSelect.options[i].selected = false;
            }

            // Luego seleccionamos los grupos según el rol
            const commonGroups = ['everybody'];
            let roleGroups = [];

            if (role === 'profesor') {
                roleGroups = ['profesores', 'docker'];
                if (btnRoleProfesor) {
                    btnRoleProfesor.classList.add('active', 'btn-secondary');
                    btnRoleProfesor.classList.remove('btn-outline-secondary');
                }
                if (btnRoleAlumno) {
                    btnRoleAlumno.classList.remove('active', 'btn-secondary');
                    btnRoleAlumno.classList.add('btn-outline-secondary');
                }
            } else if (role === 'alumno') {
                roleGroups = ['alumnos'];
                if (btnRoleAlumno) {
                    btnRoleAlumno.classList.add('active', 'btn-secondary');
                    btnRoleAlumno.classList.remove('btn-outline-secondary');
                }
                if (btnRoleProfesor) {
                    btnRoleProfesor.classList.remove('active', 'btn-secondary');
                    btnRoleProfesor.classList.add('btn-outline-secondary');
                }
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

        // Eventos para los botones de rol
        if (btnRoleProfesor) {
            btnRoleProfesor.addEventListener('click', () => selectGroupsByRole('profesor'));
        }
        if (btnRoleAlumno) {
            btnRoleAlumno.addEventListener('click', () => selectGroupsByRole('alumno'));
        }

        // Marcar el botón de rol activo según los grupos actuales
        function checkActiveRole() {
            const selectedGroups = Array.from(gruposSelect.selectedOptions).map(option => option.value);
            
            if (selectedGroups.includes('profesores')) {
                btnRoleProfesor.classList.add('active', 'btn-secondary');
                btnRoleProfesor.classList.remove('btn-outline-secondary');
            } else if (selectedGroups.includes('alumnos')) {
                btnRoleAlumno.classList.add('active', 'btn-secondary');
                btnRoleAlumno.classList.remove('btn-outline-secondary');
            }
        }

        // Inicializar
        checkActiveRole();
    });
</script>
@endpush
@endsection 