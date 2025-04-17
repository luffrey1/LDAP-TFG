@extends('layouts.departamento')

@section('title', 'Inicio')

@section('content')
    <!-- Hero Section -->
    <section class="text-white py-16 animated-fade-in">
        <div class="container mx-auto flex flex-col md:flex-row items-center">
            <div class="md:w-1/2 flex flex-col items-start justify-center p-8">
                <h1 class="text-4xl font-bold leading-tight mb-4 text-blue-900">{{ $departamento['nombre'] }}</h1>
                <p class="leading-relaxed mb-6 text-gray-700">{{ $departamento['descripcion'] }}</p>
                <div class="flex justify-center md:justify-start">
                    <a href="#profesores" class="bg-blue-700 text-white py-2 px-6 rounded-lg hover:bg-blue-800 transition duration-300 mr-4">
                        Nuestro Equipo
                    </a>
                    <a href="#proyectos" class="bg-blue-600 text-white py-2 px-6 rounded-lg hover:bg-blue-700 transition duration-300">
                        Proyectos
                    </a>
                </div>
            </div>
            <div class="md:w-1/2 p-8">
                <img class="object-cover object-center rounded-lg shadow-xl" alt="Departamento de Informática" src="https://images.unsplash.com/photo-1581092918056-0c4c3acd3789?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1470&q=80">
            </div>
        </div>
    </section>
    
    <!-- Información del Departamento -->
    <section class="py-8 bg-white shadow-inner animated-fade-in">
        <div class="container mx-auto px-4">
            <div class="flex flex-wrap -mx-4">
                <div class="w-full md:w-1/3 px-4 mb-8">
                    <div class="h-full bg-gray-100 p-6 rounded-lg shadow hover:shadow-lg transition duration-300">
                        <div class="w-10 h-10 inline-flex items-center justify-center rounded-full bg-blue-100 text-blue-700 mb-4">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <h2 class="text-lg font-medium title-font mb-2">Jefe de Departamento</h2>
                        <p class="leading-relaxed text-base">{{ $departamento['jefe_departamento'] }}</p>
                    </div>
                </div>
                <div class="w-full md:w-1/3 px-4 mb-8">
                    <div class="h-full bg-gray-100 p-6 rounded-lg shadow hover:shadow-lg transition duration-300">
                        <div class="w-10 h-10 inline-flex items-center justify-center rounded-full bg-blue-100 text-blue-700 mb-4">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h2 class="text-lg font-medium title-font mb-2">Contacto</h2>
                        <p class="leading-relaxed text-base">{{ $departamento['email'] }}</p>
                        <p class="leading-relaxed text-base">{{ $departamento['telefono'] }}</p>
                    </div>
                </div>
                <div class="w-full md:w-1/3 px-4 mb-8">
                    <div class="h-full bg-gray-100 p-6 rounded-lg shadow hover:shadow-lg transition duration-300">
                        <div class="w-10 h-10 inline-flex items-center justify-center rounded-full bg-blue-100 text-blue-700 mb-4">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h2 class="text-lg font-medium title-font mb-2">Ubicación</h2>
                        <p class="leading-relaxed text-base">{{ $departamento['ubicacion'] }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Profesores -->
    <section id="profesores" class="py-12 animated-fade-in">
        <div class="container mx-auto px-4">
            <div class="flex flex-col text-center w-full mb-12">
                <h2 class="text-3xl font-medium title-font mb-4 text-blue-900">Nuestro Equipo Docente</h2>
                <p class="lg:w-2/3 mx-auto leading-relaxed text-base">Contamos con un equipo de profesionales altamente cualificados y con amplia experiencia en el sector informático.</p>
            </div>
            
            <div class="flex flex-wrap -m-4 staggered-animation">
                @foreach($profesores as $profesor)
                    <div class="p-4 lg:w-1/3 md:w-1/2">
                        <div class="h-full flex flex-col items-center text-center bg-white p-6 rounded-lg shadow-lg hover:shadow-xl transition duration-300">
                            <img alt="{{ $profesor['nombre'] }}" class="rounded-full w-40 h-40 object-cover object-center mb-4 shadow-md" src="{{ $profesor['imagen'] }}">
                            <div class="w-full">
                                <h3 class="text-xl font-medium text-gray-900 mb-2">{{ $profesor['nombre'] }}</h3>
                                <p class="text-blue-600 mb-3">{{ $profesor['especialidad'] }}</p>
                                <div class="mb-4">
                                    <p class="text-gray-600 text-sm">
                                        <strong>Asignaturas:</strong> 
                                        {{ implode(', ', $profesor['asignaturas']) }}
                                    </p>
                                </div>
                                <a href="{{ route('departamento.profesor', $profesor['id']) }}" class="bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition duration-300 inline-block">
                                    Ver perfil
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    
    <!-- Proyectos -->
    <section id="proyectos" class="py-12 bg-gray-100 animated-fade-in">
        <div class="container mx-auto px-4">
            <div class="flex flex-col text-center w-full mb-12">
                <h2 class="text-3xl font-medium title-font mb-4 text-blue-900">Proyectos Destacados</h2>
                <p class="lg:w-2/3 mx-auto leading-relaxed text-base">Nuestro departamento trabaja en diversos proyectos innovadores relacionados con las tecnologías de la información.</p>
            </div>
            
            <div class="flex flex-wrap -m-4 staggered-animation">
                @foreach($proyectos as $proyecto)
                    <div class="p-4 md:w-1/3">
                        <div class="h-full bg-white p-6 rounded-lg shadow hover:shadow-lg transition duration-300">
                            <h3 class="text-xl font-medium text-gray-900 mb-2">{{ $proyecto['titulo'] }}</h3>
                            <div class="inline-block px-3 py-1 mb-3 text-sm font-semibold 
                                {{ $proyecto['estado'] == 'Completado' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }} 
                                rounded-full">
                                {{ $proyecto['estado'] }}
                            </div>
                            <p class="leading-relaxed mb-3">{{ $proyecto['descripcion'] }}</p>
                            <p class="text-sm text-gray-600">Coordinador: {{ $proyecto['coordinador'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    
    <!-- Eventos -->
    <section id="eventos" class="py-12 animated-fade-in">
        <div class="container mx-auto px-4">
            <div class="flex flex-col text-center w-full mb-12">
                <h2 class="text-3xl font-medium title-font mb-4 text-blue-900">Próximos Eventos</h2>
                <p class="lg:w-2/3 mx-auto leading-relaxed text-base">Organizamos y participamos en diversos eventos relacionados con la informática y las nuevas tecnologías.</p>
            </div>
            
            <div class="flex flex-wrap -m-4 staggered-animation">
                @foreach($eventos as $evento)
                    <div class="p-4 lg:w-1/3 md:w-1/2">
                        <div class="h-full bg-white p-6 rounded-lg shadow hover:shadow-lg transition duration-300">
                            <div class="w-12 h-12 inline-flex items-center justify-center rounded-full bg-blue-100 text-blue-700 mb-4">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <h3 class="text-xl font-medium text-gray-900 mb-2">{{ $evento['titulo'] }}</h3>
                            <p class="text-blue-600 mb-3"><i class="far fa-clock mr-2"></i>{{ $evento['fecha'] }}</p>
                            <p class="leading-relaxed mb-3">{{ $evento['descripcion'] }}</p>
                            <p class="text-sm text-gray-600"><i class="fas fa-map-pin mr-2"></i>{{ $evento['lugar'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    
    <!-- Call to Action -->
    <section class="py-12 bg-blue-900 text-white animated-fade-in">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-3xl font-medium title-font mb-4">¿Quieres formar parte de nuestro equipo?</h2>
            <p class="lg:w-2/3 mx-auto leading-relaxed text-base mb-6">Estamos siempre abiertos a la colaboración con otros profesionales del sector informático.</p>
            <button class="bg-white text-blue-900 py-2 px-6 rounded-lg hover:bg-blue-100 transition duration-300">
                Contactar
            </button>
        </div>
    </section>
@endsection

@section('scripts')
<script>
    // Script adicional específico para esta página
    document.addEventListener('DOMContentLoaded', () => {
        // Animación suave al hacer clic en los enlaces de navegación
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });
    });
</script>
@endsection 