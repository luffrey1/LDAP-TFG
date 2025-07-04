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

                        <div class="mb-3 text-white">
                            <label class="form-label">{{ __('Tipo de Usuario') }}</label>
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn btn-outline-secondary" id="btn-role-profesor">Profesor</button>
                                <button type="button" class="btn btn-outline-secondary active" id="btn-role-alumno">Alumno</button>
                            </div>
                        </div>

                        <div class="mb-3 text-white">
                            <label for="nombre" class="form-label">{{ __('Nombre') }} <span class="text-danger">*</span></label>
                            <input id="nombre" type="text" class="form-control @error('nombre') is-invalid @enderror" name="nombre" value="{{ old('nombre') }}" required>
                            @error('nombre')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3 text-white">
                            <label for="apellidos" class="form-label">{{ __('Apellidos') }} <span class="text-danger">*</span></label>
                            <input id="apellidos" type="text" class="form-control @error('apellidos') is-invalid @enderror" name="apellidos" value="{{ old('apellidos') }}" required>
                            @error('apellidos')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3 text-white">
                            <label for="uid" class="form-label">{{ __('Nombre de Usuario') }} <span class="text-danger">*</span></label>
                            <input id="uid" type="text" class="form-control @error('uid') is-invalid @enderror" name="uid" value="{{ old('uid') }}" required autocomplete="uid" placeholder="Automático">
                            @error('uid')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-2 text-white">
                            <small class="form-text">{{ __('DN:') }} <span id="dn_preview_text">uid=,ou=people,dc=tierno,dc=es</span></small>
                        </div>

                        <div class="mb-3 text-white">
                            <label for="email" class="form-label">{{ __('Correo Electrónico') }} <span class="text-danger">*</span></label>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required>
                            @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3 text-white">
                                <label for="uidNumber" class="form-label">{{ __('UID Number') }}</label>
                                <input id="uidNumber" type="number" class="form-control" name="uidNumber" value="{{ old('uidNumber') }}" placeholder="Automático">
                                <div class="form-text">{{ __('Se asignará automáticamente si se deja vacío.') }}</div>
                            </div>

                            <div class="col-md-6 mb-3 text-white">
                                <label for="gidNumber" class="form-label">{{ __('GID Number') }}</label>
                                <input id="gidNumber" type="number" class="form-control" name="gidNumber" value="{{ old('gidNumber') }}" placeholder="Ej: 10000 para ldapadmins">
                                <div class="form-text">{{ __('Se asignará automáticamente según el grupo seleccionado.') }}</div>
                            </div>
                        </div>

                        <div class="mb-3 text-white">
                            <label for="homeDirectory" class="form-label">{{ __('Home Directory') }}</label>
                            <input id="homeDirectory" type="text" class="form-control" name="homeDirectory" value="{{ old('homeDirectory', '/home/') }}">
                            <div class="form-text">{{ __('Se genera automáticamente a partir del nombre de usuario.') }}</div>
                        </div>

                        <div class="mb-3 text-white">
                            <label for="loginShell" class="form-label">{{ __('Shell') }}</label>
                            <input id="loginShell" type="text" class="form-control" name="loginShell" value="{{ old('loginShell', '/bin/bash') }}">
                            <div class="form-text">{{ __('Shell por defecto del usuario.') }}</div>
                        </div>

                        <div class="mb-3 text-white">
                            <label for="password" class="form-label">{{ __('Contraseña') }} <span class="text-danger">*</span></label>
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required placeholder="Mínimo 8 caracteres" minlength="8">
                            <div class="form-text">La contraseña debe tener al menos 8 caracteres.</div>
                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3 text-white">
                            <label for="password_confirmation" class="form-label">{{ __('Confirmar Contraseña') }} <span class="text-danger">*</span></label>
                            <input id="password_confirmation" type="password" class="form-control" name="password_confirmation" required>
                        </div>

                        <div class="mb-4 text-white">
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
                                    @if($groupName != 'ldapadmins' || session('auth_user.is_admin') || session('auth_user.username') === 'ldap-admin')
                                    <option value="{{ $groupName }}" {{ in_array($groupName, old('grupos', [])) ? 'selected' : '' }}
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
        const dnPreviewText = document.getElementById('dn_preview_text');
        const homeDirectory = document.getElementById('homeDirectory');
        const gidNumberInput = document.getElementById('gidNumber');
        const gruposSelect = document.getElementById('grupos');
        const btnRoleProfesor = document.getElementById('btn-role-profesor');
        const btnRoleAlumno = document.getElementById('btn-role-alumno');

        // Función para normalizar texto (eliminar acentos y espacios)
        function normalizeText(text) {
            return text.toLowerCase()
                      .normalize("NFD")
                      .replace(/[\u0300-\u036f]/g, "")
                      .replace(/\s+/g, "");
        }

        // Función para generar el nombre de usuario
        function generateUsername(nombre, apellidos) {
            if (!nombre || !apellidos) return '';
            const nombreNorm = normalizeText(nombre);
            const apellidosNorm = normalizeText(apellidos);
            return nombreNorm + apellidosNorm.charAt(0);
        }

        // Función para actualizar todos los campos relacionados
        function updateAllFields() {
            const nombre = nombreInput.value;
            const apellidos = apellidosInput.value;
            
            if (nombre && apellidos) {
                const username = generateUsername(nombre, apellidos);
                
                // Actualizar UID
                uidInput.value = username;
                
                // Actualizar email
                emailInput.value = username + '@tierno.es';
                
                // Actualizar DN
                dnPreviewText.textContent = 'uid=' + username + ',ou=people,dc=tierno,dc=es';
                
                // Actualizar home directory
                homeDirectory.value = '/home/' + username;
            } else {
                // Si falta nombre o apellidos, limpiar los campos
                uidInput.value = '';
                emailInput.value = '';
                dnPreviewText.textContent = 'uid=,ou=people,dc=tierno,dc=es';
                homeDirectory.value = '/home/';
            }
        }

        // Función para verificar si el usuario ya existe
        async function checkUserExists(username) {
            if (!username) return false;
            
            try {
                const response = await fetch(`/api/ldap/users/check/${encodeURIComponent(username)}`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const data = await response.json();
                return data.exists;
            } catch (error) {
                console.error('Error al verificar usuario:', error);
                return false;
            }
        }

        // Función para actualizar con verificación
        async function updateWithVerification() {
            const username = generateUsername(nombreInput.value, apellidosInput.value);
            
            if (username) {
                const exists = await checkUserExists(username);
                if (exists) {
                    // Mostrar advertencia si el usuario ya existe
                    const warningDiv = document.createElement('div');
                    warningDiv.className = 'alert alert-warning mt-2';
                    warningDiv.textContent = 'Este nombre de usuario ya existe. Por favor, modifica el nombre o apellidos.';
                    
                    // Eliminar advertencia anterior si existe
                    const oldWarning = document.querySelector('.alert-warning');
                    if (oldWarning) oldWarning.remove();
                    
                    // Insertar nueva advertencia
                    uidInput.parentNode.appendChild(warningDiv);
                    
                    // Deshabilitar el botón de envío
                    document.querySelector('button[type="submit"]').disabled = true;
                } else {
                    // Eliminar advertencia si existe
                    const warning = document.querySelector('.alert-warning');
                    if (warning) warning.remove();
                    
                    // Habilitar el botón de envío
                    document.querySelector('button[type="submit"]').disabled = false;
                }
            }
            
            updateAllFields();
        }

        // Eventos para actualización en tiempo real
        let updateTimeout;
        function debouncedUpdate() {
            clearTimeout(updateTimeout);
            updateTimeout = setTimeout(updateWithVerification, 300);
        }

        nombreInput.addEventListener('input', debouncedUpdate);
        apellidosInput.addEventListener('input', debouncedUpdate);

        // Evento para actualizar DN cuando cambia el username manualmente
        uidInput.addEventListener('input', function() {
            if (this.value) {
                dnPreviewText.textContent = 'uid=' + this.value + ',ou=people,dc=tierno,dc=es';
                homeDirectory.value = '/home/' + this.value;
                emailInput.value = this.value + '@tierno.es';
            }
        });

        // Función para buscar y seleccionar el grupo por GID
        async function findGroupByGid(gid) {
            if (!gid) return;
            
            try {
                const response = await fetch(`/api/ldap/groups/gid/${gid}`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const data = await response.json();
                
                if (data.success && data.group) {
                    // Deseleccionar todos los grupos primero
                    for (let i = 0; i < gruposSelect.options.length; i++) {
                        gruposSelect.options[i].selected = false;
                    }
                    
                    // Seleccionar el grupo encontrado
                    const options = gruposSelect.options;
                    let found = false;
                    for (let i = 0; i < options.length; i++) {
                        if (options[i].value === data.group) {
                            options[i].selected = true;
                            found = true;
                            break;
                        }
                    }
                    
                    if (!found) {
                        console.warn(`Grupo ${data.group} encontrado pero no está en la lista de opciones`);
                    }
                } else {
                    alert(data.message || 'El GID especificado no existe en ningún grupo');
                    gidNumberInput.value = '';
                }
            } catch (error) {
                console.error('Error al buscar grupo por GID:', error);
                alert('Error al buscar el grupo por GID. Por favor, inténtalo de nuevo.');
                gidNumberInput.value = '';
            }
        }

        // Función para buscar el GID de un grupo
        async function findGidByGroup(groupName) {
            if (!groupName) return;
            
            try {
                const response = await fetch(`/api/ldap/groups/${groupName}/gid`);
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                const data = await response.json();
                
                if (data.success && data.gidNumber) {
                    gidNumberInput.value = data.gidNumber;
                } else {
                    console.warn(`No se encontró GID para el grupo ${groupName}`);
                }
            } catch (error) {
                console.error('Error al buscar GID por grupo:', error);
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
                // Actualizar GID para profesores
                fetch('/api/ldap/groups/profesores/gid')
                    .then(response => response.json())
                    .then(data => {
                        gidNumberInput.value = data.gidNumber;
                    });
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
                // Actualizar GID para alumnos
                fetch('/api/ldap/groups/alumnos/gid')
                    .then(response => response.json())
                    .then(data => {
                        gidNumberInput.value = data.gidNumber;
                    });
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

        // Evento para buscar y seleccionar grupo por GID
        gidNumberInput.addEventListener('change', function() {
            findGroupByGid(this.value);
        });

        // Evento para actualizar GID cuando cambia la selección de grupos
        gruposSelect.addEventListener('change', function() {
            // Si solo hay un grupo seleccionado, buscar su GID
            const selectedOptions = Array.from(this.selectedOptions);
            if (selectedOptions.length === 1) {
                findGidByGroup(selectedOptions[0].value);
            }
        });

        // Inicializar
        updateAllFields();
    });
</script>
@endpush
@endsection 