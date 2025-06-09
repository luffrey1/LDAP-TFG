@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Logs del Sistema</h3>
                    <div class="card-tools">
                        <div class="input-group input-group-sm" style="width: 250px;">
                            <input type="text" id="userSearch" class="form-control float-right" placeholder="Buscar usuario...">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-default" id="clearSearch">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Pestañas de filtro -->
                    <ul class="nav nav-tabs" id="logTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="all-tab" data-toggle="tab" href="#all" role="tab">Todos</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="users-tab" data-toggle="tab" href="#users" role="tab">Usuarios</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="groups-tab" data-toggle="tab" href="#groups" role="tab">Grupos</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="access-tab" data-toggle="tab" href="#access" role="tab">Accesos</a>
                        </li>
                    </ul>

                    <!-- Contenido de las pestañas -->
                    <div class="tab-content mt-3" id="logTabsContent">
                        <div class="tab-pane fade show active" id="all" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-hover" id="logsTable">
                                    <thead>
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
                                            <td>{{ $log->performed_by }}</td>
                                            <td>{{ $log->action }}</td>
                                            <td>{{ $log->description }}</td>
                                            <td>{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i:s') }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="mt-3">
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
            <div class="modal-header">
                <h5 class="modal-title">Detalles del Log</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>ID:</label>
                    <p id="logId"></p>
                </div>
                <div class="form-group">
                    <label>Fecha:</label>
                    <p id="logDate"></p>
                </div>
                <div class="form-group">
                    <label>Usuario:</label>
                    <p id="logUser"></p>
                </div>
                <div class="form-group">
                    <label>Acción:</label>
                    <p id="logAction"></p>
                </div>
                <div class="form-group">
                    <label>Descripción:</label>
                    <p id="logDescription"></p>
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

    // Función para filtrar por tipo de log
    function filterLogs(type) {
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
            $('#logUser').text(data.performed_by);
            $('#logAction').text(data.action);
            $('#logDescription').text(data.description);
            $('#logDetailsModal').modal('show');
        });
    }
});
</script>
@endpush

@push('styles')
<style>
.log-row {
    cursor: pointer;
}
.log-row:hover {
    background-color: #f8f9fa;
}
.nav-tabs .nav-link {
    color: #495057;
}
.nav-tabs .nav-link.active {
    color: #007bff;
    font-weight: bold;
}
</style>
@endpush 