@extends('layouts.dashboard')

@section('content')
@php
    $showUrl = route('gestion.grupos.show', ['cn' => ':cn']);
    $editUrl = route('gestion.grupos.edit', ['cn' => ':cn']);
    $deleteUrl = route('gestion.grupos.destroy', ['cn' => ':cn']);
@endphp
<div class="container" 
    data-show-url="{{ $showUrl }}"
    data-edit-url="{{ $editUrl }}"
    data-delete-url="{{ $deleteUrl }}">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="search" class="text-white">Buscar grupo:</label>
                                <input type="text" class="form-control" id="search" placeholder="Escribe para buscar...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="type" class="text-white">Filtrar por tipo:</label>
                                <select class="form-control" id="type">
                                    <option value="all">Todos los tipos</option>
                                    <option value="posix">Posix Group</option>
                                    <option value="unique">Group of Unique Names</option>
                                    <option value="combined">Combinado</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Grupos LDAP</h3>
                    <div class="card-tools">
                        <a href="{{ route('gestion.grupos.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nuevo Grupo
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>GID</th>
                                    <th>Descripción</th>
                                    <th>Miembros</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="groupsTable">
                                @forelse ($groups as $group)
                                    @php
                                        $cn = $group['cn'] ?? '';
                                        $gidNumber = $group['gidNumber'] ?? 'N/A';
                                        $description = $group['description'] ?? 'Sin descripción';
                                    @endphp
                                    <tr>
                                        <td class="text-black">{{ $cn }}</td>
                                        <td>
                                            @if($group['type'] === 'posix')
                                                <button type="button" class="btn btn-sm btn-info filter-type" data-type="posix" 
                                                    onclick="filterByType('posix')">
                                                    Posix
                                                </button>
                                            @elseif($group['type'] === 'unique')
                                                <button type="button" class="btn btn-sm btn-success filter-type" data-type="unique" 
                                                    onclick="filterByType('unique')">
                                                    Unique Names
                                                </button>
                                            @elseif($group['type'] === 'combined')
                                                <button type="button" class="btn btn-sm btn-warning filter-type" data-type="combined" 
                                                    onclick="filterByType('combined')">
                                                    Combinado
                                                </button>
                                            @endif
                                        </td>
                                        <td>{{ $gidNumber }}</td>
                                        <td>{{ $description }}</td>
                                        <td>{{ count($group['members']) }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ str_replace(':cn', $group['cn'], $showUrl) }}" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> Ver
                                                </a>
                                                @if(!empty($group['cn']))
                                                <a href="{{ str_replace(':cn', $group['cn'], $editUrl) }}" class="btn btn-sm btn-info">
                                                    <i class="fas fa-edit"></i> Editar
                                                </a>
                                                @if (!in_array($group['cn'], ['admin', 'ldapadmins', 'sudo']))
                                                    <button type="button" class="btn btn-sm btn-danger" 
                                                            onclick="confirmDelete('{{ $group['cn'] }}')">
                                                        <i class="fas fa-trash"></i> Eliminar
                                                    </button>
                                                @endif
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">No hay grupos disponibles</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            Mostrando {{ $groups->firstItem() ?? 0 }} a {{ $groups->lastItem() ?? 0 }} de {{ $groups->total() ?? 0 }} grupos
                        </div>
                        <div>
                            {{ $groups->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmar eliminación</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                ¿Está seguro de que desea eliminar este grupo?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <form id="deleteForm" method="POST" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(cn) {
    const modal = $('#deleteModal');
    const form = $('#deleteForm');
    form.attr('action', "{{ route('gestion.grupos.destroy', ':cn') }}".replace(':cn', cn));
    modal.modal('show');
}

function filterByType(type) {
    typeSelect.value = type;
    updateGroups();
}

let searchTimeout;
const searchInput = document.getElementById('search');
const typeSelect = document.getElementById('type');
const groupsTable = document.getElementById('groupsTable');

function updateGroups() {
    const search = searchInput.value;
    const type = typeSelect.value;
    const page = new URLSearchParams(window.location.search).get('page') || 1;
    
    fetch(`{{ route('gestion.grupos.index') }}?search=${encodeURIComponent(search)}&type=${encodeURIComponent(type)}&page=${page}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (!data.groups) {
            console.error('No groups data received');
            return;
        }

        const container = document.querySelector('.container');
        const showUrl = container.dataset.showUrl;
        const editUrl = container.dataset.editUrl;
        const deleteUrl = container.dataset.deleteUrl;

        groupsTable.innerHTML = data.groups.map(group => {
            const showUrlWithCn = showUrl.replace(':cn', group.cn);
            const editUrlWithCn = editUrl.replace(':cn', group.cn);
            
            return `
                <tr>
                    <td class="text-black">${group.cn}</td>
                    <td>
                        ${group.type === 'posix' ? 
                            `<button type="button" class="btn btn-sm btn-info filter-type" data-type="posix" 
                                onclick="filterByType('posix')">
                                Posix
                            </button>` :
                        group.type === 'unique' ?
                            `<button type="button" class="btn btn-sm btn-success filter-type" data-type="unique" 
                                onclick="filterByType('unique')">
                                Unique Names
                            </button>` :
                            `<button type="button" class="btn btn-sm btn-warning filter-type" data-type="combined" 
                                onclick="filterByType('combined')">
                                Combinado
                            </button>`
                        }
                    </td>
                    <td>${group.gidNumber || ''}</td>
                    <td>${group.description || ''}</td>
                    <td>${group.members ? group.members.length : 0}</td>
                    <td>
                        <div class="btn-group" role="group">
                            <a href="${showUrlWithCn}" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> Ver
                            </a>
                            <a href="${editUrlWithCn}" class="btn btn-sm btn-info">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                            <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete('${group.cn}')">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }).join('') || '<tr><td colspan="6" class="text-center">No hay grupos disponibles</td></tr>';

        // Actualizar la paginación
        const paginationContainer = document.querySelector('.pagination');
        if (paginationContainer && data.links) {
            paginationContainer.innerHTML = data.links;
        }

        // Actualizar el contador de resultados
        const resultsInfo = document.querySelector('.d-flex.justify-content-between');
        if (resultsInfo) {
            resultsInfo.innerHTML = `
                <div>
                    Mostrando ${data.from || 0} a ${data.to || 0} de ${data.total || 0} grupos
                </div>
                <div>
                    ${data.links || ''}
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        groupsTable.innerHTML = '<tr><td colspan="6" class="text-center">Error al cargar los grupos</td></tr>';
    });
}

// Modificar el evento de búsqueda para mantener la página actual
searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        const url = new URL(window.location.href);
        url.searchParams.set('page', '1'); // Resetear a la primera página en búsquedas
        window.history.pushState({}, '', url);
        updateGroups();
    }, 300);
});

// Modificar el evento de filtro para mantener la página actual
typeSelect.addEventListener('change', function() {
    const url = new URL(window.location.href);
    url.searchParams.set('page', '1'); // Resetear a la primera página en cambios de filtro
    window.history.pushState({}, '', url);
    updateGroups();
});

// Añadir evento para los enlaces de paginación
document.addEventListener('click', function(e) {
    if (e.target.matches('.pagination .page-link')) {
        e.preventDefault();
        const url = new URL(e.target.href);
        window.history.pushState({}, '', url);
        updateGroups();
    }
});

// Initial load
document.addEventListener('DOMContentLoaded', function() {
    updateGroups();
});
</script>
@endsection 