@extends('layouts.departamento')

@section('title', 'Área Privada')

@section('content')
    <div class="container mx-auto px-4 py-8 animated-fade-in">
        <!-- Alerta de usuario logueado -->
        <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6" role="alert">
            <div class="flex items-center">
                <div class="py-1">
                    <i class="fas fa-info-circle text-2xl text-blue-500 mr-4"></i>
                </div>
                <div>
                    <p class="font-bold">Bienvenido, {{ $user['nombre'] }}</p>
                    <p class="text-sm">Has accedido al área privada del departamento de informática.</p>
                </div>
            </div>
        </div>
        
        <!-- Panel principal -->
        <div class="flex flex-wrap -mx-4">
            <!-- Panel lateral - Información del usuario -->
            <div class="w-full md:w-1/4 px-4 mb-8">
                <div class="bg-white rounded-lg shadow-lg p-6 sticky top-6">
                    <div class="text-center mb-6">
                        <div class="w-32 h-32 mx-auto bg-blue-100 rounded-full flex items-center justify-center text-blue-700 text-5xl mb-4">
                            <i class="fas fa-user"></i>
                        </div>
                        <h3 class="text-xl font-semibold">{{ $user['nombre'] }}</h3>
                        <p class="text-gray-600">{{ $user['username'] }}</p>
                        @if(isset($user['email']))
                            <p class="text-gray-600 text-sm mt-1">{{ $user['email'] }}</p>
                        @endif
                        <div class="mt-2">
                            <span class="inline-block bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-1 rounded-full">
                                {{ ucfirst($user['role']) }}
                            </span>
                        </div>
                    </div>
                    
                    <!-- Menú lateral -->
                    <nav class="space-y-1">
                        <a href="#dashboard" class="flex items-center px-3 py-2 text-sm font-medium text-blue-700 bg-blue-100 rounded-md" aria-current="page">
                            <i class="fas fa-home mr-3 text-blue-500"></i>
                            Dashboard
                        </a>
                        <a href="#documentos" class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 hover:text-blue-700 hover:bg-blue-50 rounded-md">
                            <i class="fas fa-file-alt mr-3 text-gray-400"></i>
                            Documentos
                        </a>
                        <a href="#calendario" class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 hover:text-blue-700 hover:bg-blue-50 rounded-md">
                            <i class="fas fa-calendar-alt mr-3 text-gray-400"></i>
                            Calendario
                        </a>
                        <a href="#comunicaciones" class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 hover:text-blue-700 hover:bg-blue-50 rounded-md">
                            <i class="fas fa-envelope mr-3 text-gray-400"></i>
                            Comunicaciones
                        </a>
                        <a href="#recursos" class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 hover:text-blue-700 hover:bg-blue-50 rounded-md">
                            <i class="fas fa-book mr-3 text-gray-400"></i>
                            Recursos Didácticos
                        </a>
                        
                        @if($user['role'] === 'admin')
                            <div class="pt-4 mt-4 border-t border-gray-200">
                                <h4 class="px-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Administración</h4>
                                <div class="mt-2 space-y-1">
                                    <a href="{{ route('ldap.users.index') }}" class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 hover:text-blue-700 hover:bg-blue-50 rounded-md">
                                        <i class="fas fa-users mr-3 text-gray-400"></i>
                                        Gestión de Usuarios LDAP
                                    </a>
                                    <a href="{{ route('ldap.logs') }}" class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 hover:text-blue-700 hover:bg-blue-50 rounded-md">
                                        <i class="fas fa-history mr-3 text-gray-400"></i>
                                        Registros LDAP
                                    </a>
                                    <a href="#configuracion" class="flex items-center px-3 py-2 text-sm font-medium text-gray-600 hover:text-blue-700 hover:bg-blue-50 rounded-md">
                                        <i class="fas fa-cog mr-3 text-gray-400"></i>
                                        Configuración
                                    </a>
                                </div>
                            </div>
                        @endif
                    </nav>
                </div>
            </div>
            
            <!-- Contenido principal -->
            <div class="w-full md:w-3/4 px-4">
                <!-- Dashboard -->
                <div id="dashboard" class="bg-white rounded-lg shadow-lg p-6 mb-8">
                    <h2 class="text-2xl font-semibold text-blue-900 mb-6 pb-2 border-b border-gray-200">Dashboard</h2>
                    
                    <!-- Estadísticas rápidas -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div class="bg-blue-50 p-4 rounded-lg shadow">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 rounded-md bg-blue-200 p-3 text-blue-600">
                                    <i class="fas fa-file-alt text-xl"></i>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-xl font-semibold">25</h3>
                                    <p class="text-sm text-gray-600">Documentos</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-green-50 p-4 rounded-lg shadow">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 rounded-md bg-green-200 p-3 text-green-600">
                                    <i class="fas fa-calendar-check text-xl"></i>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-xl font-semibold">8</h3>
                                    <p class="text-sm text-gray-600">Eventos Próximos</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-purple-50 p-4 rounded-lg shadow">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 rounded-md bg-purple-200 p-3 text-purple-600">
                                    <i class="fas fa-comment-alt text-xl"></i>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-xl font-semibold">12</h3>
                                    <p class="text-sm text-gray-600">Mensajes Nuevos</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Actividad reciente -->
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Actividad Reciente</h3>
                        <div class="flow-root">
                            <ul class="divide-y divide-gray-200">
                                <li class="py-3">
                                    <div class="flex items-center">
                                        <i class="fas fa-file-upload text-blue-500 mr-3"></i>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-900">Subida de documentación nueva</p>
                                            <p class="text-sm text-gray-500">Has subido "Programación didáctica 2024-2025.pdf"</p>
                                        </div>
                                        <div class="text-sm text-gray-500">Hace 2 horas</div>
                                    </div>
                                </li>
                                <li class="py-3">
                                    <div class="flex items-center">
                                        <i class="fas fa-calendar-plus text-green-500 mr-3"></i>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-900">Evento creado</p>
                                            <p class="text-sm text-gray-500">Has creado el evento "Reunión de departamento"</p>
                                        </div>
                                        <div class="text-sm text-gray-500">Ayer</div>
                                    </div>
                                </li>
                                <li class="py-3">
                                    <div class="flex items-center">
                                        <i class="fas fa-comment text-purple-500 mr-3"></i>
                                        <div class="min-w-0 flex-1">
                                            <p class="text-sm font-medium text-gray-900">Nuevo mensaje</p>
                                            <p class="text-sm text-gray-500">Has recibido un mensaje de Laura Sánchez</p>
                                        </div>
                                        <div class="text-sm text-gray-500">Hace 2 días</div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Acceso rápido -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Acceso Rápido</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <a href="#" class="p-4 bg-gray-50 rounded-lg text-center hover:bg-gray-100 transition duration-300">
                                <i class="fas fa-file-alt text-blue-600 text-2xl mb-2"></i>
                                <p class="text-sm font-medium text-gray-900">Subir Documento</p>
                            </a>
                            <a href="#" class="p-4 bg-gray-50 rounded-lg text-center hover:bg-gray-100 transition duration-300">
                                <i class="fas fa-calendar-plus text-green-600 text-2xl mb-2"></i>
                                <p class="text-sm font-medium text-gray-900">Crear Evento</p>
                            </a>
                            <a href="#" class="p-4 bg-gray-50 rounded-lg text-center hover:bg-gray-100 transition duration-300">
                                <i class="fas fa-envelope text-purple-600 text-2xl mb-2"></i>
                                <p class="text-sm font-medium text-gray-900">Enviar Mensaje</p>
                            </a>
                            <a href="#" class="p-4 bg-gray-50 rounded-lg text-center hover:bg-gray-100 transition duration-300">
                                <i class="fas fa-book text-orange-600 text-2xl mb-2"></i>
                                <p class="text-sm font-medium text-gray-900">Recursos</p>
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Sección de documentos -->
                <div id="documentos" class="bg-white rounded-lg shadow-lg p-6 mb-8">
                    <h2 class="text-2xl font-semibold text-blue-900 mb-6 pb-2 border-b border-gray-200">Documentos</h2>
                    
                    <!-- Botones de acción -->
                    <div class="flex flex-wrap gap-2 mb-6">
                        <button class="bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 flex items-center">
                            <i class="fas fa-upload mr-2"></i> Subir documento
                        </button>
                        <button class="bg-blue-100 text-blue-800 py-2 px-4 rounded-lg hover:bg-blue-200 flex items-center">
                            <i class="fas fa-folder-plus mr-2"></i> Nueva carpeta
                        </button>
                    </div>
                    
                    <!-- Lista de documentos -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full bg-white">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="py-3 px-4 text-left">Nombre</th>
                                    <th class="py-3 px-4 text-left">Tipo</th>
                                    <th class="py-3 px-4 text-left">Tamaño</th>
                                    <th class="py-3 px-4 text-left">Fecha</th>
                                    <th class="py-3 px-4 text-left">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3 px-4">
                                        <div class="flex items-center">
                                            <i class="fas fa-file-pdf text-red-500 mr-2"></i>
                                            <span>Programación didáctica 2024-2025.pdf</span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">PDF</td>
                                    <td class="py-3 px-4">1.2 MB</td>
                                    <td class="py-3 px-4">15/04/2024</td>
                                    <td class="py-3 px-4">
                                        <div class="flex space-x-2">
                                            <button class="text-blue-600 hover:text-blue-800"><i class="fas fa-download"></i></button>
                                            <button class="text-gray-600 hover:text-gray-800"><i class="fas fa-edit"></i></button>
                                            <button class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3 px-4">
                                        <div class="flex items-center">
                                            <i class="fas fa-file-word text-blue-500 mr-2"></i>
                                            <span>Acta reunión departamento.docx</span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">DOCX</td>
                                    <td class="py-3 px-4">245 KB</td>
                                    <td class="py-3 px-4">10/04/2024</td>
                                    <td class="py-3 px-4">
                                        <div class="flex space-x-2">
                                            <button class="text-blue-600 hover:text-blue-800"><i class="fas fa-download"></i></button>
                                            <button class="text-gray-600 hover:text-gray-800"><i class="fas fa-edit"></i></button>
                                            <button class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="hover:bg-gray-50">
                                    <td class="py-3 px-4">
                                        <div class="flex items-center">
                                            <i class="fas fa-file-excel text-green-500 mr-2"></i>
                                            <span>Calificaciones primer trimestre.xlsx</span>
                                        </div>
                                    </td>
                                    <td class="py-3 px-4">XLSX</td>
                                    <td class="py-3 px-4">520 KB</td>
                                    <td class="py-3 px-4">05/04/2024</td>
                                    <td class="py-3 px-4">
                                        <div class="flex space-x-2">
                                            <button class="text-blue-600 hover:text-blue-800"><i class="fas fa-download"></i></button>
                                            <button class="text-gray-600 hover:text-gray-800"><i class="fas fa-edit"></i></button>
                                            <button class="text-red-600 hover:text-red-800"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Sección de calendario -->
                <div id="calendario" class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-2xl font-semibold text-blue-900 mb-6 pb-2 border-b border-gray-200">Calendario</h2>
                    
                    <!-- Mini calendario (simulado) -->
                    <div class="bg-gray-50 p-6 rounded-lg mb-6">
                        <div class="text-center mb-4">
                            <h3 class="text-lg font-medium">Abril 2024</h3>
                        </div>
                        <div class="grid grid-cols-7 gap-1 text-center">
                            <div class="text-sm font-medium text-gray-500">L</div>
                            <div class="text-sm font-medium text-gray-500">M</div>
                            <div class="text-sm font-medium text-gray-500">X</div>
                            <div class="text-sm font-medium text-gray-500">J</div>
                            <div class="text-sm font-medium text-gray-500">V</div>
                            <div class="text-sm font-medium text-gray-500">S</div>
                            <div class="text-sm font-medium text-gray-500">D</div>
                            
                            <div class="py-1 text-gray-400">29</div>
                            <div class="py-1 text-gray-400">30</div>
                            <div class="py-1 text-gray-400">31</div>
                            <div class="py-1">1</div>
                            <div class="py-1">2</div>
                            <div class="py-1 text-gray-400">3</div>
                            <div class="py-1 text-gray-400">4</div>
                            
                            <div class="py-1">5</div>
                            <div class="py-1">6</div>
                            <div class="py-1">7</div>
                            <div class="py-1">8</div>
                            <div class="py-1">9</div>
                            <div class="py-1 text-gray-400">10</div>
                            <div class="py-1 text-gray-400">11</div>
                            
                            <div class="py-1">12</div>
                            <div class="py-1">13</div>
                            <div class="py-1">14</div>
                            <div class="py-1 bg-blue-100 text-blue-800 rounded">15</div>
                            <div class="py-1 bg-blue-100 text-blue-800 rounded">16</div>
                            <div class="py-1 text-gray-400">17</div>
                            <div class="py-1 text-gray-400">18</div>
                            
                            <div class="py-1">19</div>
                            <div class="py-1">20</div>
                            <div class="py-1">21</div>
                            <div class="py-1">22</div>
                            <div class="py-1">23</div>
                            <div class="py-1 text-gray-400">24</div>
                            <div class="py-1 text-gray-400">25</div>
                            
                            <div class="py-1">26</div>
                            <div class="py-1">27</div>
                            <div class="py-1">28</div>
                            <div class="py-1">29</div>
                            <div class="py-1">30</div>
                            <div class="py-1 text-gray-400">1</div>
                            <div class="py-1 text-gray-400">2</div>
                        </div>
                    </div>
                    
                    <!-- Próximos eventos -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Próximos Eventos</h3>
                        <div class="space-y-4">
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-medium text-blue-900">Jornadas de Ciberseguridad</h4>
                                        <p class="text-sm text-gray-600">15-16 de Mayo, 2025 • Salón de Actos</p>
                                        <p class="text-sm text-gray-700 mt-2">Conferencias y talleres sobre las últimas tendencias en ciberseguridad.</p>
                                    </div>
                                    <span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded">Evento</span>
                                </div>
                            </div>
                            
                            <div class="bg-green-50 p-4 rounded-lg">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-medium text-green-900">Reunión de Departamento</h4>
                                        <p class="text-sm text-gray-600">22 de Abril, 2024 • 12:00 • Sala de Reuniones</p>
                                        <p class="text-sm text-gray-700 mt-2">Reunión mensual para seguimiento de programaciones y coordinación.</p>
                                    </div>
                                    <span class="inline-block px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded">Reunión</span>
                                </div>
                            </div>
                            
                            <div class="bg-purple-50 p-4 rounded-lg">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h4 class="font-medium text-purple-900">Entrega de Calificaciones</h4>
                                        <p class="text-sm text-gray-600">15 de Mayo, 2024 • Fecha límite</p>
                                        <p class="text-sm text-gray-700 mt-2">Fecha límite para la entrega de calificaciones del segundo trimestre.</p>
                                    </div>
                                    <span class="inline-block px-2 py-1 bg-purple-100 text-purple-800 text-xs font-semibold rounded">Plazo</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Smooth scrolling para los enlaces del menú lateral
        document.querySelectorAll('nav a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 20,
                        behavior: 'smooth'
                    });
                    
                    // Activar el enlace actual
                    document.querySelectorAll('nav a').forEach(link => {
                        link.classList.remove('text-blue-700', 'bg-blue-100');
                        link.classList.add('text-gray-600', 'hover:text-blue-700', 'hover:bg-blue-50');
                    });
                    
                    this.classList.remove('text-gray-600', 'hover:text-blue-700', 'hover:bg-blue-50');
                    this.classList.add('text-blue-700', 'bg-blue-100');
                }
            });
        });
        
        // Animación de entrada para secciones
        const sections = document.querySelectorAll('.container > div > div[id]');
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate__animated', 'animate__fadeIn');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        
        sections.forEach(section => {
            observer.observe(section);
        });
    });
</script>
@endsection 