@extends('layouts.dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title mb-0">
                        <i class="fas fa-history mr-2"></i>Logs del Sistema
                    </h3>
                    <div class="card-tools">
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <input type="text" id="userSearch" class="form-control float-right" placeholder="Buscar usuario...">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-light" id="clearSearch">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Pestañas de filtro -->
                    <ul class="nav nav-tabs nav-fill mb-4" id="logTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="all-tab" data-toggle="tab" href="#all" role="tab">
                                <i class="fas fa-list mr-1"></i> Todos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="users-tab" data-toggle="tab" href="#users" role="tab">
                                <i class="fas fa-users mr-1"></i> Usuarios
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="groups-tab" data-toggle="tab" href="#groups" role="tab">
                                <i class="fas fa-user-friends mr-1"></i> Grupos
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="access-tab" data-toggle="tab" href="#access" role="tab">
                                <i class="fas fa-sign-in-alt mr-1"></i> Accesos
                            </a>
                        </li>
                    </ul>

                    <!-- Contenido de las pestañas -->
                    <div class="tab-content" id="logTabsContent">
                        <div class="tab-pane fade show active" id="all" role="tabpanel">
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
                                    <tbody>
                                        @foreach($logs as $log)
                                        <tr class="log-row" data-id="{{ $log->id }}" data-type="{{ $log->type }}">
                                            <td>
                                                <span class="badge badge-info text-black">
                                                    <i class="fas fa-user mr-1"></i>{{ $log->user }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-{{ $log->level === 'WARNING' ? 'warning' : 'success' }} text-black">
                                                    {{ $log->action }}
                                                </span>
                                            </td>
                                            <td class="text-black">{{ $log->description }}</td>
                                            <td>
                                                <span class="text-muted">
                                                    <i class="far fa-clock mr-1"></i>
                                                    {{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i:s') }}
                                                </span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-4">
                                {{ $logs->links() }}
                            </div>
                        </div>
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
    // Inicializar DataTables
    var table = $('#logsTable').DataTable({
        "paging": false,
        "ordering": true,
        "info": false,
        "searching": false,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Spanish.json"
        }
    });

    // Función para determinar el tipo de log basado en la acción
    function getLogType(action, description) {
        action = action.toLowerCase();
        description = description.toLowerCase();

        // Detección de acciones de usuario
        if (action.includes('crear usuario') || 
            action.includes('actualizar usuario') || 
            action.includes('eliminar usuario') || 
            description.includes('usuario ldap creado') ||
            description.includes('usuario ldap actualizado') ||
            description.includes('usuario ldap eliminado')) {
            return 'users';
        }
        
        // Detección de acciones de grupo
        if (action.includes('crear grupo') || 
            action.includes('actualizar grupo') || 
            action.includes('eliminar grupo') || 
            description.includes('grupo ldap creado') ||
            description.includes('grupo ldap actualizado') ||
            description.includes('grupo ldap eliminado')) {
            return 'groups';
        }
        
        // Detección de intentos de acceso
        if (action.includes('intento de acceso') || 
            action.includes('acceso exitoso') || 
            action.includes('acceso fallido') || 
            description.includes('desde')) {
            return 'access';
        }
        
        return 'all';
    }

    // Asignar tipos a las filas
    $('.log-row').each(function() {
        var $row = $(this);
        var action = $row.find('td:eq(1)').text().trim();
        var description = $row.find('td:eq(2)').text().trim();
        var type = getLogType(action, description);
        $row.attr('data-type', type);
        console.log('Row type:', type, 'Action:', action, 'Description:', description); // Debug
    });

    // Función para filtrar por tipo de log
    function filterLogs(type) {
        console.log('Filtering by type:', type); // Debug
        if (type === 'all') {
            $('.log-row').show();
        } else {
            $('.log-row').hide();
            $('.log-row[data-type="' + type + '"]').show();
        }
    }

    // Manejar cambios de pestaña
    $('#logTabs a').on('click', function(e) {
        e.preventDefault();
        $(this).tab('show');
        
        var type = $(this).attr('id').replace('-tab', '');
        console.log('Tab clicked:', type); // Debug
        filterLogs(type);
    });

    // Búsqueda de usuario
    $('#userSearch').on('keyup', function() {
        var searchText = $(this).val().toLowerCase();
        $('.log-row').each(function() {
            var userText = $(this).find('td:first').text().toLowerCase();
            $(this).toggle(userText.indexOf(searchText) > -1);
        });
    });

    // Limpiar búsqueda
    $('#clearSearch').on('click', function() {
        $('#userSearch').val('');
        $('.log-row').show();
    });

    // Mostrar detalles del log al hacer clic
    $('.log-row').on('click', function() {
        var id = $(this).data('id');
        showLogDetails(id);
    });

    // Función para mostrar detalles del log
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

    // Aplicar filtro inicial
    filterLogs('all');
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
    background-color: #e9ecef;
    color: #000 !important;
}
.badge-info {
    background-color: #e9ecef !important;
}
.badge-warning {
    background-color: #ffeeba !important;
}
.badge-success {
    background-color: #d4edda !important;
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