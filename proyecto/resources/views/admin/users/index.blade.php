@extends('layouts.dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">{{ __('Gestión de Usuarios') }}</h5>
                        <a href="{{ route('admin.users.create') }}" class="btn btn-sm btn-light">
                            <i class="fas fa-plus"></i> {{ __('Crear Usuario') }}
                        </a>
                        <a href="/alumnos/import/form" class="btn btn-sm btn-success ms-2">
                            <i class="fas fa-file-import"></i> Importar CSV
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    @if (isset($connectionError) && $connectionError)
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i> {{ $errorMessage ?? 'Error de conexión con el servidor LDAP' }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                        <div class="mb-3 d-flex justify-content-end">
                            <button class="btn btn-primary" onclick="window.location.reload()">
                                <i class="fas fa-sync-alt me-2"></i> Reintentar conexión
                            </button>
                        </div>
                    @endif

                    <form action="{{ route('admin.users.index') }}" method="GET" class="mb-4" id="searchForm">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" id="searchInput" value="{{ $search }}" placeholder="{{ __('Buscar por nombre, apellido o email...') }}">
                                    <button class="btn btn-outline-secondary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <select name="group" class="form-select" id="groupFilter" onchange="this.form.submit()">
                                    <option value="">{{ __('Todos los grupos') }}</option>
                                    @foreach($groupList as $group)
                                        <option value="{{ $group }}" {{ $selectedGroup == $group ? 'selected' : '' }}>{{ $group }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 text-end">
                                @if ($search || $selectedGroup)
                                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
                                        {{ __('Limpiar') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>{{ __('UID') }}</th>
                                    <th>{{ __('Nombre') }}</th>
                                    <th>{{ __('Apellidos') }}</th>
                                    <th>{{ __('Email') }}</th>
                                    <th>{{ __('Grupos') }}</th>
                                    <th class="text-center">{{ __('Acciones') }}</th>
                                </tr>
                            </thead>
                            <tbody id="usersTableBody">
                                @include('admin.users.partials.user-table')
                            </tbody>
                        </table>
                    </div>

                    <div class="pagination-container mt-4">
                        {{ $users->appends(request()->query())->links('pagination.custom') }}
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            <p class="text-muted mb-0">
                                Mostrando {{ $users->firstItem() ?? 0 }} - {{ $users->lastItem() ?? 0 }} de {{ $total ?? 0 }} usuarios
                            </p>
                        </div>
                        <div>
                            <select id="perPageSelector" class="form-select form-select-sm" style="width: auto;">
                                <option value="10" {{ ($perPage ?? 10) == 10 ? 'selected' : '' }}>10 por página</option>
                                <option value="25" {{ ($perPage ?? 10) == 25 ? 'selected' : '' }}>25 por página</option>
                                <option value="50" {{ ($perPage ?? 10) == 50 ? 'selected' : '' }}>50 por página</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const groupFilter = document.getElementById('groupFilter');
    const usersTableBody = document.getElementById('usersTableBody');
    const perPageSelector = document.getElementById('perPageSelector');
    let searchTimeout;

    // Función para realizar la búsqueda AJAX
    function performSearch() {
        const searchValue = searchInput.value;
        const groupValue = groupFilter.value;
        const perPage = perPageSelector.value;

        // Mostrar indicador de carga
        usersTableBody.innerHTML = '<tr><td colspan="6" class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></td></tr>';

        // Realizar la petición AJAX
        fetch(`{{ route('admin.users.index') }}?search=${encodeURIComponent(searchValue)}&group=${encodeURIComponent(groupValue)}&perPage=${perPage}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                usersTableBody.innerHTML = `<tr><td colspan="6" class="text-center"><div class="alert alert-danger">${data.message}</div></td></tr>`;
                return;
            }
            
            // Actualizar la tabla con los nuevos resultados
            usersTableBody.innerHTML = data.html;
            
            // Actualizar la URL sin recargar la página
            const newUrl = new URL(window.location.href);
            newUrl.searchParams.set('search', searchValue);
            newUrl.searchParams.set('group', groupValue);
            newUrl.searchParams.set('perPage', perPage);
            window.history.pushState({}, '', newUrl);
        })
        .catch(error => {
            usersTableBody.innerHTML = '<tr><td colspan="6" class="text-center"><div class="alert alert-danger">Error al cargar los resultados</div></td></tr>';
            console.error('Error:', error);
        });
    }

    // Evento de input para la búsqueda
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(performSearch, 500);
    });

    // Evento de cambio para el filtro de grupo
    groupFilter.addEventListener('change', performSearch);

    // Evento de cambio para el selector de elementos por página
    perPageSelector.addEventListener('change', performSearch);

    // Prevenir el envío del formulario tradicional
    document.getElementById('searchForm').addEventListener('submit', function(e) {
        e.preventDefault();
        performSearch();
    });
});
</script>
@endpush

@endsection 