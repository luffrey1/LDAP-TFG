@extends('layouts.dashboard')

@section('content')
<div class="container">
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
                        <a href="{{ route('admin.groups.create') }}" class="btn btn-primary">
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
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-info filter-type" data-type="posix" 
                                                    onclick="filterByType('posix')" 
                                                    style="{{ $group['type'] === 'posix' ? 'background-color: #17a2b8; color: white;' : '' }}">
                                                    Posix
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-success filter-type" data-type="unique" 
                                                    onclick="filterByType('unique')"
                                                    style="{{ $group['type'] === 'unique' ? 'background-color: #28a745; color: white;' : '' }}">
                                                    Unique Names
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-warning filter-type" data-type="combined" 
                                                    onclick="filterByType('combined')"
                                                    style="{{ $group['type'] === 'combined' ? 'background-color: #ffc107; color: black;' : '' }}">
                                                    Combinado
                                                </button>
                                            </div>
                                        </td>
                                        <td>{{ $gidNumber }}</td>
                                        <td>{{ $description }}</td>
                                        <td>{{ count($group['members']) }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.groups.show', ['cn' => $group['cn']]) }}" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> Ver
                                                </a>
                                                @if(!empty($group['cn']))
                                                <a href="{{ route('admin.groups.edit', ['cn' => $group['cn']]) }}" class="btn btn-sm btn-info">
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
    form.attr('action', "{{ route('admin.groups.destroy', ':cn') }}".replace(':cn', cn));
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
    
    fetch(`{{ route('admin.groups.index') }}?search=${search}&type=${type}`, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        groupsTable.innerHTML = data.groups.map(group => `
            <tr>
                <td class="text-black">${group.cn}</td>
                <td>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-sm btn-outline-info filter-type" data-type="posix" 
                            onclick="filterByType('posix')"
                            style="${group.type === 'posix' ? 'background-color: #17a2b8; color: white;' : ''}">
                            Posix
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success filter-type" data-type="unique" 
                            onclick="filterByType('unique')"
                            style="${group.type === 'unique' ? 'background-color: #28a745; color: white;' : ''}">
                            Unique Names
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning filter-type" data-type="combined" 
                            onclick="filterByType('combined')"
                            style="${group.type === 'combined' ? 'background-color: #ffc107; color: black;' : ''}">
                            Combinado
                        </button>
                    </div>
                </td>
                <td>${group.gidNumber || ''}</td>
                <td>${group.description || ''}</td>
                <td>${group.members.length}</td>
                <td>
                    <div class="btn-group" role="group">
                        <a href="{{ route('admin.groups.show', ['cn' => '']) }}${group.cn}" class="btn btn-sm btn-primary">
                            <i class="fas fa-eye"></i> Ver
                        </a>
                        <a href="{{ route('admin.groups.edit', ['cn' => '']) }}${group.cn}" class="btn btn-sm btn-info">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete('${group.cn}')">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    })
    .catch(error => console.error('Error:', error));
}

searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(updateGroups, 300);
});

typeSelect.addEventListener('change', updateGroups);
</script>
@endsection 