@extends('layouts.departamento')

@section('title', 'Crear Nuevo Usuario LDAP')

@section('content')
<div class="container mx-auto px-4 py-8 animated-fade-in">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-blue-900">Crear Nuevo Usuario LDAP</h1>
        <a href="{{ route('ldap.users.index') }}" class="bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-700 transition duration-300">
            <i class="fas fa-arrow-left mr-2"></i> Volver al Listado
        </a>
    </div>

    @if(session('error'))
    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
        {{ session('error') }}
    </div>
    @endif

    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="p-4 bg-gray-50 border-b border-gray-200">
            <h2 class="font-semibold text-lg">Información del Usuario</h2>
        </div>

        <div class="p-6">
            <form action="{{ route('ldap.users.store') }}" method="POST">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label for="cn" class="block text-sm font-medium text-gray-700 mb-1">Nombre Completo <span class="text-red-600">*</span></label>
                        <input type="text" name="cn" id="cn" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('cn') border-red-500 @enderror" 
                            value="{{ old('cn') }}" placeholder="Nombre completo">
                        @error('cn')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="sn" class="block text-sm font-medium text-gray-700 mb-1">Apellido <span class="text-red-600">*</span></label>
                        <input type="text" name="sn" id="sn" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('sn') border-red-500 @enderror" 
                            value="{{ old('sn') }}" placeholder="Apellido">
                        @error('sn')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="uid" class="block text-sm font-medium text-gray-700 mb-1">Usuario (UID) <span class="text-red-600">*</span></label>
                        <input type="text" name="uid" id="uid" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('uid') border-red-500 @enderror" 
                            value="{{ old('uid') }}" placeholder="Nombre de usuario">
                        @error('uid')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="mail" class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-600">*</span></label>
                        <input type="email" name="mail" id="mail" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('mail') border-red-500 @enderror" 
                            value="{{ old('mail') }}" placeholder="correo@ejemplo.com">
                        @error('mail')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Contraseña <span class="text-red-600">*</span></label>
                        <input type="password" name="password" id="password" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('password') border-red-500 @enderror" 
                            placeholder="Contraseña (mínimo 8 caracteres)">
                        @error('password')
                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirmar Contraseña <span class="text-red-600">*</span></label>
                        <input type="password" name="password_confirmation" id="password_confirmation" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" 
                            placeholder="Confirmar contraseña">
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Grupos</label>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        @foreach($groups as $group)
                            <div class="flex items-center">
                                <input type="checkbox" name="groups[]" id="group_{{ $loop->index }}" 
                                    value="{{ $group->getDn() }}" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    {{ in_array($group->getName(), ['profesores']) ? 'checked' : '' }}>
                                <label for="group_{{ $loop->index }}" class="ml-2 block text-sm text-gray-900">
                                    {{ $group->getName() }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="border-t border-gray-200 pt-4 flex justify-end">
                    <button type="submit" class="bg-blue-600 text-white py-2 px-6 rounded-lg hover:bg-blue-700 transition duration-300">
                        <i class="fas fa-user-plus mr-2"></i> Crear Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Función para generar una contraseña aleatoria
        function generateRandomPassword(length = 12) {
            const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-+=";
            let password = "";
            for (let i = 0; i < length; i++) {
                const randomIndex = Math.floor(Math.random() * charset.length);
                password += charset[randomIndex];
            }
            return password;
        }
        
        // Agregar botón de generación de contraseña
        const passwordField = document.getElementById('password');
        const confirmField = document.getElementById('password_confirmation');
        
        const generateButton = document.createElement('button');
        generateButton.type = 'button';
        generateButton.className = 'bg-gray-200 text-gray-700 py-1 px-3 rounded-lg text-sm mt-2 hover:bg-gray-300 transition duration-300';
        generateButton.innerHTML = '<i class="fas fa-key mr-1"></i> Generar contraseña segura';
        
        generateButton.addEventListener('click', function() {
            const newPassword = generateRandomPassword();
            passwordField.value = newPassword;
            confirmField.value = newPassword;
            alert('Se ha generado una contraseña segura. Guárdala antes de enviar el formulario.');
        });
        
        passwordField.parentNode.appendChild(generateButton);
    });
</script>
@endsection 