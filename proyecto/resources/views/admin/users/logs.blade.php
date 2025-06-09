@extends('layouts.dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-history mr-2"></i>Logs del Sistema
                    </h3>
                    <div class="d-flex align-items-center">
                        <div class="input-group input-group-sm mr-3" style="width: 250px;">
                            <input type="text" id="searchInput" class="form-control" placeholder="Buscar...">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-light" id="clearSearch">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <span id="visibleCount" class="badge badge-light"></span>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Pestañas de filtro -->
                    <ul class="nav nav-tabs nav-fill mb-4" id="logTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="all-tab" data-type="all" href="#all" role="tab">
                                <i class="fas fa-list mr-1"></i> Todos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="users-tab" data-type="users" href="#users" role="tab">
                                <i class="fas fa-users mr-1"></i> Usuarios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="groups-tab" data-type="groups" href="#groups" role="tab">
                                <i class="fas fa-user-friends mr-1"></i> Grupos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="access-tab" data-type="access" href="#access" role="tab">
                                <i class="fas fa-sign-in-alt mr-1"></i> Accesos
                            </a>
                        </li>
                    </ul>

                    <!-- Tabla de logs -->
                    <div class="table-responsive">
                        <table class="table table-hover" id="logsTable">
                            <thead class="thead-light">
                                <tr>
                                    <th>Usuario</th>
                                    <th>Acción</th>
                                    <th>Descripción</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody id="logsTableBody">
                                <!-- Los logs se cargarán aquí dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                    <div id="pagination" class="mt-4">
                        <!-- La paginación se cargará aquí dinámicamente -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para detalles del log -->
<div class="modal fade" id="logDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle mr-2"></i>Detalles del Log
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label class="font-weight-bold">ID:</label>
                    <p id="logId" class="text-muted"></p>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold">Fecha:</label>
                    <p id="logDate" class="text-muted"></p>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold">Usuario:</label>
                    <p id="logUser" class="text-muted"></p>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold">Acción:</label>
                    <p id="logAction" class="text-muted"></p>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold">Descripción:</label>
                    <p id="logDescription" class="text-muted"></p>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let currentType = 'all';
    let currentSearch = '';
    let currentPage = 1;

    function loadLogs(type = currentType, search = currentSearch, page = currentPage) {
        currentType = type;
        currentSearch = search;
        currentPage = page;

        $.get('{{ route("admin.logs") }}', {
            type: type,
            search: search,
            page: page
        }, function(response) {
            $('#logsTableBody').html(response.html);
            $('#pagination').html(response.pagination);
            updateVisibleCount(response.total);
        });
    }

    // Manejar cambios de pestaña
    $('#logTabs a').on('click', function(e) {
        e.preventDefault();
        $('#logTabs a').removeClass('active');
        $(this).addClass('active');
        
        const type = $(this).data('type');
        loadLogs(type);
    });

    // Búsqueda
    let searchTimeout;
    $('#searchInput').on('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const searchText = $(this).val();
            loadLogs(currentType, searchText);
        }, 300);
    });

    // Limpiar búsqueda
    $('#clearSearch').on('click', function() {
        $('#searchInput').val('');
        loadLogs(currentType, '');
    });

    // Mostrar detalles del log
    $(document).on('click', '.log-row', function() {
        const id = $(this).data('id');
        showLogDetails(id);
    });

    function showLogDetails(id) {
        $.get('/admin/logs/' + id, function(data) {
            $('#logId').text(data.id);
            $('#logDate').text(new Date(data.created_at).toLocaleString());
            $('#logUser').text(data.user);
            $('#logAction').text(data.action);
            $('#logDescription').text(data.description);
            $('#logDetailsModal').modal('show');
        });
    }

    // Función para actualizar el contador
    function updateVisibleCount(count) {
        $('#visibleCount').text('Filas visibles: ' + count);
    }

    // Manejar paginación
    $(document).on('click', '.pagination a', function(e) {
        e.preventDefault();
        const page = $(this).attr('href').split('page=')[1];
        loadLogs(currentType, currentSearch, page);
    });

    // Cargar logs iniciales
    loadLogs();
});
</script>
@endpush

@push('styles')
<style>
.log-row {
    cursor: pointer;
    transition: all 0.2s ease;
}
.log-row:hover {
    background-color: #f8f9fa;
    transform: translateX(5px);
}
.nav-tabs .nav-link {
    color: #495057;
    border: none;
    border-bottom: 2px solid transparent;
    padding: 0.75rem 1rem;
    transition: all 0.2s ease;
}
.nav-tabs .nav-link:hover {
    border-bottom-color: #007bff;
    color: #007bff;
}
.nav-tabs .nav-link.active {
    color: #007bff;
    font-weight: bold;
    border-bottom: 2px solid #007bff;
    background: none;
}
.badge {
    padding: 0.5em 0.75em;
    font-weight: 500;
}
.card {
    border: none;
    border-radius: 0.5rem;
}
.card-header {
    border-radius: 0.5rem 0.5rem 0 0 !important;
}
.table th {
    border-top: none;
    font-weight: 600;
}
.pagination {
    justify-content: center;
}
.page-item.active .page-link {
    background-color: #007bff;
    border-color: #007bff;
}
.modal-content {
    border-radius: 0.5rem;
    border: none;
}
.modal-header {
    border-radius: 0.5rem 0.5rem 0 0;
}
</style>
@endpush 