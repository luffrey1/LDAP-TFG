@extends('layouts.dashboard')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Crear Nuevo Usuario</h3>
                </div>

                <div class="card-body">
                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('gestion.usuarios.store') }}" id="createUserForm">
                        @csrf

                        <div class="form-group">
                            <label for="givenName" class="text-white">Nombre:</label>
                            <input type="text" class="form-control" id="givenName" name="givenName" required>
                        </div>

                        <div class="form-group">
                            <label for="sn" class="text-white">Apellidos:</label>
                            <input type="text" class="form-control" id="sn" name="sn" required>
                        </div>

                        <div class="form-group">
                            <label for="userPassword" class="text-white">Contraseña:</label>
                            <input type="password" class="form-control" id="userPassword" name="userPassword" required>
                        </div>

                        <div class="form-group">
                            <label for="uidNumber" class="text-white">UID Number:</label>
                            <input type="number" class="form-control" id="uidNumber" name="uidNumber" required>
                        </div>

                        <div class="form-group">
                            <label for="gidNumber" class="text-white">GID Number:</label>
                            <input type="number" class="form-control" id="gidNumber" name="gidNumber" required>
                        </div>

                        <div class="alert alert-info">
                            <h5>Información generada automáticamente:</h5>
                            <p><strong>UID:</strong> <span id="generatedUid"></span></p>
                            <p><strong>Email:</strong> <span id="generatedEmail"></span></p>
                            <p><strong>Home Directory:</strong> <span id="generatedHomeDir"></span></p>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Crear Usuario</button>
                            <a href="{{ route('gestion.usuarios.index') }}" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const givenNameInput = document.getElementById('givenName');
    const snInput = document.getElementById('sn');
    const generatedUid = document.getElementById('generatedUid');
    const generatedEmail = document.getElementById('generatedEmail');
    const generatedHomeDir = document.getElementById('generatedHomeDir');

    function updateGeneratedInfo() {
        const firstName = givenNameInput.value.toLowerCase();
        const lastName = snInput.value.toLowerCase();
        if (firstName && lastName) {
            const uid = firstName + lastName.charAt(0);
            generatedUid.textContent = uid;
            generatedEmail.textContent = uid + '@tierno.es';
            generatedHomeDir.textContent = '/home/' + uid;
        }
    }

    givenNameInput.addEventListener('input', updateGeneratedInfo);
    snInput.addEventListener('input', updateGeneratedInfo);

    // Actualizar al cargar la página si hay valores
    updateGeneratedInfo();
});
</script>
@endsection 