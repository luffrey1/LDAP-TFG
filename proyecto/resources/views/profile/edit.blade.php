@extends('layouts.dashboard')

@section('title', 'Editar Perfil')

@section('content')
<div class="container-fluid px-4">
    <h1 class="mt-4">Editar Perfil</h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="{{ route('dashboard.index') }}">Dashboard</a></li>
        <li class="breadcrumb-item active">Editar Perfil</li>
    </ol>

    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-user-edit me-1"></i>
            Información del Perfil
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('profile.update') }}">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Nombre Completo</label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name', auth()->user()->name) }}" required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" 
                               id="email" name="email" value="{{ old('email', auth()->user()->email) }}" required>
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="username" class="form-label">Nombre de Usuario</label>
                        <input type="text" class="form-control bg-dark text-light" id="username" 
                               value="{{ auth()->user()->username }}" disabled>
                    </div>

                    <div class="col-md-6">
                        <label for="role" class="form-label">Rol</label>
                        <input type="text" class="form-control bg-dark text-light" id="role" 
                               value="{{ ucfirst(auth()->user()->role) }}" disabled>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="guid" class="form-label">ID LDAP</label>
                        <input type="text" class="form-control bg-dark text-light" id="guid" 
                               value="{{ auth()->user()->guid ?? 'No disponible' }}" disabled>
                    </div>

                    <div class="col-md-6">
                        <label for="domain" class="form-label">Dominio LDAP</label>
                        <input type="text" class="form-control bg-dark text-light" id="domain" 
                               value="{{ auth()->user()->domain ?? 'No disponible' }}" disabled>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="email_verified_at" class="form-label">Email Verificado</label>
                        <input type="text" class="form-control bg-dark text-light" id="email_verified_at" 
                               value="{{ auth()->user()->email_verified_at ? 'Sí' : 'No' }}" disabled>
                    </div>

                    <div class="col-md-6">
                        <label for="created_at" class="form-label">Fecha de Registro</label>
                        <input type="text" class="form-control bg-dark text-light" id="created_at" 
                               value="{{ auth()->user()->created_at->format('d/m/Y H:i') }}" disabled>
                    </div>
                </div>

                <hr class="my-4">

                <h5 class="mb-3">Cambiar Contraseña</h5>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="current_password" class="form-label">Contraseña Actual</label>
                        <input type="password" class="form-control @error('current_password') is-invalid @enderror" 
                               id="current_password" name="current_password">
                        @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="password" class="form-label">Nueva Contraseña</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" 
                               id="password" name="password">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="password_confirmation" class="form-label">Confirmar Nueva Contraseña</label>
                        <input type="password" class="form-control" 
                               id="password_confirmation" name="password_confirmation">
                    </div>
                </div>

                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .form-control:disabled {
        color: black !important;
        border-color: var(--border-color) !important;
        cursor: not-allowed;
    }
    
    .form-control:disabled:hover {
        background-color: var(--card-bg) !important;
    }
</style>
@endsection 