@extends('layouts.dashboard')

@section('content')
<style>
    #grupos option {
        padding: 8px !important;
        margin: 2px !important;
        border-left: 4px solid transparent !important;
        transition: all 0.2s ease !important;
    }
    .form-control:disabled {
        background-color: #f8f9fa !important;
        color: #495057 !important;
        border-color: #ced4da !important;
        cursor: not-allowed !important;
    }
</style>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <span>{{ __('Editar Perfil') }}</span>
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

                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="uid" class="form-label">{{ __('UID') }}</label>
                            <input id="uid" type="text" class="form-control" value="{{ $ldapUid }}" disabled>
                            <div class="form-text">{{ __('El UID no puede cambiarse.') }}</div>
                        </div>

                        <div class="mb-3">
                            <label for="gid" class="form-label">{{ __('GID') }}</label>
                            <input id="gid" type="text" class="form-control" value="{{ $ldapGuid }}" disabled>
                            <div class="form-text">{{ __('El GID no puede cambiarse.') }}</div>
                        </div>

                        <div class="mb-3">
                            <label for="cn" class="form-label">{{ __('CN') }}</label>
                            <input id="cn" type="text" class="form-control" value="{{ $ldapCn }}" disabled>
                            <div class="form-text">{{ __('El CN no puede cambiarse.') }}</div>
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">{{ __('Nombre Completo') }}</label>
                            <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $fullName) }}" required>
                            @error('name')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">{{ __('Correo Electrónico') }}</label>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $user->email) }}" required>
                            @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label">{{ __('Contraseña') }}</label>
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password">
                            <div class="form-text">{{ __('Dejar en blanco para mantener la contraseña actual. Mínimo 8 caracteres.') }}</div>
                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">{{ __('Confirmar Contraseña') }}</label>
                            <input id="password_confirmation" type="password" class="form-control" name="password_confirmation">
                        </div>

                        <div class="mb-4">
                            <label class="form-label">{{ __('Grupos') }}</label>
                            <select id="grupos" class="form-select form-control" multiple size="6" style="height: auto !important; min-height: 150px !important; width: 100% !important; display: block !important; font-size: 14px !important; padding: 8px !important;" disabled>
                                @foreach($groups as $group)
                                    <option value="{{ $group->nombre_completo }}" selected>{{ $group->nombre_completo }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">{{ __('Los grupos no pueden ser modificados desde el perfil.') }}</div>
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
@endsection 