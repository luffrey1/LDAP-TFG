<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Departamento de Informática') - IES Tecnológico</title>
    
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Animate.css para animaciones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <style>
        .animated-fade-in {
            opacity: 0;
            animation: fadeIn 0.8s ease forwards;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .staggered-animation > * {
            opacity: 0;
            animation: fadeIn 0.5s ease forwards;
        }
        
        .staggered-animation > *:nth-child(1) { animation-delay: 0.1s; }
        .staggered-animation > *:nth-child(2) { animation-delay: 0.2s; }
        .staggered-animation > *:nth-child(3) { animation-delay: 0.3s; }
        .staggered-animation > *:nth-child(4) { animation-delay: 0.4s; }
        .staggered-animation > *:nth-child(5) { animation-delay: 0.5s; }
        .staggered-animation > *:nth-child(6) { animation-delay: 0.6s; }
    </style>
    
    @yield('styles')
</head>
<body class="bg-gray-100 font-sans leading-normal tracking-normal">
    <!-- Header -->
    <header class="bg-blue-900 text-white shadow-lg">
        <div class="container mx-auto flex flex-wrap p-5 flex-col md:flex-row items-center">
            <a href="{{ route('departamento.index') }}" class="flex title-font font-medium items-center text-white mb-4 md:mb-0">
                <i class="fas fa-laptop-code text-2xl mr-2"></i>
                <span class="text-xl">Departamento de Informática</span>
            </a>
            <nav class="md:ml-auto flex flex-wrap items-center text-base justify-center">
                <a href="{{ route('departamento.index') }}" class="mr-5 hover:text-blue-300 transition duration-300 {{ request()->routeIs('departamento.index') ? 'font-semibold text-blue-300 border-b-2 border-blue-300' : '' }}">
                    Inicio
                </a>
                <a href="#profesores" class="mr-5 hover:text-blue-300 transition duration-300">
                    Profesores
                </a>
                <a href="#proyectos" class="mr-5 hover:text-blue-300 transition duration-300">
                    Proyectos
                </a>
                <a href="#eventos" class="mr-5 hover:text-blue-300 transition duration-300">
                    Eventos
                </a>
                <a href="{{ route('departamento.area_privada') }}" class="mr-5 hover:text-blue-300 transition duration-300 {{ request()->routeIs('departamento.area_privada') ? 'font-semibold text-blue-300 border-b-2 border-blue-300' : '' }}">
                    Área Privada
                </a>
                
                @if (session('auth_user') && session('auth_user')['role'] === 'admin')
                    <a href="{{ route('ldap.users.index') }}" class="mr-5 hover:text-blue-300 transition duration-300 {{ request()->routeIs('ldap.users.*') || request()->routeIs('ldap.logs') ? 'font-semibold text-blue-300 border-b-2 border-blue-300' : '' }}">
                        <i class="fas fa-users-cog"></i> Administración
                    </a>
                @endif
            </nav>
            
            @if (session('auth_user'))
                <div class="inline-flex items-center bg-blue-800 border-0 py-1 px-3 focus:outline-none hover:bg-blue-700 rounded text-white mt-4 md:mt-0">
                    <span class="mr-2">{{ session('auth_user')['nombre'] }}</span>
                    <form action="{{ route('departamento.logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="hover:text-blue-300">
                            <i class="fas fa-sign-out-alt"></i> Salir
                        </button>
                    </form>
                </div>
            @else
                <a href="{{ route('departamento.login') }}" class="inline-flex items-center bg-blue-800 border-0 py-1 px-3 focus:outline-none hover:bg-blue-700 rounded text-white mt-4 md:mt-0">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    Acceso
                </a>
            @endif
        </div>
    </header>
    
    <!-- Mensajes Flash -->
    @if (session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 animate__animated animate__fadeIn" role="alert">
            <p>{{ session('success') }}</p>
        </div>
    @endif
    
    @if (session('error'))
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 animate__animated animate__fadeIn" role="alert">
            <p>{{ session('error') }}</p>
        </div>
    @endif
    
    <!-- Contenido Principal -->
    <main class="container mx-auto px-4 py-6">
        @yield('content')
    </main>
    
    <!-- Footer -->
    <footer class="bg-blue-900 text-white">
        <div class="container mx-auto px-6 pt-10 pb-6">
            <div class="flex flex-wrap">
                <div class="w-full md:w-1/3 text-center md:text-left">
                    <h5 class="uppercase mb-6 font-bold">Enlaces</h5>
                    <ul class="mb-4">
                        <li class="mt-2">
                            <a href="#" class="hover:underline hover:text-blue-300">Sitio del Instituto</a>
                        </li>
                        <li class="mt-2">
                            <a href="#" class="hover:underline hover:text-blue-300">Campus Virtual</a>
                        </li>
                        <li class="mt-2">
                            <a href="#" class="hover:underline hover:text-blue-300">Secretaría Virtual</a>
                        </li>
                    </ul>
                </div>
                <div class="w-full md:w-1/3 text-center">
                    <h5 class="uppercase mb-6 font-bold">Contacto</h5>
                    <ul class="mb-4">
                        <li class="mt-2">
                            <i class="fas fa-envelope mr-2"></i> dept.informatica@iestecnologico.edu.es
                        </li>
                        <li class="mt-2">
                            <i class="fas fa-phone mr-2"></i> 912 345 678
                        </li>
                        <li class="mt-2">
                            <i class="fas fa-map-marker-alt mr-2"></i> Edificio A, Planta 2, Aula 204
                        </li>
                    </ul>
                </div>
                <div class="w-full md:w-1/3 text-center md:text-right">
                    <h5 class="uppercase mb-6 font-bold">Redes Sociales</h5>
                    <div class="flex justify-center md:justify-end">
                        <a href="#" class="text-white hover:text-blue-300 mx-2">
                            <i class="fab fa-twitter fa-2x"></i>
                        </a>
                        <a href="#" class="text-white hover:text-blue-300 mx-2">
                            <i class="fab fa-facebook fa-2x"></i>
                        </a>
                        <a href="#" class="text-white hover:text-blue-300 mx-2">
                            <i class="fab fa-instagram fa-2x"></i>
                        </a>
                        <a href="#" class="text-white hover:text-blue-300 mx-2">
                            <i class="fab fa-linkedin fa-2x"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="border-t border-blue-800 mt-6 pt-6 text-center">
                <p>© {{ date('Y') }} - Departamento de Informática - IES Tecnológico. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>
    
    <!-- Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Inicializar animaciones
            const elements = document.querySelectorAll('.animated-fade-in');
            elements.forEach(el => {
                const observer = new IntersectionObserver(entries => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.style.animation = 'fadeIn 0.8s ease forwards';
                            observer.unobserve(entry.target);
                        }
                    });
                });
                observer.observe(el);
            });
            
            // Para elementos que deberían tener animación escalonada
            const staggeredContainers = document.querySelectorAll('.staggered-animation');
            staggeredContainers.forEach(container => {
                const observer = new IntersectionObserver(entries => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            let delay = 0.1;
                            Array.from(entry.target.children).forEach(child => {
                                child.style.animationDelay = `${delay}s`;
                                child.style.animation = 'fadeIn 0.5s ease forwards';
                                delay += 0.1;
                            });
                            observer.unobserve(entry.target);
                        }
                    });
                });
                observer.observe(container);
            });
        });
    </script>
    
    @yield('scripts')
</body>
</html> 