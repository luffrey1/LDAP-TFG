@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Gestión de Grupos LDAP</h3>
                    <div class="card-tools">
                        <a href="{{ route('gestion.grupos.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nuevo Grupo
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" id="searchInput" class="form-control" placeholder="Buscar grupos...">
                                <div class="input-group-append">
                                    <button class="btn btn-default" type="button" id="searchButton">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <select id="typeFilter" class="form-control">
                                <option value="">Todos los tipos</option>
                                <option value="posix">Posix</option>
                                <option value="unique">Unique</option>
                                <option value="both">Combinado</option>
                            </select>
                        </div>
                    </div>

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
                            <tbody id="groupsTableBody">
                                @foreach($groups as $group)
                                <tr>
                                    <td>{{ $group['cn'] }}</td>
                                    <td>{{ $group['type'] }}</td>
                                    <td>{{ $group['gidNumber'] }}</td>
                                    <td>{{ $group['description'] }}</td>
                                    <td>
                                        @if(!empty($group['members']))
                                            {{ implode(', ', $group['members']) }}
                                        @else
                                            <span class="text-muted">Sin miembros</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('gestion.grupos.edit', ['group' => $group['dn']]) }}" class="btn btn-sm btn-info">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('gestion.grupos.destroy', ['group' => $group['dn']]) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de eliminar este grupo?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div>
                            Mostrando {{ $groups->firstItem() ?? 0 }} a {{ $groups->lastItem() ?? 0 }} de {{ $total }} grupos
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

@push('scripts')
<script>
$(document).ready(function() {
    let currentPage = 1;
    let searchTerm = '';
    let typeFilter = '';

    function loadGroups() {
        $.ajax({
            url: '{{ route("gestion.grupos.index") }}',
            data: {
                page: currentPage,
                search: searchTerm,
                type: typeFilter
            },
            success: function(response) {
                let html = '';
                response.groups.forEach(function(group) {
                    html += `
                        <tr>
                            <td>${group.cn}</td>
                            <td>${group.type}</td>
                            <td>${group.gidNumber}</td>
                            <td>${group.description || ''}</td>
                            <td>${group.members.length ? group.members.join(', ') : '<span class="text-muted">Sin miembros</span>'}</td>
                            <td>
                                <a href="/gestion/grupos/${encodeURIComponent(group.dn)}/edit" class="btn btn-sm btn-info">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="/gestion/grupos/${encodeURIComponent(group.dn)}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de eliminar este grupo?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    `;
                });
                $('#groupsTableBody').html(html);
                
                // Actualizar paginación
                updatePagination(response.currentPage, response.lastPage);
            }
        });
    }

    function updatePagination(currentPage, lastPage) {
        let paginationHtml = '';
        
        // Botón anterior
        paginationHtml += `
            <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage - 1}">Anterior</a>
            </li>
        `;
        
        // Números de página
        for (let i = 1; i <= lastPage; i++) {
            paginationHtml += `
                <li class="page-item ${i === currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `;
        }
        
        // Botón siguiente
        paginationHtml += `
            <li class="page-item ${currentPage === lastPage ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${currentPage + 1}">Siguiente</a>
            </li>
        `;
        
        $('.pagination').html(paginationHtml);
    }

    // Eventos de paginación
    $(document).on('click', '.pagination .page-link', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        if (page && page !== currentPage) {
            currentPage = page;
            loadGroups();
        }
    });

    // Evento de búsqueda
    $('#searchButton').click(function() {
        searchTerm = $('#searchInput').val();
        currentPage = 1;
        loadGroups();
    });

    // Evento de filtro por tipo
    $('#typeFilter').change(function() {
        typeFilter = $(this).val();
        currentPage = 1;
        loadGroups();
    });

    // Evento de tecla en el campo de búsqueda
    $('#searchInput').keypress(function(e) {
        if (e.which === 13) {
            searchTerm = $(this).val();
            currentPage = 1;
            loadGroups();
        }
    });
});
</script>
@endpush
@endsection 