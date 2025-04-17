@extends('layouts.departamento')

@section('title', 'Acceso')

@section('content')
    <div class="container mx-auto px-4 py-10 animated-fade-in">
        <div class="max-w-md mx-auto bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-blue-900 text-white py-6 px-6">
                <h2 class="text-2xl font-bold text-center">Acceso al Sistema</h2>
                <p class="text-center text-blue-200 mt-1">Departamento de Informática</p>
            </div>
            
            <div class="p-6">
                @if($errors->any())
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                        <ul>
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                <form action="{{ route('departamento.autenticar') }}" method="POST">
                    @csrf
                    
                    <div class="mb-4">
                        <label for="username" class="block text-gray-700 text-sm font-bold mb-2">Usuario:</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <i class="fas fa-user text-gray-400"></i>
                            </div>
                            <input type="text" id="username" name="username" 
                                class="pl-10 w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                placeholder="Introduce tu nombre de usuario" value="{{ old('username') }}" required>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Contraseña:</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <i class="fas fa-lock text-gray-400"></i>
                            </div>
                            <input type="password" id="password" name="password" 
                                class="pl-10 w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" 
                                placeholder="Introduce tu contraseña" required>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <input type="checkbox" id="remember" name="remember" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="remember" class="ml-2 block text-sm text-gray-700">Recordarme</label>
                        </div>
                        
                        <a href="#" class="text-sm text-blue-600 hover:text-blue-800">¿Olvidaste tu contraseña?</a>
                    </div>
                    
                    <div>
                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50 transition duration-300">
                            Iniciar Sesión
                        </button>
                    </div>
                </form>
                
                <div class="mt-6 text-center text-sm">
                    <p class="text-gray-600">Para acceder como administrador:</p>
                    <p class="text-gray-700 font-semibold">Usuario: ldap-admin | Contraseña: password</p>
                </div>
            </div>
            
            <div class="px-6 py-4 bg-gray-100">
                <div class="text-center text-sm text-gray-600">
                    <p>¿Problemas para acceder? Contacta con el administrador del sistema.</p>
                    <p class="mt-1"><i class="fas fa-envelope mr-1"></i> soporte@iestecnologico.edu.es</p>
                </div>
            </div>
        </div>
        
        <div class="mt-8 text-center">
            <a href="{{ route('departamento.index') }}" class="text-blue-600 hover:text-blue-800 flex items-center justify-center">
                <i class="fas fa-arrow-left mr-2"></i> Volver a la página principal
            </a>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Focus en el campo de usuario al cargar la página
        document.getElementById('username').focus();
        
        // Animación de aparición del formulario
        const form = document.querySelector('form');
        form.style.opacity = '0';
        form.style.transform = 'translateY(20px)';
        form.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        
        setTimeout(() => {
            form.style.opacity = '1';
            form.style.transform = 'translateY(0)';
        }, 300);
    });
</script>
@endsection 