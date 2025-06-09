@extends('layouts.dashboard')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-user-edit me-2"></i>{{ __('Editar Perfil') }}</span>
                    </div>
                </div>

                <div class="card-body text-white">
                    @if (session('status'))
                        <div class="alert alert-success alert-dismissible fade show text-white" role="alert" style="background:#198754; border:none;">
                            {{ session('status') }}
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show text-white" role="alert" style="background:#dc3545; border:none;">
                            {{ session('error') }}
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-4">
                            <label for="uid" class="block text-sm font-medium text-white">UID</label>
                            <input type="text" name="uid" id="uid" value="{{ $ldapUid }}" class="mt-1 block w-full rounded-md border-gray-300 bg-gray-700 text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" readonly>
                            <p class="mt-1 text-sm text-gray-400">El UID no se puede modificar</p>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-white">{{ __('DN Completo') }}</label>
                            <input type="text" class="form-control text-white bg-dark border-secondary" value="{{ $ldapUser['dn'] ?? '' }}" readonly>
                            <div class="form-text text-white-50">{{ __('Distinguished Name (DN) del usuario en LDAP.') }}</div>
                        </div>

                        <div class="mb-3">
                            <label for="gid" class="form-label text-white">{{ __('GID (Número)') }}</label>
                            <input id="gid" type="text" class="form-control text-white bg-dark border-secondary" name="gid" value="{{ $ldapGuid }}" readonly>
                            <div class="form-text text-white-50">{{ __('El GID numérico no puede ser modificado.') }}</div>
                        </div>

                        <div class="mb-3">
                            <label for="cn" class="form-label text-white">{{ __('Nombre de Usuario') }}</label>
                            <input id="cn" type="text" class="form-control text-white bg-dark border-secondary" name="cn" value="{{ $ldapCn }}" readonly>
                            <div class="form-text text-white-50">{{ __('El nombre de usuario no puede ser modificado.') }}</div>
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label text-white">{{ __('Nombre Completo') }}</label>
                            <input id="name" type="text" class="form-control text-white bg-dark border-secondary @error('name') is-invalid @enderror" name="name" value="{{ old('name', $fullName) }}" required>
                            @error('name')
                                <span class="invalid-feedback d-block text-white" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label text-white">{{ __('Correo Electrónico') }}</label>
                            <input id="email" type="email" class="form-control text-white bg-dark border-secondary @error('email') is-invalid @enderror" name="email" value="{{ old('email', $user->email) }}" required>
                            @error('email')
                                <span class="invalid-feedback d-block text-white" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="current_password" class="form-label text-white">{{ __('Contraseña Actual') }}</label>
                            <input id="current_password" type="password" class="form-control text-white bg-dark border-secondary @error('current_password') is-invalid @enderror" name="current_password">
                            @error('current_password')
                                <span class="invalid-feedback d-block text-white" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label text-white">{{ __('Nueva Contraseña') }}</label>
                            <input id="new_password" type="password" class="form-control text-white bg-dark border-secondary @error('new_password') is-invalid @enderror" name="new_password" placeholder="Mínimo 8 caracteres" minlength="8">
                            <div class="form-text text-white-50">Mínimo 8 caracteres.</div>
                            @error('new_password')
                                <span class="invalid-feedback d-block text-white" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="new_password_confirmation" class="form-label text-white">{{ __('Confirmar Nueva Contraseña') }}</label>
                            <input id="new_password_confirmation" type="password" class="form-control text-white bg-dark border-secondary" name="new_password_confirmation">
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-white">{{ __('Grupos LDAP') }}</label>
                            <div class="grupos-container">
                                @foreach($groups as $group)
                                    <div class="grupo-item text-white">
                                        <i class="fas fa-users me-2"></i>
                                        <span>{{ $group['nombre_completo'] ?? $group['cn'] ?? $group }}</span>
                                        @if(isset($group['description']) && $group['description'])
                                            <small class="text-white-50 ms-2">({{ $group['description'] }})</small>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                            <div class="form-text text-white-50">{{ __('Los grupos no pueden ser modificados desde el perfil.') }}</div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>{{ __('Actualizar Perfil') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.grupos-container {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 0.5rem;
    padding: 1rem;
    max-height: 200px;
    overflow-y: auto;
}

.grupo-item {
    padding: 0.5rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    color: white;
}

.grupo-item:last-child {
    border-bottom: none;
}

.grupo-item:hover {
    background: rgba(255, 255, 255, 0.05);
}
</style>
@endsection 