@extends('layouts.departamento')

@section('title', 'Registros de Actividad LDAP')

@section('content')
<div class="container mx-auto px-4 py-8 animated-fade-in">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-blue-900">Registros de Actividad LDAP</h1>
        <a href="{{ route('ldap.users.index') }}" class="bg-gray-600 text-white py-2 px-4 rounded-lg hover:bg-gray-700 transition duration-300">
            <i class="fas fa-arrow-left mr-2"></i> Volver a Usuarios
        </a>
    </div>

    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="p-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
            <h2 class="font-semibold text-lg">Últimas 100 entradas de registro</h2>
            <button id="refreshBtn" class="bg-blue-100 text-blue-700 py-1 px-3 rounded-lg hover:bg-blue-200 transition duration-300 text-sm">
                <i class="fas fa-sync-alt mr-1"></i> Refrescar
            </button>
        </div>

        <div class="overflow-x-auto">
            <div class="p-4 bg-gray-900 text-gray-200 font-mono text-sm h-96 overflow-y-auto" id="logContainer">
                @if(count($logs) > 0)
                    @foreach($logs as $log)
                        <div class="py-1 border-b border-gray-800 log-entry">
                            @php
                                $logClass = '';
                                if (strpos($log, 'ERROR') !== false) {
                                    $logClass = 'text-red-400';
                                } elseif (strpos($log, 'WARNING') !== false) {
                                    $logClass = 'text-yellow-400';
                                } elseif (strpos($log, 'INFO') !== false) {
                                    $logClass = 'text-green-400';
                                } elseif (strpos($log, 'DEBUG') !== false) {
                                    $logClass = 'text-blue-400';
                                }
                            @endphp
                            <div class="{{ $logClass }}">{{ $log }}</div>
                        </div>
                    @endforeach
                @else
                    <div class="py-2 text-gray-400">No se encontraron registros de actividad.</div>
                @endif
            </div>
        </div>
    </div>

    <div class="mt-6">
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="p-4 bg-gray-50 border-b border-gray-200">
                <h2 class="font-semibold text-lg">Filtros</h2>
            </div>
            <div class="p-4">
                <div class="flex flex-wrap gap-3">
                    <button class="filter-btn bg-red-100 text-red-800 py-1 px-3 rounded-lg hover:bg-red-200 transition duration-300 text-sm" data-filter="ERROR">
                        <i class="fas fa-times-circle mr-1"></i> Errores
                    </button>
                    <button class="filter-btn bg-yellow-100 text-yellow-800 py-1 px-3 rounded-lg hover:bg-yellow-200 transition duration-300 text-sm" data-filter="WARNING">
                        <i class="fas fa-exclamation-triangle mr-1"></i> Advertencias
                    </button>
                    <button class="filter-btn bg-green-100 text-green-800 py-1 px-3 rounded-lg hover:bg-green-200 transition duration-300 text-sm" data-filter="INFO">
                        <i class="fas fa-info-circle mr-1"></i> Información
                    </button>
                    <button class="filter-btn bg-blue-100 text-blue-800 py-1 px-3 rounded-lg hover:bg-blue-200 transition duration-300 text-sm" data-filter="DEBUG">
                        <i class="fas fa-bug mr-1"></i> Depuración
                    </button>
                    <button class="filter-btn bg-purple-100 text-purple-800 py-1 px-3 rounded-lg hover:bg-purple-200 transition duration-300 text-sm" data-filter="LOGIN">
                        <i class="fas fa-sign-in-alt mr-1"></i> Inicios de sesión
                    </button>
                    <button class="filter-btn bg-pink-100 text-pink-800 py-1 px-3 rounded-lg hover:bg-pink-200 transition duration-300 text-sm" data-filter="LOGOUT">
                        <i class="fas fa-sign-out-alt mr-1"></i> Cierres de sesión
                    </button>
                    <button class="filter-btn bg-gray-100 text-gray-800 py-1 px-3 rounded-lg hover:bg-gray-200 transition duration-300 text-sm" data-filter="all">
                        <i class="fas fa-list mr-1"></i> Mostrar todo
                    </button>
                </div>

                <div class="mt-4">
                    <label for="searchInput" class="block text-sm font-medium text-gray-700 mb-1">Buscar en los registros:</label>
                    <div class="flex">
                        <input type="text" id="searchInput" class="flex-grow px-3 py-2 border border-gray-300 rounded-l-lg focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Texto a buscar...">
                        <button id="searchBtn" class="bg-blue-600 text-white py-2 px-4 rounded-r-lg hover:bg-blue-700 transition duration-300">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const logContainer = document.getElementById('logContainer');
        const logEntries = document.querySelectorAll('.log-entry');
        const filterButtons = document.querySelectorAll('.filter-btn');
        const searchInput = document.getElementById('searchInput');
        const searchBtn = document.getElementById('searchBtn');
        const refreshBtn = document.getElementById('refreshBtn');

        // Función para filtrar logs
        function filterLogs(filterText) {
            logEntries.forEach(entry => {
                if (filterText === 'all' || entry.textContent.includes(filterText)) {
                    entry.style.display = '';
                } else {
                    entry.style.display = 'none';
                }
            });
        }

        // Configurar botones de filtro
        filterButtons.forEach(button => {
            button.addEventListener('click', function() {
                const filter = this.dataset.filter;
                filterLogs(filter);
                
                // Destacar el botón activo
                filterButtons.forEach(btn => btn.classList.remove('ring-2', 'ring-offset-2', 'ring-blue-500'));
                this.classList.add('ring-2', 'ring-offset-2', 'ring-blue-500');
            });
        });

        // Configurar búsqueda
        searchBtn.addEventListener('click', function() {
            const searchText = searchInput.value.trim().toLowerCase();
            if (searchText) {
                logEntries.forEach(entry => {
                    if (entry.textContent.toLowerCase().includes(searchText)) {
                        entry.style.display = '';
                        // Resaltar el texto encontrado (simule highlight)
                        entry.innerHTML = entry.innerHTML.replace(
                            new RegExp(searchText, 'gi'),
                            match => `<span class="bg-yellow-300 text-black px-1 rounded">${match}</span>`
                        );
                    } else {
                        entry.style.display = 'none';
                    }
                });
            } else {
                // Si no hay texto, mostrar todos
                logEntries.forEach(entry => {
                    entry.style.display = '';
                });
            }
        });

        // Permitir buscar con enter
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchBtn.click();
            }
        });

        // Botón de refrescar
        refreshBtn.addEventListener('click', function() {
            // En un caso real, aquí haríamos una petición AJAX para actualizar los logs
            // Por ahora, simplemente recargamos la página
            location.reload();
        });

        // Desplazar al final de los logs al cargar la página
        logContainer.scrollTop = logContainer.scrollHeight;
    });
</script>
@endsection 