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
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required>
                            @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
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
                                        style="padding: 8px !important; margin: 2px !important;">
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
@endsection 