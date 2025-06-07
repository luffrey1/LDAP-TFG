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
                                <label for="search">Buscar grupo:</label>
                                <input type="text" class="form-control" id="search" placeholder="Escribe para buscar...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="type">Filtrar por tipo:</label>
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
                                        <td>{{ $cn }}</td>
                                        <td>
                                            @if($group['type'] === 'posix')
                                                <span class="badge badge-info">Posix</span>
                                            @elseif($group['type'] === 'unique')
                                                <span class="badge badge-success">Unique Names</span>
                                            @elseif($group['type'] === 'combined')
                                                <span class="badge badge-warning">Combinado</span>
                                            @endif
                                        </td>
                                        <td>{{ $gidNumber }}</td>
                                        <td>{{ $description }}</td>
                                        <td>{{ count($group['members']) }}</td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                @if(!empty($cn))
                                                <a href="{{ route('admin.groups.edit', ['cn' => $cn]) }}" class="btn btn-sm btn-info">
                                                    <i class="fas fa-edit"></i> Editar
                                                </a>
                                                @if (!in_array($cn, ['admin', 'ldapadmins', 'sudo']))
                                                    <button type="button" class="btn btn-sm btn-danger" 
                                                            onclick="confirmDelete('{{ $cn }}')">
                                                        <i class="fas fa-trash"></i> Eliminar
                                                    </button>
                                                @endif
                                                @else
                                                <span class="text-muted">No se puede editar</span>
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
                <td>${group.cn}</td>
                <td>
                    ${group.type === 'posix' ? '<span class="badge badge-info">Posix</span>' : 
                      group.type === 'unique' ? '<span class="badge badge-success">Unique Names</span>' : 
                      '<span class="badge badge-warning">Combinado</span>'}
                </td>
                <td>${group.gidNumber || ''}</td>
                <td>${group.description || ''}</td>
                <td>${group.members.length}</td>
                <td>
                    <a href="/admin/groups/${group.cn}/edit" class="btn btn-sm btn-info">
                        <i class="fas fa-edit"></i>
                    </a>
                    <form action="/admin/groups/${group.cn}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro?')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
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