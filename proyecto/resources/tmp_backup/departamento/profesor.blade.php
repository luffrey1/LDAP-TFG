@extends('layouts.departamento')

@section('title', $profesor['nombre'])

@section('content')
    <div class="container mx-auto px-4 py-8 animated-fade-in">
        <!-- Breadcrumb -->
        <div class="text-sm breadcrumbs mb-6">
            <ul class="flex flex-wrap text-gray-600">
                <li><a href="{{ route('departamento.index') }}" class="hover:text-blue-700">Inicio</a></li>
                <li class="mx-2">/</li>
                <li><a href="#profesores" class="hover:text-blue-700">Profesores</a></li>
                <li class="mx-2">/</li>
                <li class="text-blue-700">{{ $profesor['nombre'] }}</li>
            </ul>
        </div>
        
        <!-- Perfil del Profesor -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <!-- Cabecera con imagen de fondo -->
            <div class="h-48 bg-gradient-to-r from-blue-800 to-blue-600 relative">
                <div class="absolute inset-0 bg-black opacity-30"></div>
                <div class="absolute bottom-0 left-0 p-6 text-white">
                    <h1 class="text-3xl font-bold">{{ $profesor['nombre'] }}</h1>
                    <p class="text-xl">{{ $profesor['especialidad'] }}</p>
                </div>
            </div>
            
            <!-- Contenido del perfil -->
            <div class="flex flex-wrap p-6">
                <!-- Columna izquierda - Foto y detalles de contacto -->
                <div class="w-full md:w-1/3 p-4">
                    <div class="bg-gray-100 p-6 rounded-lg">
                        <img src="{{ $profesor['imagen'] }}" alt="{{ $profesor['nombre'] }}" class="w-full h-64 object-cover object-center rounded-lg shadow-md mb-6">
                        
                        <h3 class="text-lg font-medium mb-4 text-blue-800 border-b border-gray-300 pb-2">Información de contacto</h3>
                        
                        <div class="mb-4">
                            <p class="flex items-center text-gray-700 mb-2">
                                <i class="fas fa-envelope text-blue-600 mr-2 w-5 text-center"></i> {{ $profesor['email'] }}
                            </p>
                            <p class="flex items-center text-gray-700 mb-2">
                                <i class="fas fa-phone text-blue-600 mr-2 w-5 text-center"></i> {{ $profesor['telefono'] }}
                            </p>
                            <p class="flex items-center text-gray-700 mb-2">
                                <i class="fas fa-building text-blue-600 mr-2 w-5 text-center"></i> {{ $profesor['despacho'] }}
                            </p>
                        </div>
                        
                        <h3 class="text-lg font-medium mb-2 text-blue-800 border-b border-gray-300 pb-2">Horario de tutoría</h3>
                        <p class="text-gray-700 mb-4">{{ $profesor['horario_tutoria'] }}</p>
                        
                        <a href="mailto:{{ $profesor['email'] }}" class="block w-full bg-blue-600 text-white text-center py-2 px-4 rounded-lg hover:bg-blue-700 transition duration-300">
                            Contactar por email
                        </a>
                    </div>
                </div>
                
                <!-- Columna derecha - Biografía, asignaturas y publicaciones -->
                <div class="w-full md:w-2/3 p-4">
                    <!-- Biografía -->
                    <div class="mb-8">
                        <h2 class="text-2xl font-medium mb-4 text-blue-900 border-b-2 border-blue-200 pb-2">Biografía</h2>
                        <p class="text-gray-700 leading-relaxed">{{ $profesor['bio'] }}</p>
                    </div>
                    
                    <!-- Asignaturas -->
                    <div class="mb-8">
                        <h2 class="text-2xl font-medium mb-4 text-blue-900 border-b-2 border-blue-200 pb-2">Asignaturas Impartidas</h2>
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="py-3 px-4 text-left">Asignatura</th>
                                        <th class="py-3 px-4 text-left">Curso</th>
                                        <th class="py-3 px-4 text-left">Horas</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($profesor['asignaturas'] as $asignatura)
                                        <tr class="hover:bg-gray-50">
                                            <td class="py-3 px-4">{{ $asignatura['nombre'] }}</td>
                                            <td class="py-3 px-4">{{ $asignatura['curso'] }}</td>
                                            <td class="py-3 px-4">{{ $asignatura['horas'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Publicaciones -->
                    <div class="mb-8">
                        <h2 class="text-2xl font-medium mb-4 text-blue-900 border-b-2 border-blue-200 pb-2">Publicaciones</h2>
                        <ul class="list-disc pl-5 space-y-2">
                            @foreach($profesor['publicaciones'] as $publicacion)
                                <li class="text-gray-700">{{ $publicacion }}</li>
                            @endforeach
                        </ul>
                    </div>
                    
                    <!-- Botones de acción -->
                    <div class="flex flex-wrap gap-2 mt-6">
                        <a href="{{ route('departamento.index') }}#profesores" class="bg-gray-200 text-gray-800 py-2 px-4 rounded-lg hover:bg-gray-300 transition duration-300 flex items-center">
                            <i class="fas fa-arrow-left mr-2"></i> Volver al listado
                        </a>
                        <a href="#" class="bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition duration-300 flex items-center">
                            <i class="fas fa-calendar-plus mr-2"></i> Solicitar tutoría
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    // Efectos de animación adicionales para la página de perfil
    document.addEventListener('DOMContentLoaded', () => {
        // Hacer que las secciones aparezcan con efecto
        const sections = document.querySelectorAll('h2');
        sections.forEach((section, index) => {
            section.style.opacity = '0';
            section.style.transition = 'opacity 0.5s ease';
            
            setTimeout(() => {
                section.style.opacity = '1';
            }, 300 + (index * 150));
        });
    });
</script>
@endsection 